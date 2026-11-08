<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\Antecedente;
use App\Models\Paciente;
use App\Models\Rol;
use App\Models\User;
use App\Rules\ClaveSegura;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * CU05 · Gestionar pacientes y CU06 · Gestionar antecedentes clínicos.
 *
 * Toda alta o modificación se ejecuta dentro de DB::transaction (RNF11).
 * El paciente siempre queda vinculado a una cuenta de la tabla usuario
 * con rol Paciente y clave temporal (RNF04).
 */
class PacienteService
{
    /** Campos clínicos que se guardan en la tabla antecedente. */
    private const CAMPOS_ANTECEDENTE = [
        'tipo_sangre',
        'alergias',
        'enfermedades_cronicas',
        'medicamentos',
        'antecedentes',
        'contacto_nombre',
        'contacto_parentesco',
        'contacto_telefono',
    ];

    public function __construct(
        private CodigoService $codigos,
        private BitacoraService $bitacora,
    ) {
    }

    /**
     * Listado paginado con búsqueda por CI, apellidos, nombres o código.
     */
    public function listar(?string $buscar, ?string $estado): LengthAwarePaginator
    {
        return Paciente::query()
            ->with(['usuario', 'antecedente'])
            ->when(filled($buscar), function ($query) use ($buscar) {
                $texto = '%'.mb_strtolower(trim($buscar)).'%';

                $query->where(fn ($w) => $w
                    ->whereRaw('lower(ci) like ?', [$texto])
                    ->orWhereRaw('lower(apellidos) like ?', [$texto])
                    ->orWhereRaw('lower(nombres) like ?', [$texto])
                    ->orWhereRaw('lower(codigo) like ?', [$texto]));
            })
            ->when($estado !== null && $estado !== '', fn ($q) => $q->where('estado', $estado === '1'))
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Registra al paciente, su cuenta de acceso y sus antecedentes.
     *
     * @return array{paciente: Paciente, clave_temporal: string, username: string}
     */
    public function registrar(array $datos, User $actor): array
    {
        return DB::transaction(function () use ($datos, $actor) {
            $codigo = $this->codigos->paciente();
            $claveTemporal = ClaveSegura::generar();
            $ci = trim((string) $datos['ci']);
            $username = mb_strtolower($ci);
            $correo = ($datos['correo'] ?? null) ?: $this->correoPlaceholder($username);

            // Cuenta de acceso: username = CI, clave temporal cifrada con bcrypt.
            $usuario = User::create([
                'id_rol' => $this->idRolPaciente(),
                'username' => $username,
                'correo' => $correo,
                'password_hash' => Hash::make($claveTemporal),
                'debe_cambiar_clave' => true,
                'estado' => true,
            ]);

            $paciente = Paciente::create([
                'id_usuario' => $usuario->id_usuario,
                'codigo' => $codigo,
                'ci' => $ci,
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
                'fecha_nacimiento' => $datos['fecha_nacimiento'],
                'sexo' => $datos['sexo'],
                'telefono' => $datos['telefono'],
                'correo' => $datos['correo'] ?? null,
                'direccion' => $datos['direccion'] ?? null,
                'recibe_recordatorios' => (bool) ($datos['recibe_recordatorios'] ?? true),
                'estado' => true,
            ]);

            $this->guardarAntecedentes($paciente, $datos, $actor);

            $this->bitacora->registrar(
                Bitacora::REGISTRAR_PACIENTE,
                'paciente',
                $paciente->id_paciente,
                "Paciente {$paciente->codigo} · CI {$paciente->ci} · cuenta {$usuario->username}",
                $actor->id_usuario,
            );

            return [
                'paciente' => $paciente,
                'clave_temporal' => $claveTemporal,
                'username' => $usuario->username,
            ];
        });
    }

    /**
     * Actualiza los datos del paciente, su cuenta y sus antecedentes.
     */
    public function actualizar(Paciente $paciente, array $datos, User $actor): Paciente
    {
        return DB::transaction(function () use ($paciente, $datos, $actor) {
            $ci = trim((string) $datos['ci']);
            $username = mb_strtolower($ci);
            $usuario = $paciente->usuario;

            // La cuenta mantiene sincronizados el username (CI) y el correo.
            if ($usuario) {
                $usuario->fill([
                    'username' => $username,
                    'correo' => ($datos['correo'] ?? null) ?: $this->correoPlaceholder($username, $usuario->id_usuario),
                ])->save();
            }

            $paciente->fill([
                'ci' => $ci,
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
                'fecha_nacimiento' => $datos['fecha_nacimiento'],
                'sexo' => $datos['sexo'],
                'telefono' => $datos['telefono'],
                'correo' => $datos['correo'] ?? null,
                'direccion' => $datos['direccion'] ?? null,
                'recibe_recordatorios' => (bool) ($datos['recibe_recordatorios'] ?? true),
            ])->save();

            $this->guardarAntecedentes($paciente, $datos, $actor);

            $this->bitacora->registrar(
                Bitacora::MODIFICAR_PACIENTE,
                'paciente',
                $paciente->id_paciente,
                "Paciente {$paciente->codigo} actualizado",
                $actor->id_usuario,
            );

            return $paciente->refresh();
        });
    }

    /**
     * Baja lógica (RNF13): nunca se elimina al paciente ni su historial.
     * La cuenta asociada también se desactiva para impedir el acceso.
     */
    public function cambiarEstado(Paciente $paciente, bool $estado, User $actor): Paciente
    {
        DB::transaction(function () use ($paciente, $estado, $actor) {
            $paciente->forceFill(['estado' => $estado])->save();
            $paciente->usuario?->forceFill(['estado' => $estado])->save();

            // Al desactivar, se cierran las sesiones abiertas de la cuenta.
            if (! $estado && $paciente->id_usuario) {
                DB::table('sessions')->where('user_id', $paciente->id_usuario)->delete();
            }

            $this->bitacora->registrar(
                $estado ? Bitacora::ACTIVAR_PACIENTE : Bitacora::DESACTIVAR_PACIENTE,
                'paciente',
                $paciente->id_paciente,
                $estado ? 'Paciente activado' : 'Paciente desactivado (baja lógica)',
                $actor->id_usuario,
            );
        });

        return $paciente->refresh();
    }

    /**
     * CU04 · El paciente actualiza sus datos de contacto, el contacto de
     * emergencia y la preferencia de recordatorios. CI, nombres y
     * antecedentes clínicos son de solo lectura (los cambia la recepción).
     * $datos ya viene validado por PerfilRequest.
     */
    public function actualizarPerfil(Paciente $paciente, array $datos, User $actor): Paciente
    {
        return DB::transaction(function () use ($paciente, $datos, $actor) {
            $correo = ($datos['correo'] ?? null) ?: null;

            $paciente->fill([
                'telefono' => $datos['telefono'],
                'correo' => $correo,
                'direccion' => ($datos['direccion'] ?? null) ?: null,
                'recibe_recordatorios' => (bool) ($datos['recibe_recordatorios'] ?? false),
            ])->save();

            // La cuenta usa el mismo correo (o uno interno si lo deja vacío).
            $paciente->usuario?->fill([
                'correo' => $correo ?? $this->correoPlaceholder($paciente->usuario->username, $paciente->usuario->id_usuario),
            ])->save();

            // Contacto de emergencia: solo esos campos; el resto de los antecedentes no se toca.
            $antecedente = Antecedente::firstOrNew(['id_paciente' => $paciente->id_paciente]);
            foreach (['contacto_nombre', 'contacto_parentesco', 'contacto_telefono'] as $campo) {
                $antecedente->{$campo} = ($datos[$campo] ?? null) ? trim($datos[$campo]) : null;
            }
            if ($antecedente->exists || $antecedente->isDirty()) {
                $antecedente->actualizado_por = $actor->id_usuario;
                $antecedente->save();
            }

            $this->bitacora->registrar(
                Bitacora::ACTUALIZAR_PERFIL,
                'paciente',
                $paciente->id_paciente,
                "El paciente {$paciente->codigo} actualizó su perfil",
                $actor->id_usuario,
            );

            return $paciente->refresh();
        });
    }

    /**
     * CU06 · Crea o actualiza los antecedentes clínicos del paciente.
     */
    public function actualizarAntecedentes(Paciente $paciente, array $datos, User $actor): ?Antecedente
    {
        return DB::transaction(function () use ($paciente, $datos, $actor) {
            $antecedente = $this->guardarAntecedentes($paciente, $datos, $actor);

            $this->bitacora->registrar(
                Bitacora::ACTUALIZAR_ANTECEDENTES,
                'antecedente',
                $paciente->id_paciente,
                "Antecedentes del paciente {$paciente->codigo}",
                $actor->id_usuario,
            );

            return $antecedente;
        });
    }

    /**
     * Guarda los antecedentes. Si no hay ningún dato y la fila no existe,
     * no se crea un registro vacío. Si ya existe, se actualiza (incluso a NULL).
     */
    private function guardarAntecedentes(Paciente $paciente, array $datos, User $actor): ?Antecedente
    {
        $valores = [];

        foreach (self::CAMPOS_ANTECEDENTE as $campo) {
            $valor = $datos[$campo] ?? null;
            $valores[$campo] = is_string($valor) && trim($valor) === '' ? null : (is_string($valor) ? trim($valor) : $valor);
        }

        $antecedente = Antecedente::firstOrNew(['id_paciente' => $paciente->id_paciente]);
        $sinDatos = collect($valores)->every(fn ($valor) => $valor === null);

        if (! $antecedente->exists && $sinDatos) {
            return null;
        }

        $antecedente->fill($valores);
        $antecedente->actualizado_por = $actor->id_usuario;
        $antecedente->save();

        return $antecedente;
    }

    /**
     * Correo interno para pacientes sin correo propio. El campo usuario.correo
     * es obligatorio y único, así que se genera uno estable a partir del CI.
     */
    private function correoPlaceholder(string $username, ?int $ignorarUsuario = null): string
    {
        $base = Str::slug($username) ?: 'paciente';
        $correo = "{$base}@pacientes.vidasalud.bo";
        $contador = 1;

        while (User::query()
            ->whereRaw('lower(correo) = ?', [mb_strtolower($correo)])
            ->when($ignorarUsuario, fn ($q) => $q->where('id_usuario', '!=', $ignorarUsuario))
            ->exists()) {
            $correo = "{$base}.{$contador}@pacientes.vidasalud.bo";
            $contador++;
        }

        return $correo;
    }

    /**
     * El rol Paciente se resuelve por nombre (con el seeder actual es id_rol = 4).
     */
    private function idRolPaciente(): int
    {
        $id = Rol::query()
            ->where('nombre', Rol::PACIENTE)
            ->where('estado', true)
            ->value('id_rol');

        if (! $id) {
            throw new RuntimeException('No existe un rol Paciente activo. Ejecute: php artisan db:seed --class=SeguridadSeeder');
        }

        return (int) $id;
    }
}
