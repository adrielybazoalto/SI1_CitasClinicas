<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medico', function (Blueprint $table) {
            $table->id('id_medico');
            $table->integer('id_usuario')->nullable()->unique();
            $table->string('codigo', 12)->unique();
            $table->string('matricula', 30)->unique();
            $table->string('nombres', 60);
            $table->string('apellidos', 60);
            $table->string('telefono', 20)->nullable();
            $table->string('correo', 100)->nullable();
            $table->boolean('estado')->default(true);
            $table->foreign('id_usuario')->references('id_usuario')->on('usuario')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medico');
    }
};
