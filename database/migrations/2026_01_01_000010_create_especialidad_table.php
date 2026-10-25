<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('especialidad', function (Blueprint $table) {
            $table->id('id_especialidad');
            $table->string('nombre', 60);
            $table->string('descripcion', 200)->nullable();
            $table->smallInteger('duracion_min')->default(30);
            $table->boolean('estado')->default(true);
        });
        DB::statement('ALTER TABLE especialidad ADD CONSTRAINT chk_especialidad_duracion CHECK (duracion_min BETWEEN 10 AND 120)');
        DB::statement('CREATE UNIQUE INDEX ux_especialidad_nombre ON especialidad (lower(trim(nombre)))');
    }

    public function down(): void
    {
        Schema::dropIfExists('especialidad');
    }
};
