<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja', function (Blueprint $table) {
            $table->id('id_caja');
            $table->integer('id_usuario');
            $table->timestamp('apertura')->useCurrent();
            $table->timestamp('cierre')->nullable();
            $table->decimal('monto_inicial', 10, 2);
            $table->decimal('monto_esperado', 10, 2)->nullable();
            $table->decimal('monto_contado', 10, 2)->nullable();
            $table->decimal('diferencia', 10, 2)->nullable();
            $table->string('observacion', 200)->nullable();
            $table->string('estado', 7)->default('ABIERTA');
            $table->foreign('id_usuario')->references('id_usuario')->on('usuario')->onDelete('restrict');
        });
        DB::statement('ALTER TABLE caja ADD CONSTRAINT chk_caja_monto_inicial CHECK (monto_inicial >= 0)');
        DB::statement("ALTER TABLE caja ADD CONSTRAINT chk_caja_estado CHECK (estado IN ('ABIERTA','CERRADA'))");
        DB::statement('ALTER TABLE caja ADD CONSTRAINT chk_caja_cierre CHECK (cierre IS NULL OR cierre >= apertura)');
        DB::statement("CREATE UNIQUE INDEX ux_caja_abierta ON caja (id_usuario) WHERE estado = 'ABIERTA'");
    }

    public function down(): void
    {
        Schema::dropIfExists('caja');
    }
};
