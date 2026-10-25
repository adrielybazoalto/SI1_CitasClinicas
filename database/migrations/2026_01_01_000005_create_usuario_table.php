<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->integer('id_rol');
            $table->string('username', 50)->unique();
            $table->string('correo', 100)->unique();
            $table->string('password_hash', 255);
            $table->smallInteger('intentos_fallidos')->default(0);
            $table->timestamp('bloqueado_hasta')->nullable();
            $table->boolean('debe_cambiar_clave')->default(false);
            $table->boolean('estado')->default(true);
            $table->timestamp('creado_en')->useCurrent();
            $table->foreign('id_rol')->references('id_rol')->on('rol')->onDelete('restrict');
        });
        DB::statement('ALTER TABLE usuario ADD CONSTRAINT chk_usuario_intentos_fallidos CHECK (intentos_fallidos >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario');
    }
};
