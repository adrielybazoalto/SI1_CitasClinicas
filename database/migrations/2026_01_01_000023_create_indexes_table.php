<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX ix_paciente_apellidos ON paciente (apellidos, nombres)');
        DB::statement('CREATE INDEX ix_cita_medico_fecha ON cita (id_medico, fecha)');
        DB::statement('CREATE INDEX ix_cita_paciente ON cita (id_paciente)');
        DB::statement('CREATE INDEX ix_cita_estado ON cita (estado)');
        DB::statement('CREATE INDEX ix_bitacora_fecha ON bitacora (fecha_hora)');
        DB::statement('CREATE INDEX ix_recuperacion_usuario ON recuperacion_clave (id_usuario)');
        DB::statement("CREATE INDEX ix_notificacion_pend ON notificacion (programada_para) WHERE estado = 'PENDIENTE'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ix_paciente_apellidos');
        DB::statement('DROP INDEX IF EXISTS ix_cita_medico_fecha');
        DB::statement('DROP INDEX IF EXISTS ix_cita_paciente');
        DB::statement('DROP INDEX IF EXISTS ix_cita_estado');
        DB::statement('DROP INDEX IF EXISTS ix_bitacora_fecha');
        DB::statement('DROP INDEX IF EXISTS ix_recuperacion_usuario');
        DB::statement('DROP INDEX IF EXISTS ix_notificacion_pend');
    }
};
