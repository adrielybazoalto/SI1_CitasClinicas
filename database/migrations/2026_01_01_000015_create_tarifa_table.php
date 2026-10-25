<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifa', function (Blueprint $table) {
            $table->id('id_tarifa');
            $table->integer('id_especialidad');
            $table->decimal('monto', 10, 2);
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->foreign('id_especialidad')->references('id_especialidad')->on('especialidad')->onDelete('restrict');
        });
        DB::statement('ALTER TABLE tarifa ADD CONSTRAINT chk_tarifa_monto CHECK (monto > 0)');
        DB::statement('ALTER TABLE tarifa ADD CONSTRAINT chk_tarifa_vigencia CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde)');
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifa');
    }
};
