<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pago', function (Blueprint $table) {
            $table->id('id_pago');
            $table->integer('id_cita');
            $table->integer('id_caja')->nullable();
            $table->integer('registrado_por')->nullable();
            $table->string('tipo', 10)->default('COBRO');
            $table->string('metodo', 15);
            $table->decimal('monto', 10, 2);
            $table->string('id_transaccion', 80)->nullable();
            $table->string('estado', 10)->default('APROBADO');
            $table->timestamp('fecha_hora')->useCurrent();
            $table->foreign('id_cita')->references('id_cita')->on('cita')->onDelete('restrict');
            $table->foreign('id_caja')->references('id_caja')->on('caja')->onDelete('restrict');
            $table->foreign('registrado_por')->references('id_usuario')->on('usuario')->onDelete('set null');
        });
        DB::statement("ALTER TABLE pago ADD CONSTRAINT chk_pago_tipo CHECK (tipo IN ('COBRO','REEMBOLSO'))");
        DB::statement("ALTER TABLE pago ADD CONSTRAINT chk_pago_metodo CHECK (metodo IN ('EFECTIVO','TARJETA','TRANSFERENCIA','PAYPAL','QR'))");
        DB::statement('ALTER TABLE pago ADD CONSTRAINT chk_pago_monto CHECK (monto > 0)');
        DB::statement("ALTER TABLE pago ADD CONSTRAINT chk_pago_estado CHECK (estado IN ('PENDIENTE','APROBADO','RECHAZADO'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('pago');
    }
};
