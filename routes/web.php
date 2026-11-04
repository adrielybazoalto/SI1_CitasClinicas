<?php

use App\Http\Controllers\Atencion\AgendaController;
use App\Http\Controllers\Citas\AsistenciaController;
use App\Http\Controllers\Citas\CitaController;
use App\Http\Controllers\Citas\FichaController;
use App\Http\Controllers\Oferta\EspecialidadController;
use App\Http\Controllers\Oferta\HorarioController;
use App\Http\Controllers\Oferta\MedicoController;
use App\Http\Controllers\Pacientes\AntecedenteController;
use App\Http\Controllers\Pacientes\PacienteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Seguridad\RolController;
use App\Http\Controllers\Seguridad\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

// {cita} siempre es numérico: evita que /citas/fichas se tome como una cita.
Route::pattern('cita', '[0-9]+');

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
        ->middleware('permiso:usuario.gestionar')
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
        ->middleware('permiso:rol.gestionar')
        ->controller(RolController::class)->group(function () {
            Route::get('/', 'listarRoles')->name('index');
            Route::post('/', 'registrarRol')->name('store');
            Route::get('/{rol}/permisos', 'cargarPermisos')->name('permisos');
            Route::put('/{rol}/permisos', 'actualizarPermisos')->name('permisos.update');
        });

    // Seguridad · bitácora

    // Pacientes · CU05 Gestionar pacientes
    Route::prefix('pacientes')->name('pacientes.')
        ->middleware('permiso:paciente.gestionar')
        ->controller(PacienteController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/nuevo', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{paciente}/editar', 'edit')->name('edit');
            Route::put('/{paciente}', 'update')->name('update');
            Route::delete('/{paciente}', 'destroy')->name('destroy');
            Route::patch('/{paciente}/activar', 'activar')->name('activar');
        });

    // Pacientes · CU06 Gestionar antecedentes clínicos
    Route::get('/pacientes/{paciente}/antecedentes', [AntecedenteController::class, 'edit'])
        ->middleware('permiso:antecedente.gestionar')
        ->name('pacientes.antecedentes');

    Route::put('/pacientes/{paciente}/antecedentes', [AntecedenteController::class, 'update'])
        ->middleware('permiso:antecedente.gestionar')
        ->name('pacientes.antecedentes.update');

    // Oferta médica · CU07 Gestionar especialidades
    Route::prefix('oferta/especialidades')->name('especialidades.')
        ->middleware('permiso:especialidad.gestionar')
        ->controller(EspecialidadController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/nuevo', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{especialidad}/editar', 'edit')->name('edit');
            Route::put('/{especialidad}', 'update')->name('update');
            Route::patch('/{especialidad}/estado', 'cambiarEstado')->name('estado');
        });

    // Oferta médica · CU08 Gestionar médicos
    Route::prefix('oferta/medicos')->name('medicos.')
        ->middleware('permiso:medico.gestionar')
        ->controller(MedicoController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/nuevo', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{medico}/editar', 'edit')->name('edit');
            Route::put('/{medico}', 'update')->name('update');
            Route::patch('/{medico}/estado', 'cambiarEstado')->name('estado');
        });

    // Oferta médica · CU09 Gestionar horarios de atención
    Route::prefix('oferta/horarios')->name('horarios.')
        ->middleware('permiso:horario.gestionar')
        ->controller(HorarioController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/nuevo', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{horario}/editar', 'edit')->name('edit');
            Route::put('/{horario}', 'update')->name('update');
            Route::patch('/{horario}/estado', 'cambiarEstado')->name('estado');
        });

    // Oferta médica (CU10, CU11 ciclo 2)

    // Citas · CU12 Consultar fichas disponibles
    Route::prefix('citas/fichas')->name('fichas.')
        ->controller(FichaController::class)->group(function () {
            Route::get('/', 'index')->middleware('permiso:cita.consultar')->name('index');
            Route::get('/disponibles', 'disponibles')->middleware('permiso:cita.consultar')->name('disponibles');
            Route::get('/pacientes', 'pacientes')->middleware('permiso:cita.programar')->name('pacientes');
        });

    // Citas · CU13 Programar, CU15 Reprogramar, CU16 Cancelar
    Route::prefix('citas')->name('citas.')
        ->controller(CitaController::class)->group(function () {
            Route::get('/', 'index')->middleware('permiso:cita.consultar,cita.asistencia')->name('index');
            Route::post('/', 'store')->middleware('permiso:cita.programar')->name('store');
            Route::get('/{cita}', 'show')->middleware('permiso:cita.consultar,cita.asistencia')->name('show');
            Route::get('/{cita}/reprogramar', 'editReprogramar')->middleware('permiso:cita.reprogramar')->name('reprogramar');
            Route::put('/{cita}/reprogramar', 'reprogramar')->middleware('permiso:cita.reprogramar')->name('reprogramar.update');
            Route::patch('/{cita}/cancelar', 'cancelar')->middleware('permiso:cita.cancelar')->name('cancelar');
        });

    // Citas · CU17 Registrar asistencia e inasistencia
    Route::prefix('citas/{cita}')->name('citas.')
        ->middleware('permiso:cita.asistencia')
        ->controller(AsistenciaController::class)->group(function () {
            Route::patch('/llegada', 'llegada')->name('llegada');
            Route::patch('/inasistencia', 'inasistencia')->name('inasistencia');
        });

    // Atención · CU20 Consultar agenda médica
    Route::get('/atencion/agenda', [AgendaController::class, 'index'])
        ->middleware('permiso:agenda.consultar')
        ->name('agenda.index');

    // Atención (CU21, CU22)
    // Reportes (CU23)
    // Caja (CU18, CU19 ciclo 2)
});
