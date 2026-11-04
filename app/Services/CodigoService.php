<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Genera los códigos correlativos PAC-000001, MED-0001 y CM-000123.
 *
 * El número se obtiene del valor máximo actual de la columna. Los códigos
 * están rellenados con ceros, por lo que el máximo lexicográfico coincide
 * con el máximo numérico.
 */
class CodigoService
{
    /**
     * Siguiente código de una tabla, por ejemplo siguiente('paciente', 'codigo', 'PAC-', 6).
     */
    public function siguiente(string $tabla, string $columna, string $prefijo, int $digitos): string
    {
        $ultimo = DB::table($tabla)->max($columna);
        $numero = 1;

        if ($ultimo !== null && preg_match('/(\d+)\s*$/', (string) $ultimo, $coincidencia)) {
            $numero = ((int) $coincidencia[1]) + 1;
        }

        return $prefijo.str_pad((string) $numero, $digitos, '0', STR_PAD_LEFT);
    }

    /** Código de paciente: PAC-000001. */
    public function paciente(): string
    {
        $config = config('clinica.codigos.paciente');

        return $this->siguiente('paciente', 'codigo', $config['prefijo'], $config['digitos']);
    }

    /** Código de médico: MED-0001. */
    public function medico(): string
    {
        $config = config('clinica.codigos.medico');

        return $this->siguiente('medico', 'codigo', $config['prefijo'], $config['digitos']);
    }

    /** Código de cita: CM-000123. */
    public function cita(): string
    {
        $config = config('clinica.codigos.cita');

        return $this->siguiente('cita', 'codigo', $config['prefijo'], $config['digitos']);
    }
}
