<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Services\DisponibilidadService;
use App\Services\HorarioService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Datos de demostración de la Clínica Vida Salud.
 *
 * - Datos maestros (usuarios, pacientes, antecedentes, especialidades, médicos,
 *   horarios, tarifas e indisponibilidades) tomados de 02_poblacion.sql.
 * - Citas generadas con fechas RELATIVAS A HOY, ubicadas en los turnos reales
 *   de cada médico: pasadas (Atendida, Cancelada, Inasistencia), de hoy
 *   (Programada, Confirmada) y futuras (Programada).
 *
 * Idempotente: cada registro maestro se busca por su clave natural (username,
 * CI, código, matrícula, nombre…) y solo se crea si no existe. Las citas solo
 * se generan si la tabla cita está vacía. Nunca borra ni modifica nada.
 *
 * Uso: php artisan db:seed --class=DatosDemoSeeder
 * Clave de cada usuario: <username>@2026 (ver Contrasenas_de_prueba.md).
 */
class DatosDemoSeeder extends Seeder
{
    /** username => [rol, correo, debe_cambiar_clave, intentos_fallidos] */
    private const USUARIOS = [
        'rmendoza' => [Rol::RECEPCIONISTA, 'rosa.mendoza@vidasalud.bo', false, 0],
        'jcuellar' => [Rol::RECEPCIONISTA, 'jorge.cuellar@vidasalud.bo', true, 0],
        'lsuarez' => [Rol::RECEPCIONISTA, 'lucia.suarez@vidasalud.bo', false, 0],
        'cperez' => [Rol::MEDICO, 'carlos.perez@vidasalud.bo', false, 0],
        'avaca' => [Rol::MEDICO, 'ana.vaca@vidasalud.bo', false, 0],
        'lgutierrez' => [Rol::MEDICO, 'luis.gutierrez@vidasalud.bo', false, 0],
        'psaucedo' => [Rol::MEDICO, 'patricia.saucedo@vidasalud.bo', false, 0],
        'ranez' => [Rol::MEDICO, 'roberto.anez@vidasalud.bo', false, 0],
        'drivero' => [Rol::MEDICO, 'daniela.rivero@vidasalud.bo', false, 0],
        'p6543210' => [Rol::PACIENTE, 'maria.lopez@gmail.com', false, 0],
        'p7123456' => [Rol::PACIENTE, 'jc.flores@gmail.com', false, 2],
        'p5987654' => [Rol::PACIENTE, 'carla.rojas@hotmail.com', false, 0],
        'p8234567' => [Rol::PACIENTE, 'miguel.cespedes@gmail.com', false, 0],
        'p6345678' => [Rol::PACIENTE, 'valeria.montano@gmail.com', false, 0],
        'p9456789' => [Rol::PACIENTE, 'fam.arancibia@gmail.com', false, 0],
        'p4567890' => [Rol::PACIENTE, '4567890@sincorreo.vidasalud.bo', false, 0],
        'p7678901' => [Rol::PACIENTE, 'fernando.ortiz@gmail.com', false, 0],
        'p6789012' => [Rol::PACIENTE, 'gabi.justiniano@gmail.com', false, 0],
        'p5890123' => [Rol::PACIENTE, 'andres.melgar@gmail.com', false, 0],
        'p8901234' => [Rol::PACIENTE, 'fam.paz@gmail.com', true, 0],
        'p7012345' => [Rol::PACIENTE, '7012345@sincorreo.vidasalud.bo', false, 0],
        'p9123456' => [Rol::PACIENTE, 'sofia.romero@gmail.com', false, 0],
    ];

    /** codigo => [username, ci, nombres, apellidos, nacimiento, sexo, teléfono, correo, dirección, recordatorios] */
    private const PACIENTES = [
        'PAC-000001' => ['p6543210', '6543210', 'María', 'López Ribera', '1990-03-15', 'F', '76011111', 'maria.lopez@gmail.com', 'Av. Banzer 4to anillo', true],
        'PAC-000002' => ['p7123456', '7123456', 'Juan Carlos', 'Flores Méndez', '1985-07-22', 'M', '76022222', 'jc.flores@gmail.com', 'Barrio Equipetrol, calle 7', true],
        'PAC-000003' => ['p5987654', '5987654', 'Carla', 'Rojas Vargas', '2001-11-02', 'F', '76033333', 'carla.rojas@hotmail.com', 'Plan 3000, UV 160', true],
        'PAC-000004' => ['p8234567', '8234567', 'Miguel Ángel', 'Céspedes Toro', '1978-01-30', 'M', '76044444', 'miguel.cespedes@gmail.com', 'Av. Alemana 3er anillo', true],
        'PAC-000005' => ['p6345678', '6345678', 'Valeria', 'Montaño Salvatierra', '1995-09-12', 'F', '76055555', 'valeria.montano@gmail.com', 'Urb. Las Palmas', true],
        'PAC-000006' => ['p9456789', '9456789', 'Diego', 'Arancibia Soliz', '2015-05-20', 'M', '76066666', 'fam.arancibia@gmail.com', 'Barrio Hamacas', true],
        'PAC-000007' => ['p4567890', '4567890', 'Rosa', 'Chávez Quiroga', '1962-12-05', 'F', '76077777', null, 'Av. Santos Dumont 5to anillo', false],
        'PAC-000008' => ['p7678901', '7678901', 'Fernando', 'Ortiz Lima', '1988-04-18', 'M', '76088888', 'fernando.ortiz@gmail.com', 'Villa 1ro de Mayo', true],
        'PAC-000009' => ['p6789012', '6789012', 'Gabriela', 'Justiniano Roca', '1999-08-25', 'F', '76099999', 'gabi.justiniano@gmail.com', 'Av. Piraí 4to anillo', true],
        'PAC-000010' => ['p5890123', '5890123', 'Andrés', 'Melgar Suárez', '1972-02-14', 'M', '77011111', 'andres.melgar@gmail.com', 'Barrio Sirari', true],
        'PAC-000011' => ['p8901234', '8901234', 'Lucía', 'Paz Aguilera', '2010-10-09', 'F', '77022222', 'fam.paz@gmail.com', 'Urb. Remanso', true],
        'PAC-000012' => ['p7012345', '7012345', 'Jorge', 'Antelo Durán', '1955-06-01', 'M', '77033333', null, 'Av. Roca y Coronado', false],
        'PAC-000013' => ['p9123456', '9123456', 'Sofía', 'Romero Vaca', '2003-04-11', 'F', '77044444', 'sofia.romero@gmail.com', 'Barrio Las Misiones', true],
    ];

    /** codigo paciente => [actualizado_por, sangre, alergias, crónicas, medicamentos, contacto, parentesco, teléfono] */
    private const ANTECEDENTES = [
        'PAC-000001' => ['rmendoza', 'O+', 'Penicilina', null, null, 'Contacto de María', 'Madre', '78000000'],
        'PAC-000002' => ['jcuellar', 'A+', null, 'Hipertensión arterial', 'Losartán 50 mg', 'Contacto de Juan Carlos', 'Esposo(a)', '79001001'],
        'PAC-000003' => ['lsuarez', 'O+', 'Ninguna conocida', null, null, 'Contacto de Carla', 'Hermano(a)', '78002002'],
        'PAC-000004' => ['rmendoza', 'B+', 'AINES', 'Diabetes tipo 2', 'Metformina 850 mg', 'Contacto de Miguel Ángel', 'Hijo(a)', '79003003'],
        'PAC-000005' => ['jcuellar', 'O-', null, null, null, 'Contacto de Valeria', 'Madre', '78004004'],
        'PAC-000006' => ['lsuarez', 'A+', 'Lactosa', null, null, 'Contacto de Diego', 'Madre', '79005005'],
        'PAC-000007' => ['rmendoza', 'O+', 'Sulfas', 'Hipertensión, artrosis', 'Losartán 50 mg', 'Contacto de Rosa', 'Hermano(a)', '78006006'],
        'PAC-000008' => ['jcuellar', 'AB+', null, null, null, 'Contacto de Fernando', 'Hijo(a)', '79007007'],
        'PAC-000009' => ['lsuarez', 'O+', 'Polen', null, null, 'Contacto de Gabriela', 'Madre', '78008008'],
        'PAC-000010' => ['rmendoza', 'A-', null, 'Hipotiroidismo', null, 'Contacto de Andrés', 'Esposo(a)', '79009009'],
        'PAC-000011' => ['jcuellar', 'O+', null, null, null, 'Contacto de Lucía', 'Madre', '78010010'],
        'PAC-000012' => ['lsuarez', 'B-', 'Penicilina', 'Diabetes tipo 2, hipertensión', 'Metformina 850 mg', 'Contacto de Jorge', 'Hijo(a)', '79011011'],
        'PAC-000013' => ['rmendoza', 'A+', null, null, null, 'Contacto de Sofía', 'Madre', '78012012'],
    ];

    /** nombre => [descripción, duración] */
    private const ESPECIALIDADES = [
        'Medicina general' => ['Consulta general de adultos', 20],
        'Pediatría' => ['Atención de niños y adolescentes', 30],
        'Ginecología' => ['Salud de la mujer', 30],
        'Odontología' => ['Salud bucal', 30],
        'Cardiología' => ['Corazón y sistema circulatorio', 30],
        'Dermatología' => ['Enfermedades de la piel', 20],
    ];

    /** codigo => [username, matrícula, nombres, apellidos, teléfono, correo, especialidades] */
    private const MEDICOS = [
        'MED-0001' => ['cperez', 'MP-SC-1045', 'Carlos', 'Pérez Rojas', '70011223', 'carlos.perez@vidasalud.bo', ['Medicina general']],
        'MED-0002' => ['avaca', 'MP-SC-2210', 'Ana María', 'Vaca Justiniano', '70122334', 'ana.vaca@vidasalud.bo', ['Pediatría']],
        'MED-0003' => ['lgutierrez', 'MP-SC-1873', 'Luis Fernando', 'Gutiérrez Antelo', '70233445', 'luis.gutierrez@vidasalud.bo', ['Cardiología', 'Medicina general']],
        'MED-0004' => ['psaucedo', 'MP-SC-3301', 'Patricia', 'Saucedo Arteaga', '70344556', 'patricia.saucedo@vidasalud.bo', ['Ginecología']],
        'MED-0005' => ['ranez', 'MP-SC-2756', 'Roberto', 'Áñez Moreno', '70455667', 'roberto.anez@vidasalud.bo', ['Odontología']],
        'MED-0006' => ['drivero', 'MP-SC-4102', 'Daniela', 'Rivero Paz', '70566778', 'daniela.rivero@vidasalud.bo', ['Dermatología', 'Medicina general']],
    ];

    /** [médico, especialidad, día ISO, inicio, fin, duración, consultorio] */
    private const HORARIOS = [
        ['MED-0001', 'Medicina general', 1, '08:00', '12:00', 20, 'C-01'],
        ['MED-0001', 'Medicina general', 3, '08:00', '12:00', 20, 'C-01'],
        ['MED-0001', 'Medicina general', 5, '14:00', '18:00', 20, 'C-01'],
        ['MED-0002', 'Pediatría', 2, '08:00', '12:00', 30, 'C-02'],
        ['MED-0002', 'Pediatría', 4, '14:00', '18:00', 30, 'C-02'],
        ['MED-0003', 'Cardiología', 1, '14:00', '18:00', 30, 'C-03'],
        ['MED-0003', 'Medicina general', 4, '08:00', '12:00', 20, 'C-03'],
        ['MED-0004', 'Ginecología', 2, '14:00', '18:00', 30, 'C-04'],
        ['MED-0004', 'Ginecología', 5, '08:00', '12:00', 30, 'C-04'],
        ['MED-0005', 'Odontología', 1, '08:00', '12:00', 30, 'C-05'],
        ['MED-0005', 'Odontología', 3, '14:00', '18:00', 30, 'C-05'],
        ['MED-0006', 'Dermatología', 2, '08:00', '12:00', 20, 'C-06'],
        ['MED-0006', 'Medicina general', 6, '08:00', '12:00', 20, 'C-06'],
    ];

    /** [especialidad, monto, desde, hasta] */
    private const TARIFAS = [
        ['Medicina general', 80.0, '2026-01-01', '2026-09-30'],
        ['Medicina general', 100.0, '2026-10-01', null],
        ['Pediatría', 120.0, '2026-01-01', null],
        ['Ginecología', 150.0, '2026-01-01', null],
        ['Odontología', 130.0, '2026-01-01', null],
        ['Cardiología', 180.0, '2025-01-01', '2025-12-31'],
        ['Cardiología', 200.0, '2026-01-01', null],
        ['Dermatología', 140.0, '2026-01-01', null],
    ];

    /** especialidad => [motivos, diagnósticos] */
    private const CLINICA = [
        'Medicina general' => [
            ['Dolor de cabeza', 'Fiebre', 'Control general', 'Dolor de garganta', 'Malestar estomacal'],
            ['Cefalea tensional', 'Faringitis aguda', 'Resfrío común', 'Gastritis aguda', 'Lumbalgia mecánica'],
        ],
        'Pediatría' => [
            ['Control de niño sano', 'Fiebre', 'Tos persistente'],
            ['Niño sano, desarrollo adecuado', 'Resfrío común', 'Bronquitis aguda'],
        ],
        'Ginecología' => [
            ['Control prenatal', 'Control anual', 'Dolor pélvico'],
            ['Embarazo de 12 semanas, control normal', 'Control ginecológico normal', 'Dismenorrea primaria'],
        ],
        'Odontología' => [
            ['Dolor de muela', 'Limpieza dental', 'Control odontológico'],
            ['Caries en pieza 36', 'Profilaxis dental', 'Gingivitis leve'],
        ],
        'Cardiología' => [
            ['Chequeo cardiológico', 'Palpitaciones', 'Control de presión'],
            ['Hipertensión arterial controlada', 'Arritmia sinusal benigna', 'Control cardiológico normal'],
        ],
        'Dermatología' => [
            ['Manchas en la piel', 'Acné', 'Picazón'],
            ['Dermatitis de contacto', 'Acné vulgar moderado', 'Urticaria aguda'],
        ],
    ];

    private const TRATAMIENTOS = [
        'Paracetamol 500 mg cada 8 horas por 3 días',
        'Reposo e hidratación; control en 15 días',
        'Tratamiento tópico por 7 días; control en 2 semanas',
        'Continuar medicación habitual; control en 1 mes',
    ];

    private const MOTIVOS_CANCELACION = [
        'Viaje imprevisto', 'Indisponibilidad del paciente', 'Pago vencido', 'Mejoró de salud', 'Cambio de médico',
    ];

    /** @var array<string, int> username => id_usuario */
    private array $usuarios = [];

    /** @var array<string, int> */
    private array $especialidades = [];

    /** @var array<string, int> */
    private array $medicos = [];

    /** @var array<string, int> */
    private array $pacientes = [];

    public function __construct(
        private DisponibilidadService $disponibilidad,
        private HorarioService $horarioService,
    ) {}

    public function run(): void
    {
        mt_srand(2026); // Datos reproducibles.

        DB::transaction(function () {
            $this->sembrarUsuarios();
            $this->sembrarPacientes();
            $this->sembrarOfertaMedica();
        });

        if (DB::table('cita')->exists()) {
            $this->command?->warn('La tabla cita ya tiene datos: no se generan citas de demostración.');

            return;
        }

        DB::transaction(fn () => $this->sembrarCitas());
    }

    /* ------------------------------------------------------------------
     | Datos maestros
     * ------------------------------------------------------------------ */

    private function sembrarUsuarios(): void
    {
        $roles = DB::table('rol')->pluck('id_rol', 'nombre');

        foreach (self::USUARIOS as $username => [$rol, $correo, $cambiar, $intentos]) {
            $this->usuarios[$username] = $this->obtenerOCrear('usuario', 'id_usuario', ['username' => $username], [
                'id_rol' => $roles[$rol],
                'correo' => $correo,
                'password_hash' => Hash::make("{$username}@2026"),
                'intentos_fallidos' => $intentos,
                'debe_cambiar_clave' => $cambiar,
                'estado' => true,
                'creado_en' => '2026-09-01 08:00:00',
            ]);
        }

        $this->usuarios['admin'] = (int) DB::table('usuario')->where('username', 'admin')->value('id_usuario');
    }

    private function sembrarPacientes(): void
    {
        $n = 0;
        foreach (self::PACIENTES as $codigo => [$username, $ci, $nombres, $apellidos, $nac, $sexo, $tel, $correo, $dir, $rec]) {
            $n++;
            $this->pacientes[$codigo] = $this->obtenerOCrear('paciente', 'id_paciente', ['ci' => $ci], [
                'id_usuario' => $this->usuarios[$username],
                'codigo' => $codigo,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'fecha_nacimiento' => $nac,
                'sexo' => $sexo,
                'telefono' => $tel,
                'correo' => $correo,
                'direccion' => $dir,
                'recibe_recordatorios' => $rec,
                'estado' => true,
                'creado_en' => sprintf('2026-09-%02d 09:00:00', $n + 1),
            ]);
        }

        $n = 0;
        foreach (self::ANTECEDENTES as $codigo => [$por, $sangre, $alergias, $cronicas, $medicamentos, $contacto, $parentesco, $telefono]) {
            $n++;
            $idPaciente = $this->pacientes[$codigo];
            if (DB::table('antecedente')->where('id_paciente', $idPaciente)->exists()) {
                continue;
            }
            DB::table('antecedente')->insert([
                'id_paciente' => $idPaciente,
                'actualizado_por' => $this->usuarios[$por],
                'tipo_sangre' => $sangre,
                'alergias' => $alergias,
                'enfermedades_cronicas' => $cronicas,
                'medicamentos' => $medicamentos,
                'antecedentes' => null,
                'contacto_nombre' => $contacto,
                'contacto_parentesco' => $parentesco,
                'contacto_telefono' => $telefono,
                'actualizado_en' => sprintf('2026-09-%02d 09:20:00', $n + 1),
            ]);
        }
    }

    private function sembrarOfertaMedica(): void
    {
        foreach (self::ESPECIALIDADES as $nombre => [$descripcion, $duracion]) {
            $existente = DB::table('especialidad')->whereRaw('lower(trim(nombre)) = ?', [mb_strtolower($nombre)])->value('id_especialidad');
            $this->especialidades[$nombre] = (int) ($existente ?? DB::table('especialidad')->insertGetId([
                'nombre' => $nombre, 'descripcion' => $descripcion, 'duracion_min' => $duracion, 'estado' => true,
            ], 'id_especialidad'));
        }

        foreach (self::MEDICOS as $codigo => [$username, $matricula, $nombres, $apellidos, $tel, $correo, $esps]) {
            $id = $this->obtenerOCrear('medico', 'id_medico', ['matricula' => $matricula], [
                'id_usuario' => $this->usuarios[$username],
                'codigo' => $codigo,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'telefono' => $tel,
                'correo' => $correo,
                'estado' => true,
            ]);
            $this->medicos[$codigo] = $id;

            foreach ($esps as $esp) {
                DB::table('medico_especialidad')->insertOrIgnore([
                    'id_medico' => $id, 'id_especialidad' => $this->especialidades[$esp],
                ]);
            }
        }

        foreach (self::HORARIOS as [$medico, $esp, $dia, $inicio, $fin, $duracion, $consultorio]) {
            $this->obtenerOCrear('horario', 'id_horario', [
                'id_medico' => $this->medicos[$medico],
                'id_especialidad' => $this->especialidades[$esp],
                'dia_semana' => $dia,
                'hora_inicio' => $inicio.':00',
            ], ['hora_fin' => $fin.':00', 'duracion_min' => $duracion, 'consultorio' => $consultorio, 'estado' => true]);
        }

        foreach (self::TARIFAS as [$esp, $monto, $desde, $hasta]) {
            $this->obtenerOCrear('tarifa', 'id_tarifa', [
                'id_especialidad' => $this->especialidades[$esp],
                'vigente_desde' => $desde,
            ], ['monto' => $monto, 'vigente_hasta' => $hasta]);
        }

        // Indisponibilidades relativas a hoy: una pasada, una próxima (para CU10) y unas vacaciones.
        $hoy = CarbonImmutable::today();
        $indisponibilidades = [
            ['MED-0005', 'rmendoza', $this->proximoDia($hoy->subDays(10), 3, false)->setTime(14, 0), 3, 'Cirugía de emergencia'],
            ['MED-0003', 'admin', $this->proximoDia($hoy->addDays(15), 1)->setTime(0, 0), 24 * 5 - 1, 'Congreso de cardiología'],
            ['MED-0002', 'admin', CarbonImmutable::create($hoy->year, 12, 21), 24 * 14 - 1, 'Vacaciones'],
        ];
        foreach ($indisponibilidades as [$medico, $por, $inicio, $horas, $motivo]) {
            $existe = DB::table('indisponibilidad')
                ->where('id_medico', $this->medicos[$medico])->where('motivo', $motivo)->exists();
            if (! $existe) {
                DB::table('indisponibilidad')->insert([
                    'id_medico' => $this->medicos[$medico],
                    'registrado_por' => $this->usuarios[$por] ?: null,
                    'inicio' => $inicio->toDateTimeString(),
                    'fin' => $inicio->addHours($horas)->addMinutes(59)->toDateTimeString(),
                    'motivo' => $motivo,
                ]);
            }
        }
    }

    /* ------------------------------------------------------------------
     | Citas, atenciones, pagos y notificaciones
     * ------------------------------------------------------------------ */

    private function sembrarCitas(): void
    {
        $ahora = CarbonImmutable::now();
        $hoy = CarbonImmutable::today();

        // 1. Fichas candidatas de cada día (turnos reales del médico).
        $pasadas = $this->fichasEntre($hoy->subDays(30), $hoy->subDay(), fn ($f) => true);
        $deHoy = $this->fichasEntre($hoy, $hoy, fn ($f) => true);
        // RN17: en línea solo se reserva dentro de la ventana; más allá, solo fichas de mostrador.
        $ventana = $hoy->addDays((int) config('clinica.reserva_abre_dias_antes', 7));
        $futuras = $this->fichasEntre($hoy->addDay(), $hoy->addDays(14),
            fn ($f) => $f['canal'] === 'MOSTRADOR' || $f['inicio']->lt($ventana));

        // 2. Plan de estados (cantidades del plan de trabajo).
        $plan = [];
        $estadosPasados = array_merge(array_fill(0, 15, 'Atendida'), array_fill(0, 5, 'Cancelada'), array_fill(0, 4, 'Inasistencia'));
        shuffle($estadosPasados);
        foreach ($this->elegir($pasadas, count($estadosPasados)) as $i => $ficha) {
            $plan[] = [$ficha, $estadosPasados[$i]];
        }

        // Hoy: 2 Confirmada (cualquier hora) y 3 Programada (aún no empiezan).
        $yaLlegaron = array_values(array_filter($deHoy, fn ($f) => $f['inicio']->subMinutes(15)->lte($ahora)));
        $confirmadas = $this->elegir($yaLlegaron ?: $deHoy, 2);
        foreach ($confirmadas as $ficha) {
            $plan[] = [$ficha, 'Confirmada'];
        }
        $porVenir = array_values(array_filter($deHoy, fn ($f) => $f['inicio']->gt($ahora->addMinutes(30)) && ! in_array($f, $confirmadas, true)));
        $programadasHoy = $this->elegir($porVenir, 3);
        foreach ($programadasHoy as $ficha) {
            $plan[] = [$ficha, 'Programada'];
        }
        // Si hoy no hay turnos suficientes (fin de semana o tarde), se completan con días futuros.
        $faltan = 15 + (3 - count($programadasHoy));
        foreach ($this->elegir($futuras, $faltan) as $ficha) {
            $plan[] = [$ficha, 'Programada'];
        }

        // 3. Orden cronológico de creación → códigos correlativos.
        usort($plan, fn ($a, $b) => $a[0]['inicio'] <=> $b[0]['inicio']);

        $ocupado = [];   // "medico|inicio" (sin superposición)
        $pacDia = [];    // "paciente|fecha" → [inicio, fin] para no cruzar horas
        $pacMedDia = []; // RN19: una cita por paciente, médico y día
        $activas = [];   // RN19: máx. citas activas por paciente
        $maxActivas = (int) config('clinica.max_citas_activas', 3);
        $numero = 0;
        $cajas = [];
        $comprobante = 1000;

        foreach ($plan as [$ficha, $estado]) {
            $clave = $ficha['id_medico'].'|'.$ficha['inicio'];
            if (isset($ocupado[$clave])) {
                continue;
            }

            $activa = in_array($estado, ['Programada', 'Confirmada'], true);
            $codigoPaciente = $this->elegirPaciente($ficha, $activa, $pacDia, $pacMedDia, $activas, $maxActivas);
            if ($codigoPaciente === null) {
                continue;
            }

            $ocupado[$clave] = true;
            $idPaciente = $this->pacientes[$codigoPaciente];
            $fecha = $ficha['inicio']->toDateString();
            $pacDia[$idPaciente.'|'.$fecha][] = [$ficha['inicio'], $ficha['fin']];
            $pacMedDia[$idPaciente.'|'.$ficha['id_medico'].'|'.$fecha] = true;
            if ($activa) {
                $activas[$idPaciente] = ($activas[$idPaciente] ?? 0) + 1;
            }

            $numero++;
            $this->crearCita($numero, $ficha, $estado, $codigoPaciente, $ahora, $cajas, $comprobante);
        }

        $this->cerrarCajas($cajas);

        $this->command?->info("Citas de demostración creadas: {$numero}.");
    }

    /**
     * Fichas de todos los turnos entre dos fechas, usando el cálculo de
     * turnos de DisponibilidadService y la capacidad de HorarioService.
     *
     * @return list<array<string, mixed>>
     */
    private function fichasEntre(CarbonImmutable $desde, CarbonImmutable $hasta, callable $filtro): array
    {
        $bloqueos = DB::table('indisponibilidad')->get(['id_medico', 'inicio', 'fin']);
        $pares = DB::table('medico_especialidad')->get();
        $fichas = [];

        for ($dia = $desde; $dia->lte($hasta); $dia = $dia->addDay()) {
            foreach ($pares as $par) {
                $turnos = $this->disponibilidad->turnosDelDia((int) $par->id_medico, (int) $par->id_especialidad, $dia);
                foreach ($turnos as $turno) {
                    $calculo = $this->horarioService->capacidad($turno->hora_inicio, $turno->hora_fin, (int) $turno->duracion_min);
                    [$h, $m] = array_map('intval', explode(':', $turno->hora_inicio));
                    $inicioTurno = $dia->setTime($h, $m);

                    for ($nro = 1; $nro <= $calculo['capacidad']; $nro++) {
                        $inicio = $inicioTurno->addMinutes(($nro - 1) * $turno->duracion_min);
                        $fin = $inicio->addMinutes($turno->duracion_min);

                        $bloqueada = $bloqueos->contains(fn ($b) => (int) $b->id_medico === (int) $par->id_medico
                            && CarbonImmutable::parse($b->inicio)->lt($fin) && $inicio->lt(CarbonImmutable::parse($b->fin)));
                        if ($bloqueada) {
                            continue;
                        }

                        $ficha = [
                            'id_medico' => (int) $par->id_medico,
                            'id_especialidad' => (int) $par->id_especialidad,
                            'nro' => $nro,
                            'canal' => $nro <= $calculo['cupos_linea'] ? 'LINEA' : 'MOSTRADOR',
                            'inicio' => $inicio,
                            'fin' => $fin,
                        ];
                        if ($filtro($ficha)) {
                            $fichas[] = $ficha;
                        }
                    }
                }
            }
        }

        return $fichas;
    }

    /** Elige $n fichas al azar (reproducible), repartidas entre días. */
    private function elegir(array $fichas, int $n): array
    {
        if ($fichas === [] || $n <= 0) {
            return [];
        }
        $indices = array_keys($fichas);
        shuffle($indices);

        return array_map(fn ($i) => $fichas[$i], array_slice($indices, 0, min($n, count($indices))));
    }

    private function elegirPaciente(array $ficha, bool $activa, array $pacDia, array $pacMedDia, array $activas, int $maxActivas): ?string
    {
        $especialidad = array_search($ficha['id_especialidad'], $this->especialidades, true);
        $fecha = $ficha['inicio']->toDateString();
        $codigos = array_keys(self::PACIENTES);
        shuffle($codigos);

        foreach ($codigos as $codigo) {
            [, , , , $nac, $sexo] = self::PACIENTES[$codigo];
            $edad = CarbonImmutable::parse($nac)->age;
            $id = $this->pacientes[$codigo];

            $compatible = match ($especialidad) {
                'Pediatría' => $edad < 18,
                'Ginecología' => $sexo === 'F' && $edad >= 18,
                'Cardiología' => $edad >= 18,
                default => true,
            };
            if (! $compatible
                || isset($pacMedDia[$id.'|'.$ficha['id_medico'].'|'.$fecha])
                || ($activa && ($activas[$id] ?? 0) >= $maxActivas)) {
                continue;
            }
            $cruza = collect($pacDia[$id.'|'.$fecha] ?? [])->contains(fn ($r) => $r[0]->lt($ficha['fin']) && $ficha['inicio']->lt($r[1]));
            if (! $cruza) {
                return $codigo;
            }
        }

        return null;
    }

    private function crearCita(int $numero, array $ficha, string $estado, string $codigoPaciente, CarbonImmutable $ahora, array &$cajas, int &$comprobante): void
    {
        $especialidad = array_search($ficha['id_especialidad'], $this->especialidades, true);
        [$motivos, $diagnosticos] = self::CLINICA[$especialidad];
        $inicio = $ficha['inicio'];
        $canal = $ficha['canal'];
        $usernamePaciente = self::PACIENTES[$codigoPaciente][0];
        $recepcionistas = ['rmendoza', 'jcuellar', 'lsuarez'];
        $recepcion = $this->usuarios[$recepcionistas[array_rand($recepcionistas)]];
        $idUsuarioPaciente = $this->usuarios[$usernamePaciente];
        $idUsuarioMedico = (int) DB::table('medico')->where('id_medico', $ficha['id_medico'])->value('id_usuario');
        $creador = $canal === 'LINEA' ? $idUsuarioPaciente : $recepcion;

        // Se reservó entre 1 y 5 días antes (sin pasar de "ahora").
        $creado = $inicio->subDays(mt_rand(1, 5))->setTime(9, mt_rand(0, 59));
        if ($creado->gt($ahora)) {
            $creado = $ahora->subHours(2);
        }

        $monto = (float) DB::table('tarifa')
            ->where('id_especialidad', $ficha['id_especialidad'])
            ->where('vigente_desde', '<=', $creado->toDateString())
            ->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $creado->toDateString()))
            ->orderByDesc('vigente_desde')
            ->value('monto');

        // Pago: en línea se paga al reservar (RN20); en mostrador, algunas futuras quedan por cobrar (CU19).
        $pagada = $estado !== 'Programada' || $canal === 'LINEA' || $numero % 2 === 0;
        $motivoCancelacion = null;
        $cancelada = null;
        $reembolso = false;
        if ($estado === 'Cancelada') {
            if ($canal === 'LINEA' && $numero % 3 === 0) {
                // RN20: la reserva en línea no se pagó a tiempo y el temporizador la canceló.
                $motivoCancelacion = 'Pago vencido';
                $pagada = false;
                $cancelada = $creado->addMinutes((int) config('clinica.retencion_pago_min', 15) + 1);
            } else {
                $motivoCancelacion = self::MOTIVOS_CANCELACION[mt_rand(0, count(self::MOTIVOS_CANCELACION) - 1)];
                $cancelada = $creado->addHours(mt_rand(2, 20));
                // RN26: reembolso total solo si se cancela con la anticipación configurada.
                $reembolso = $cancelada->diffInHours($inicio, false) >= (int) config('clinica.cancelacion_reembolso_horas', 24);
            }
        }
        $estadoPago = match (true) {
            $reembolso => 'Reembolsado',
            $pagada => 'Pagado',
            default => 'Pendiente',
        };

        $llegada = in_array($estado, ['Confirmada', 'Atendida'], true)
            ? $inicio->subMinutes(mt_rand(5, 15))
            : null;

        $idCita = DB::table('cita')->insertGetId([
            'id_paciente' => $this->pacientes[$codigoPaciente],
            'id_medico' => $ficha['id_medico'],
            'id_especialidad' => $ficha['id_especialidad'],
            'creado_por' => $creador,
            'codigo' => sprintf('CM-%06d', $numero),
            'fecha' => $inicio->toDateString(),
            'hora_inicio' => $inicio->format('H:i:s'),
            'hora_fin' => $ficha['fin']->format('H:i:s'),
            'nro_cita' => $ficha['nro'],
            'sobrecupo' => false,
            'canal' => $canal,
            'motivo' => $motivos[array_rand($motivos)],
            'estado' => $estado,
            'estado_pago' => $estadoPago,
            'monto' => $monto,
            'vence_pago_en' => $canal === 'LINEA' ? $creado->addMinutes((int) config('clinica.retencion_pago_min', 15))->toDateTimeString() : null,
            'veces_reprogramada' => 0,
            'motivo_cancelacion' => $motivoCancelacion,
            'hora_llegada' => $llegada?->toDateTimeString(),
            'creado_en' => $creado->toDateTimeString(),
        ], 'id_cita');

        // Historial de estados.
        $historial = [[null, 'Programada', sprintf('Reserva %s · ficha %d', mb_strtolower($canal), $ficha['nro']), $creador, $creado]];
        if ($llegada) {
            $historial[] = ['Programada', 'Confirmada', 'Llegada registrada en recepción', $recepcion, $llegada];
        }
        if ($estado === 'Atendida') {
            $atendida = $inicio->addMinutes(15);
            $historial[] = ['Confirmada', 'Atendida', 'Atención registrada por el médico', $idUsuarioMedico, $atendida];
            $this->crearAtencion($idCita, $diagnosticos, $codigoPaciente, $atendida);
        } elseif ($estado === 'Inasistencia') {
            $historial[] = ['Programada', 'Inasistencia', 'Marcada por el temporizador', null, $inicio->addMinutes((int) config('clinica.inasistencia_min_despues', 15))];
        } elseif ($estado === 'Cancelada') {
            $actor = $motivoCancelacion === 'Pago vencido' ? null : ($canal === 'LINEA' ? $idUsuarioPaciente : $recepcion);
            $historial[] = ['Programada', 'Cancelada', $motivoCancelacion, $actor, $cancelada];
        }
        foreach ($historial as [$anterior, $nuevo, $detalle, $usuario, $cuando]) {
            DB::table('historial_estado_cita')->insert([
                'id_cita' => $idCita, 'id_usuario' => $usuario, 'estado_anterior' => $anterior,
                'estado_nuevo' => $nuevo, 'detalle' => $detalle, 'fecha_hora' => $cuando->toDateTimeString(),
            ]);
        }

        // Pago, reembolso y comprobante.
        if ($pagada) {
            $cobrado = $canal === 'LINEA' ? $creado->addMinutes(mt_rand(2, 10)) : $creado->addMinutes(5);
            $idPago = $this->registrarPago($idCita, $canal, 'COBRO', $monto, $cobrado, $recepcion, $cajas, $numero);
            $comprobante++;
            DB::table('comprobante')->insert(['id_pago' => $idPago, 'numero' => $comprobante, 'fecha_emision' => $cobrado->toDateTimeString()]);

            if ($reembolso) {
                $this->registrarPago($idCita, $canal, 'REEMBOLSO', $monto, $cancelada, $recepcion, $cajas, $numero);
            }
        }

        // Notificaciones: recordatorio −24 h (si la cita seguía vigente) y aviso de cancelación.
        $recibe = self::PACIENTES[$codigoPaciente][9];
        if ($recibe) {
            $recordatorio = $inicio->subHours((int) config('clinica.recordatorio_horas_antes', 24))->max($creado);
            if (! $cancelada || $cancelada->gt($recordatorio)) {
                $enviado = $recordatorio->lte($ahora);
                DB::table('notificacion')->insert([
                    'id_paciente' => $this->pacientes[$codigoPaciente], 'id_cita' => $idCita, 'tipo' => 'RECORDATORIO',
                    'programada_para' => $recordatorio->toDateTimeString(),
                    'enviada_en' => $enviado ? $recordatorio->toDateTimeString() : null,
                    'estado' => $enviado ? 'ENVIADA' : 'PENDIENTE',
                ]);
            }
            if ($cancelada) {
                DB::table('notificacion')->insert([
                    'id_paciente' => $this->pacientes[$codigoPaciente], 'id_cita' => $idCita, 'tipo' => 'CANCELACION',
                    'programada_para' => $cancelada->toDateTimeString(),
                    'enviada_en' => $cancelada->addMinute()->toDateTimeString(), 'estado' => 'ENVIADA',
                ]);
            }
        }

        DB::table('bitacora')->insert([
            'id_usuario' => $creador, 'fecha_hora' => $creado->toDateTimeString(),
            'ip' => $canal === 'LINEA' ? '190.181.6.'.mt_rand(10, 99) : '192.168.1.'.mt_rand(20, 29),
            'accion' => 'CITA_PROGRAMADA', 'tabla' => 'cita', 'id_registro' => $idCita,
            'detalle' => sprintf('Cita CM-%06d · %s %s · ficha %d', $numero, $inicio->toDateString(), $inicio->format('H:i'), $ficha['nro']),
        ]);
    }

    private function crearAtencion(int $idCita, array $diagnosticos, string $codigoPaciente, CarbonImmutable $cuando): void
    {
        $edad = CarbonImmutable::parse(self::PACIENTES[$codigoPaciente][4])->age;
        $nino = $edad < 14;

        DB::table('atencion')->insert([
            'id_cita' => $idCita,
            'diagnostico' => $diagnosticos[array_rand($diagnosticos)],
            'tratamiento' => self::TRATAMIENTOS[array_rand(self::TRATAMIENTOS)],
            'peso_kg' => $nino ? mt_rand(250, 400) / 10 : mt_rand(550, 900) / 10,
            'talla_cm' => $nino ? mt_rand(1250, 1450) / 10 : mt_rand(1550, 1850) / 10,
            'presion_arterial' => $nino ? null : sprintf('%d/%d', [110, 120, 130][mt_rand(0, 2)], [70, 80, 90][mt_rand(0, 2)]),
            'temperatura_c' => mt_rand(362, 378) / 10,
            'observaciones' => null,
            'registrado_en' => $cuando->toDateTimeString(),
        ]);
    }

    /** Registra un cobro o reembolso; los de mostrador van a la caja del día del recepcionista. */
    private function registrarPago(int $idCita, string $canal, string $tipo, float $monto, CarbonImmutable $cuando, int $recepcion, array &$cajas, int $numero): int
    {
        $enLinea = $canal === 'LINEA';
        $metodo = $enLinea ? (['PAYPAL', 'QR'][$numero % 2]) : (['EFECTIVO', 'TARJETA', 'TRANSFERENCIA'][$numero % 3]);
        $idCaja = null;

        if (! $enLinea) {
            $clave = $recepcion.'|'.$cuando->toDateString();
            if (! isset($cajas[$clave])) {
                // ux_caja_abierta: solo la caja de hoy queda ABIERTA; las de días pasados nacen cerradas.
                $esHoy = $cuando->isToday();
                $cajas[$clave] = [
                    'id' => DB::table('caja')->insertGetId([
                        'id_usuario' => $recepcion,
                        'apertura' => $cuando->setTime(7, 45)->toDateTimeString(),
                        'cierre' => $esHoy ? null : $cuando->setTime(18, 10)->toDateTimeString(),
                        'monto_inicial' => 200,
                        'estado' => $esHoy ? 'ABIERTA' : 'CERRADA',
                    ], 'id_caja'),
                    'dia' => $cuando->startOfDay(),
                    'efectivo' => 0.0,
                ];
            }
            $idCaja = $cajas[$clave]['id'];
            if ($metodo === 'EFECTIVO') {
                $cajas[$clave]['efectivo'] += $tipo === 'COBRO' ? $monto : -$monto;
            }
        }

        return DB::table('pago')->insertGetId([
            'id_cita' => $idCita,
            'id_caja' => $idCaja,
            'registrado_por' => $enLinea && $tipo === 'COBRO' ? null : $recepcion,
            'tipo' => $tipo,
            'metodo' => $metodo,
            'monto' => $monto,
            'id_transaccion' => $enLinea ? sprintf('%s-%04d-%06d', $tipo === 'COBRO' ? 'TX' : 'RF', $numero, mt_rand(100000, 999999)) : null,
            'estado' => 'APROBADO',
            'fecha_hora' => $cuando->toDateTimeString(),
        ], 'id_pago');
    }

    /** Cierra las cajas de días anteriores con arqueo cuadrado; la de hoy queda abierta. */
    private function cerrarCajas(array $cajas): void
    {
        $hoy = CarbonImmutable::today();
        foreach ($cajas as $caja) {
            if ($caja['dia']->gte($hoy)) {
                continue;
            }
            $esperado = 200 + $caja['efectivo'];
            DB::table('caja')->where('id_caja', $caja['id'])->update([
                'monto_esperado' => $esperado,
                'monto_contado' => $esperado,
                'diferencia' => 0,
            ]);
        }
    }

    /* ------------------------------------------------------------------
     | Utilidades
     * ------------------------------------------------------------------ */

    /** Devuelve el id de la fila que coincide con $claves o la crea con $valores. */
    private function obtenerOCrear(string $tabla, string $pk, array $claves, array $valores): int
    {
        $id = DB::table($tabla)->where($claves)->value($pk);

        return (int) ($id ?? DB::table($tabla)->insertGetId(array_merge($claves, $valores), $pk));
    }

    /** Primer día con el día ISO dado desde $desde (hacia adelante o atrás). */
    private function proximoDia(CarbonImmutable $desde, int $diaIso, bool $adelante = true): CarbonImmutable
    {
        $dia = $desde;
        while ($dia->dayOfWeekIso !== $diaIso) {
            $dia = $adelante ? $dia->addDay() : $dia->subDay();
        }

        return $dia;
    }
}
