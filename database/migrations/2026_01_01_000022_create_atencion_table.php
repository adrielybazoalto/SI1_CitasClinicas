<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atencion', function (Blueprint $table) {
            $table->id('id_atencion');
            $table->integer('id_cita')->unique();
            $table->text('diagnostico');
            $table->text('tratamiento')->nullable();
            $table->decimal('peso_kg', 5, 2)->nullable();
            $table->decimal('talla_cm', 5, 1)->nullable();
            $table->string('presion_arterial', 7)->nullable();
            $table->decimal('temperatura_c', 4, 1)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamp('registrado_en')->useCurrent();
            $table->foreign('id_cita')->references('id_cita')->on('cita')->onDelete('restrict');
        });
        DB::statement('ALTER TABLE atencion ADD CONSTRAINT chk_atencion_peso CHECK (peso_kg BETWEEN 0.5 AND 400)');
        DB::statement('ALTER TABLE atencion ADD CONSTRAINT chk_atencion_talla CHECK (talla_cm BETWEEN 20 AND 250)');
        DB::statement('ALTER TABLE atencion ADD CONSTRAINT chk_atencion_temperatura CHECK (temperatura_c BETWEEN 30 AND 45)');
    }

    public function down(): void
    {
        Schema::dropIfExists('atencion');
    }
};
