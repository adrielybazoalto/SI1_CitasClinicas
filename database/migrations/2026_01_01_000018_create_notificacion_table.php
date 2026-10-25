<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificacion', function (Blueprint $table) {
            $table->id('id_notificacion');
            $table->integer('id_paciente');
            $table->integer('id_cita')->nullable();
            $table->string('tipo', 20);
            $table->timestamp('programada_para');
            $table->timestamp('enviada_en')->nullable();
            $table->string('estado', 10)->default('PENDIENTE');
            $table->foreign('id_paciente')->references('id_paciente')->on('paciente')->onDelete('cascade');
            $table->foreign('id_cita')->references('id_cita')->on('cita')->onDelete('cascade');
        });
        DB::statement("ALTER TABLE notificacion ADD CONSTRAINT chk_notificacion_tipo CHECK (tipo IN ('RECORDATORIO','REPROGRAMACION','CANCELACION','COMPROBANTE'))");
        DB::statement("ALTER TABLE notificacion ADD CONSTRAINT chk_notificacion_estado CHECK (estado IN ('PENDIENTE','ENVIADA','FALLIDA'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacion');
    }
};
