<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El Administrador supervisa todo el sistema: ve los 24 CU en el menú.
 * Solo AGREGA las filas que faltan en rol_permiso (no borra nada).
 */
return new class extends Migration
{
    private const NUEVOS = ['pago.en_linea', 'atencion.registrar', 'perfil.gestionar'];

    public function up(): void
    {
        $admin = DB::table('rol')->where('nombre', 'Administrador')->value('id_rol');
        if (! $admin) {
            return;
        }

        $tiene = DB::table('rol_permiso')->where('id_rol', $admin)->pluck('id_permiso')->all();
        $faltan = DB::table('permiso')->whereNotIn('id_permiso', $tiene)->pluck('id_permiso');

        foreach ($faltan as $idPermiso) {
            DB::table('rol_permiso')->insert(['id_rol' => $admin, 'id_permiso' => $idPermiso]);
        }
    }

    public function down(): void
    {
        $admin = DB::table('rol')->where('nombre', 'Administrador')->value('id_rol');
        $ids = DB::table('permiso')->whereIn('codigo', self::NUEVOS)->pluck('id_permiso');
        DB::table('rol_permiso')->where('id_rol', $admin)->whereIn('id_permiso', $ids)->delete();
    }
};
