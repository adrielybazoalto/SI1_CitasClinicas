<?php

namespace Tests\Feature\Datos;

use Database\Seeders\DatosDemoSeeder;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Fase 0.5 · El seeder de demostración respeta las reglas y es idempotente. */
class DatosDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    private function sembrar(): void
    {
        $this->seed(SeguridadSeeder::class);
        $this->seed(DatosDemoSeeder::class);
    }

    public function test_crea_los_datos_maestros_y_las_citas(): void
    {
        $this->sembrar();

        $this->assertSame(23, DB::table('usuario')->count());
        $this->assertSame(13, DB::table('paciente')->count());
        $this->assertSame(6, DB::table('medico')->count());
        $this->assertSame(13, DB::table('horario')->count());
        $this->assertGreaterThanOrEqual(35, DB::table('cita')->count());
        $this->assertSame(
            DB::table('cita')->where('estado', 'Atendida')->count(),
            DB::table('atencion')->count(),
        );
        $this->assertTrue(Hash::check('cperez@2026', DB::table('usuario')->where('username', 'cperez')->value('password_hash')));
    }

    public function test_es_idempotente(): void
    {
        $this->sembrar();
        $antes = collect(['usuario', 'paciente', 'antecedente', 'medico', 'horario', 'tarifa', 'cita', 'pago', 'indisponibilidad'])
            ->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()]);

        $this->seed(DatosDemoSeeder::class);

        foreach ($antes as $tabla => $total) {
            $this->assertSame($total, DB::table($tabla)->count(), "Se duplicaron filas en {$tabla}");
        }
    }

    public function test_las_citas_respetan_las_reglas(): void
    {
        $this->sembrar();
        $citas = DB::table('cita')->get();

        // Sin superposición por médico (citas no canceladas).
        $vigentes = $citas->where('estado', '<>', 'Cancelada');
        $this->assertSame($vigentes->count(), $vigentes->unique(fn ($c) => $c->id_medico.$c->fecha.$c->hora_inicio)->count());

        // RN19: una cita por paciente, médico y día; máximo de citas activas.
        $this->assertSame($vigentes->count(), $vigentes->unique(fn ($c) => $c->id_paciente.'|'.$c->id_medico.'|'.$c->fecha)->count());
        $activas = $citas->whereIn('estado', ['Programada', 'Confirmada'])->countBy('id_paciente');
        $this->assertLessThanOrEqual(config('clinica.max_citas_activas'), $activas->max());

        // Cada cita está dentro de un turno real del médico.
        foreach ($citas as $c) {
            $dia = \Carbon\Carbon::parse($c->fecha)->dayOfWeekIso;
            $this->assertTrue(DB::table('horario')->where('id_medico', $c->id_medico)->where('id_especialidad', $c->id_especialidad)
                ->where('dia_semana', $dia)->where('hora_inicio', '<=', $c->hora_inicio)->where('hora_fin', '>=', $c->hora_fin)->exists(),
                "La cita {$c->codigo} está fuera del horario");
        }

        // Las citas futuras siguen Programadas y las pasadas tienen estado final.
        $hoy = now()->toDateString();
        $this->assertFalse($citas->where('fecha', '>', $hoy)->contains(fn ($c) => $c->estado !== 'Programada'));
        $this->assertFalse($citas->where('fecha', '<', $hoy)->contains(fn ($c) => in_array($c->estado, ['Programada', 'Confirmada'])));

        // Una sola caja abierta por usuario.
        $this->assertLessThanOrEqual(1, DB::table('caja')->where('estado', 'ABIERTA')->get()->countBy('id_usuario')->max() ?? 0);
    }
}
