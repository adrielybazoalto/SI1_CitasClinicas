<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cita', function (Blueprint $table) {
            $table->id('id_cita');
            $table->integer('id_paciente');
            $table->integer('id_medico');
            $table->integer('id_especialidad');
            $table->integer('creado_por');
            $table->string('codigo', 12)->unique();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->smallInteger('nro_cita');
            $table->boolean('sobrecupo')->default(false);
            $table->string('canal', 10);
            $table->string('motivo', 200)->nullable();
            $table->string('estado', 12)->default('Programada');
            $table->string('estado_pago', 12)->default('Pendiente');
            $table->decimal('monto', 10, 2)->nullable();
            $table->timestamp('vence_pago_en')->nullable();
            $table->smallInteger('veces_reprogramada')->default(0);
            $table->string('motivo_cancelacion', 200)->nullable();
            $table->timestamp('hora_llegada')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->foreign('id_paciente')->references('id_paciente')->on('paciente')->onDelete('restrict');
            $table->foreign('id_medico')->references('id_medico')->on('medico')->onDelete('restrict');
            $table->foreign('id_especialidad')->references('id_especialidad')->on('especialidad')->onDelete('restrict');
            $table->foreign('creado_por')->references('id_usuario')->on('usuario')->onDelete('restrict');
        });
        DB::statement('ALTER TABLE cita ADD CONSTRAINT chk_cita_nro_cita CHECK (nro_cita > 0)');
        DB::statement('ALTER TABLE cita ADD CONSTRAINT chk_cita_canal CHECK (canal IN (\'LINEA\',\'MOSTRADOR\'))');
        DB::statement("ALTER TABLE cita ADD CONSTRAINT chk_cita_estado CHECK (estado IN ('Programada','Confirmada','Atendida','Cancelada','Inasistencia'))");
        DB::statement("ALTER TABLE cita ADD CONSTRAINT chk_cita_estado_pago CHECK (estado_pago IN ('Pendiente','Pagado','Reembolsado'))");
        DB::statement('ALTER TABLE cita ADD CONSTRAINT chk_cita_monto CHECK (monto IS NULL OR monto >= 0)');
        DB::statement('ALTER TABLE cita ADD CONSTRAINT chk_cita_veces_reprog CHECK (veces_reprogramada >= 0)');
        DB::statement('ALTER TABLE cita ADD CONSTRAINT chk_cita_horas CHECK (hora_fin > hora_inicio)');
        DB::statement("ALTER TABLE cita ADD CONSTRAINT ex_cita_sin_superposicion EXCLUDE USING gist (id_medico WITH =, tsrange(fecha + hora_inicio, fecha + hora_fin) WITH &&) WHERE (estado <> 'Cancelada' AND sobrecupo = FALSE)");
    }

    public function down(): void
    {
        Schema::dropIfExists('cita');
    }
};
