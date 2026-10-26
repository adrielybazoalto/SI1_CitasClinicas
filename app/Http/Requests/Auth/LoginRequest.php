<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * CU01 · Iniciar sesión con username o correo sobre la tabla usuario.
 *
 * Control de intentos: cada fallo suma 1 a intentos_fallidos; al llegar a
 * config('clinica.max_intentos_login') la cuenta queda bloqueada
 * config('clinica.bloqueo_login_min') minutos en bloqueado_hasta.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'login' => 'usuario o correo',
            'password' => 'contraseña',
        ];
    }

    /**
     * Valida las credenciales y, si son correctas, inicia la sesión.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $bitacora = app(BitacoraService::class);
        $login = mb_strtolower(trim((string) $this->input('login')));

        $usuario = User::query()
            ->whereRaw('lower(username) = ?', [$login])
            ->orWhereRaw('lower(correo) = ?', [$login])
            ->first();

        if (! $usuario) {
            $bitacora->registrar('LOGIN_FALLIDO', 'usuario', null, "Usuario inexistente: {$login}");
            $this->fallar('Usuario o contraseña incorrectos.');
        }

        // Cuenta bloqueada: no se verifica la contraseña hasta que venza el bloqueo.
        if ($usuario->estaBloqueado()) {
            $bitacora->registrar('LOGIN_FALLIDO', 'usuario', $usuario->id_usuario, 'Intento con cuenta bloqueada', $usuario->id_usuario);
            $this->fallar($this->mensajeBloqueo($usuario));
        }

        if (! Hash::check((string) $this->input('password'), $usuario->password_hash)) {
            $usuario = $this->registrarIntentoFallido($usuario);
            $bitacora->registrar(
                'LOGIN_FALLIDO', 'usuario', $usuario->id_usuario,
                $usuario->estaBloqueado()
                    ? 'Contraseña incorrecta. Cuenta bloqueada hasta '.$usuario->bloqueado_hasta->format('H:i')
                    : "Contraseña incorrecta (intento {$usuario->intentos_fallidos})",
                $usuario->id_usuario,
            );
            $this->fallar($usuario->estaBloqueado()
                ? $this->mensajeBloqueo($usuario)
                : 'Usuario o contraseña incorrectos.');
        }

        // La contraseña es correcta, pero la cuenta o su rol están desactivados.
        if (! $usuario->estado || ! $usuario->rol?->estado) {
            $bitacora->registrar('LOGIN_FALLIDO', 'usuario', $usuario->id_usuario, 'Cuenta o rol inactivo', $usuario->id_usuario);
            $this->fallar('La cuenta está desactivada. Comuníquese con el administrador.');
        }

        // Éxito: se reinicia el contador y se abre la sesión (sin "recordarme").
        $usuario->forceFill(['intentos_fallidos' => 0, 'bloqueado_hasta' => null])->save();
        Auth::login($usuario);

        $bitacora->registrar('LOGIN', 'usuario', $usuario->id_usuario, 'Inicio de sesión');
    }

    /**
     * Suma un intento fallido con bloqueo de fila para que dos intentos
     * simultáneos no se pisen. Al llegar al máximo bloquea la cuenta y
     * reinicia el contador para el siguiente ciclo.
     */
    private function registrarIntentoFallido(User $usuario): User
    {
        return DB::transaction(function () use ($usuario) {
            $usuario = User::whereKey($usuario->id_usuario)->lockForUpdate()->first();
            $intentos = $usuario->intentos_fallidos + 1;

            if ($intentos >= config('clinica.max_intentos_login')) {
                $usuario->forceFill([
                    'intentos_fallidos' => 0,
                    'bloqueado_hasta' => now()->addMinutes(config('clinica.bloqueo_login_min')),
                ])->save();
                $usuario->intentos_fallidos = $intentos; // para el detalle de bitácora
            } else {
                $usuario->forceFill(['intentos_fallidos' => $intentos])->save();
            }

            return $usuario;
        });
    }

    private function mensajeBloqueo(User $usuario): string
    {
        $minutos = max(1, (int) ceil(now()->diffInSeconds($usuario->bloqueado_hasta) / 60));

        return "Cuenta bloqueada por demasiados intentos fallidos. Intente nuevamente en {$minutos} minuto(s).";
    }

    /**
     * @throws ValidationException
     */
    private function fallar(string $mensaje): never
    {
        throw ValidationException::withMessages(['login' => $mensaje]);
    }
}
