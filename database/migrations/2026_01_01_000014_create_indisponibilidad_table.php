<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indisponibilidad', function (Blueprint $table) {
            $table->id('id_indisponibilidad');
            $table->integer('id_medico');
            $table->integer('registrado_por')->nullable();
            $table->timestamp('inicio');
            $table->timestamp('fin');
            $table->string('motivo', 150);
            $table->foreign('id_medico')->references('id_medico')->on('medico')->onDelete('restrict');
            $table->foreign('registrado_por')->references('id_usuario')->on('usuario')->onDelete('set null');
        });
        DB::statement('ALTER TABLE indisponibilidad ADD CONSTRAINT chk_indisponibilidad_fechas CHECK (fin > inicio)');
    }

    public function down(): void
    {
        Schema::dropIfExists('indisponibilidad');
    }
};
