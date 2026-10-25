<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paciente', function (Blueprint $table) {
            $table->id('id_paciente');
            $table->integer('id_usuario')->nullable()->unique();
            $table->string('codigo', 12)->unique();
            $table->string('ci', 15)->unique();
            $table->string('nombres', 60);
            $table->string('apellidos', 60);
            $table->date('fecha_nacimiento');
            $table->char('sexo', 1)->nullable();
            $table->string('telefono', 20);
            $table->string('correo', 100)->nullable();
            $table->string('direccion', 150)->nullable();
            $table->boolean('recibe_recordatorios')->default(true);
            $table->boolean('estado')->default(true);
            $table->timestamp('creado_en')->useCurrent();
            $table->foreign('id_usuario')->references('id_usuario')->on('usuario')->onDelete('set null');
        });
        DB::statement("ALTER TABLE paciente ADD CONSTRAINT chk_paciente_fecha_nac CHECK (fecha_nacimiento <= CURRENT_DATE)");
        DB::statement("ALTER TABLE paciente ADD CONSTRAINT chk_paciente_sexo CHECK (sexo IN ('M','F','O'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('paciente');
    }
};
