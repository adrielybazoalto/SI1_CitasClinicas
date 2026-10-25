<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobante', function (Blueprint $table) {
            $table->id('id_comprobante');
            $table->integer('id_pago')->unique();
            $table->integer('numero')->unique();
            $table->timestamp('fecha_emision')->useCurrent();
            $table->foreign('id_pago')->references('id_pago')->on('pago')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobante');
    }
};
