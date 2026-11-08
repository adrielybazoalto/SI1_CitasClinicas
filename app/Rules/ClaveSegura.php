<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Política de contraseñas del sistema (CU01, CU02, CU04).
 * La clave debe tener al menos config('clinica.clave_min_longitud') caracteres,
 * con al menos una mayúscula, una minúscula, un número y un carácter especial.
 * Se usa en todos los formularios que crean o cambian una contraseña.
 */
class ClaveSegura implements ValidationRule
{
    /** Símbolos que usa el generador de claves temporales (fáciles de dictar). */
    private const SIMBOLOS = '@#$%&*!?';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $clave = (string) $value;
        $longitud = (int) config('clinica.clave_min_longitud');

        if (mb_strlen($clave) < $longitud) {
            $fail("La contraseña debe tener al menos {$longitud} caracteres.");
        }
        if (! preg_match('/\p{Lu}/u', $clave)) {
            $fail('La contraseña debe incluir al menos una letra mayúscula.');
        }
        if (! preg_match('/\p{Ll}/u', $clave)) {
            $fail('La contraseña debe incluir al menos una letra minúscula.');
        }
        if (! preg_match('/\d/', $clave)) {
            $fail('La contraseña debe incluir al menos un número.');
        }
        if (! preg_match('/[^\p{L}\p{N}\s]/u', $clave)) {
            $fail('La contraseña debe incluir al menos un carácter especial (por ejemplo @ # $ % & * ! ?).');
        }
        if (preg_match('/\s/u', $clave)) {
            $fail('La contraseña no puede contener espacios.');
        }
    }

    /** Reglas completas para el campo de la nueva contraseña. */
    public static function reglas(): array
    {
        return ['required', 'string', 'confirmed', new self()];
    }

    /**
     * Genera una clave temporal que cumple la política: una de cada tipo
     * y el resto al azar, mezclada. Sin caracteres ambiguos (0/O, 1/l/I).
     */
    public static function generar(): string
    {
        $mayusculas = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $minusculas = 'abcdefghijkmnpqrstuvwxyz';
        $numeros = '23456789';
        $todos = $mayusculas.$minusculas.$numeros.self::SIMBOLOS;

        $elegir = fn (string $juego) => $juego[random_int(0, strlen($juego) - 1)];

        $caracteres = [$elegir($mayusculas), $elegir($minusculas), $elegir($numeros), $elegir(self::SIMBOLOS)];
        for ($i = count($caracteres); $i < (int) config('clinica.clave_min_longitud'); $i++) {
            $caracteres[] = $elegir($todos);
        }

        // Mezcla de Fisher-Yates con random_int (criptográficamente seguro).
        for ($i = count($caracteres) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$caracteres[$i], $caracteres[$j]] = [$caracteres[$j], $caracteres[$i]];
        }

        return implode('', $caracteres);
    }
}
