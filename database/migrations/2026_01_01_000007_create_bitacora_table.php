<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora', function (Blueprint $table) {
            $table->bigIncrements('id_bitacora');
            $table->integer('id_usuario')->nullable();
            $table->timestamp('fecha_hora')->useCurrent();
            $table->string('ip', 45)->nullable();
            $table->string('accion', 50);
            $table->string('tabla', 50)->nullable();
            $table->integer('id_registro')->nullable();
            $table->text('detalle')->nullable();
            $table->foreign('id_usuario')->references('id_usuario')->on('usuario')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora');
    }
};
