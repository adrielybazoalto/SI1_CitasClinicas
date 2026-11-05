<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La matriz de CU03 agrupa los permisos por paquete. Caja y Reportes
 * forman el paquete P6 "Finanzas y Reportes", así la matriz muestra los
 * 6 paquetes de la documentación. Solo actualiza la columna paquete.
 */
return new class extends Migration
{
    private const CODIGOS = ['caja.gestionar', 'caja.cobrar', 'reporte.generar'];

    public function up(): void
    {
        DB::table('permiso')->whereIn('codigo', self::CODIGOS)->update(['paquete' => 'Finanzas y Reportes']);
    }

    public function down(): void
    {
        DB::table('permiso')->whereIn('codigo', ['caja.gestionar', 'caja.cobrar'])->update(['paquete' => 'Caja']);
        DB::table('permiso')->where('codigo', 'reporte.generar')->update(['paquete' => 'Reportes']);
    }
};
