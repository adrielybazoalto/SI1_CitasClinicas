<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * RNF08 · Bitácora inmutable.
 *
 * Impide a nivel de base de datos cualquier UPDATE, DELETE o TRUNCATE
 * sobre la tabla bitacora: solo se permite insertar. Solo aplica en
 * PostgreSQL (en SQLite, usado por los tests, no se crea).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_bitacora_inmutable() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'La bitácora es de solo inserción: no se permite %', TG_OP;
            END;
            $$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS tg_bitacora_inmutable ON bitacora;
            CREATE TRIGGER tg_bitacora_inmutable
                BEFORE UPDATE OR DELETE ON bitacora
                FOR EACH ROW EXECUTE FUNCTION fn_bitacora_inmutable();

            DROP TRIGGER IF EXISTS tg_bitacora_sin_truncate ON bitacora;
            CREATE TRIGGER tg_bitacora_sin_truncate
                BEFORE TRUNCATE ON bitacora
                FOR EACH STATEMENT EXECUTE FUNCTION fn_bitacora_inmutable();
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS tg_bitacora_sin_truncate ON bitacora;
            DROP TRIGGER IF EXISTS tg_bitacora_inmutable ON bitacora;
            DROP FUNCTION IF EXISTS fn_bitacora_inmutable();
        SQL);
    }
};
