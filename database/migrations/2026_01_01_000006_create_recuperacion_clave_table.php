<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recuperacion_clave', function (Blueprint $table) {
            $table->id('id_recuperacion');
            $table->integer('id_usuario');
            $table->string('token_hash', 255)->unique();
            $table->timestamp('expira_en');
            $table->timestamp('usado_en')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->foreign('id_usuario')->references('id_usuario')->on('usuario')->onDelete('cascade');
        });
        DB::statement('ALTER TABLE recuperacion_clave ADD CONSTRAINT chk_recuperacion_expira CHECK (expira_en > creado_en)');
    }

    public function down(): void
    {
        Schema::dropIfExists('recuperacion_clave');
    }
};
