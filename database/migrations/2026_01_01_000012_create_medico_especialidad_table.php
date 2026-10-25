<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medico_especialidad', function (Blueprint $table) {
            $table->integer('id_medico');
            $table->integer('id_especialidad');
            $table->primary(['id_medico', 'id_especialidad']);
            $table->foreign('id_medico')->references('id_medico')->on('medico')->onDelete('restrict');
            $table->foreign('id_especialidad')->references('id_especialidad')->on('especialidad')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medico_especialidad');
    }
};
