<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Seguridad\RolController;
use App\Http\Controllers\Seguridad\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Sin DELETE /profile: las cuentas nunca se borran físicamente (RN11),
    // solo el administrador las desactiva (CU02).
});

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Rutas del sistema agrupadas por paquete
|--------------------------------------------------------------------------
| Cada ruta protegida lleva ->middleware('permiso:codigo').
*/
Route::middleware('auth')->group(function () {
    // Seguridad · CU02 Gestionar usuarios
    Route::prefix('seguridad/usuarios')->name('usuarios.')
        ->middleware('permiso:seguridad.usuarios')
        ->controller(UsuarioController::class)->group(function () {
            Route::get('/', 'listarUsuarios')->name('index');
            Route::get('/nuevo', 'formularioRegistro')->name('create');
            Route::post('/', 'registrarUsuario')->name('store');
            Route::get('/{usuario}/editar', 'formularioEdicion')->name('edit');
            Route::put('/{usuario}', 'actualizarUsuario')->name('update');
            Route::patch('/{usuario}/estado', 'cambiarEstado')->name('estado');
            Route::post('/{usuario}/restablecer-clave', 'restablecerClave')->name('restablecer');
        });

    // Seguridad · CU03 Gestionar roles y permisos
    Route::prefix('seguridad/roles')->name('roles.')
        ->middleware('permiso:seguridad.roles')
        ->controller(RolController::class)->group(function () {
            Route::get('/', 'listarRoles')->name('index');
            Route::post('/', 'registrarRol')->name('store');
            Route::get('/{rol}/permisos', 'cargarPermisos')->name('permisos');
            Route::put('/{rol}/permisos', 'actualizarPermisos')->name('permisos.update');
        });

    // Seguridad · bitácora
    // Pacientes (CU05, CU06; CU04 ciclo 2)
    // Oferta médica (CU07, CU08, CU09; CU10, CU11 ciclo 2)
    // Citas (CU12, CU13, CU15, CU16, CU17; CU14 ciclo 2)
    // Atención (CU20, CU21, CU22)
    // Reportes (CU23)
    // Caja (CU18, CU19 ciclo 2)
});
