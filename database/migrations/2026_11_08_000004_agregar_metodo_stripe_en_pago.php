<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * CU14 · El pago en línea con tarjeta pasa de PayPal a Stripe.
 * Agrega 'STRIPE' al CHECK de pago.metodo. 'PAYPAL' se conserva para los
 * pagos ya registrados (nada se borra). Solo en PostgreSQL: en SQLite
 * (tests) el CHECK no existe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // El nombre depende de si la tabla se creó con la migración o con 01_script.sql.
        DB::statement('ALTER TABLE pago DROP CONSTRAINT IF EXISTS chk_pago_metodo');
        DB::statement('ALTER TABLE pago DROP CONSTRAINT IF EXISTS pago_metodo_check');
        DB::statement("ALTER TABLE pago ADD CONSTRAINT chk_pago_metodo CHECK (metodo IN ('EFECTIVO','TARJETA','TRANSFERENCIA','PAYPAL','STRIPE','QR'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Falla a propósito si ya hay pagos con STRIPE: no se pierden registros.
        DB::statement('ALTER TABLE pago DROP CONSTRAINT IF EXISTS chk_pago_metodo');
        DB::statement("ALTER TABLE pago ADD CONSTRAINT chk_pago_metodo CHECK (metodo IN ('EFECTIVO','TARJETA','TRANSFERENCIA','PAYPAL','QR'))");
    }
};
