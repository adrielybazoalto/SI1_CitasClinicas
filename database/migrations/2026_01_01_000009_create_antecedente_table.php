<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('antecedente', function (Blueprint $table) {
            $table->integer('id_paciente')->primary();
            $table->integer('actualizado_por')->nullable();
            $table->string('tipo_sangre', 3)->nullable();
            $table->text('alergias')->nullable();
            $table->text('enfermedades_cronicas')->nullable();
            $table->text('medicamentos')->nullable();
            $table->text('antecedentes')->nullable();
            $table->string('contacto_nombre', 100)->nullable();
            $table->string('contacto_parentesco', 30)->nullable();
            $table->string('contacto_telefono', 20)->nullable();
            $table->timestamp('actualizado_en')->useCurrent();
            $table->foreign('id_paciente')->references('id_paciente')->on('paciente')->onDelete('cascade');
            $table->foreign('actualizado_por')->references('id_usuario')->on('usuario')->onDelete('set null');
        });
        DB::statement("ALTER TABLE antecedente ADD CONSTRAINT chk_antecedente_tipo_sangre CHECK (tipo_sangre IN ('A+','A-','B+','B-','AB+','AB-','O+','O-'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('antecedente');
    }
};
