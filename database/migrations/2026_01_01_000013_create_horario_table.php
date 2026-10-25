<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horario', function (Blueprint $table) {
            $table->id('id_horario');
            $table->integer('id_medico');
            $table->integer('id_especialidad');
            $table->smallInteger('dia_semana');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->smallInteger('duracion_min');
            $table->string('consultorio', 20)->nullable();
            $table->boolean('estado')->default(true);
            $table->foreign(['id_medico', 'id_especialidad'])
                ->references(['id_medico', 'id_especialidad'])
                ->on('medico_especialidad')
                ->onDelete('restrict');
        });
        DB::statement('ALTER TABLE horario ADD CONSTRAINT chk_horario_dia_semana CHECK (dia_semana BETWEEN 1 AND 7)');
        DB::statement('ALTER TABLE horario ADD CONSTRAINT chk_horario_duracion CHECK (duracion_min BETWEEN 10 AND 120)');
        DB::statement('ALTER TABLE horario ADD CONSTRAINT chk_horario_horas CHECK (hora_fin > hora_inicio)');
    }

    public function down(): void
    {
        Schema::dropIfExists('horario');
    }
};
