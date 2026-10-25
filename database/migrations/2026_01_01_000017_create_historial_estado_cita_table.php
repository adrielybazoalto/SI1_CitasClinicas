<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_estado_cita', function (Blueprint $table) {
            $table->bigIncrements('id_historial');
            $table->integer('id_cita');
            $table->integer('id_usuario')->nullable();
            $table->string('estado_anterior', 12)->nullable();
            $table->string('estado_nuevo', 12);
            $table->string('detalle', 200)->nullable();
            $table->timestamp('fecha_hora')->useCurrent();
            $table->foreign('id_cita')->references('id_cita')->on('cita')->onDelete('cascade');
            $table->foreign('id_usuario')->references('id_usuario')->on('usuario')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_estado_cita');
    }
};
