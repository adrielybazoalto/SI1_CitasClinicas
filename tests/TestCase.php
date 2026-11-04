<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Guardia de seguridad: los tests usan RefreshDatabase (migrate:fresh).
     * Si la configuración quedó en caché, Laravel ignora phpunit.xml y
     * borraría la base compartida de Supabase. Se verifica aquí, al crear
     * la aplicación, porque RefreshDatabase corre en setUp() ANTES de que
     * cualquier comprobación dentro de setUp() pueda ejecutarse.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $conexion = $app['config']->get('database.default');
        $base = $app['config']->get("database.connections.{$conexion}.database");

        if ($conexion !== 'sqlite' || $base !== ':memory:') {
            throw new RuntimeException(
                "PELIGRO: los tests deben correr en SQLite en memoria (conexión actual: {$conexion}). "
                .'Ejecuta "php artisan config:clear" y usa "composer test".'
            );
        }

        return $app;
    }
}
