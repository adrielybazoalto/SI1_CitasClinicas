<?php

use App\Http\Controllers\Atencion\AgendaController;
use App\Http\Controllers\Atencion\AtencionController;
use App\Http\Controllers\Atencion\HistorialController;
use App\Http\Controllers\Caja\CajaController;
use App\Http\Controllers\Caja\CobroController;
use App\Http\Controllers\Citas\AsistenciaController;
use App\Http\Controllers\Citas\CitaController;
use App\Http\Controllers\Citas\FichaController;
use App\Http\Controllers\Citas\ReubicacionController;
use App\Http\Controllers\Oferta\EspecialidadController;
use App\Http\Controllers\Oferta\HorarioController;
use App\Http\Controllers\Oferta\IndisponibilidadController;
use App\Http\Controllers\Oferta\MedicoController;
use App\Http\Controllers\Oferta\TarifaController;
use App\Http\Controllers\Pacientes\AntecedenteController;
use App\Http\Controllers\Pacientes\PacienteController;
use App\Http\Controllers\Pacientes\PerfilController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Reportes\ReporteController;
use App\Http\Controllers\Seguridad\BitacoraController;
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

    // Seguridad · CU24 Consultar bitácora (solo lectura)
    Route::get('/seguridad/bitacora', [BitacoraController::class, 'index'])
        ->middleware('permiso:bitacora.ver')
        ->name('bitacora.index');

    // Pacientes · CU05 Gestionar pacientes
    // CU06: la lista también la usan quienes solo gestionan antecedentes (médico), con ?accion=antecedentes.
    Route::get('/pacientes', [PacienteController::class, 'index'])
        ->middleware('permiso:paciente.gestionar,antecedente.gestionar')
        ->name('pacientes.index');

    Route::prefix('pacientes')->name('pacientes.')
        ->middleware('permiso:paciente.gestionar')
        ->controller(PacienteController::class)->group(function () {
            Route::get('/nuevo', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{paciente}/editar', 'edit')->name('edit');
            Route::put('/{paciente}', 'update')->name('update');
            Route::delete('/{paciente}', 'destroy')->name('destroy');
            Route::patch('/{paciente}/activar', 'activar')->name('activar');
        });

    // Pacientes · CU04 Gestionar perfil del paciente (solo el propio)
    Route::middleware('permiso:perfil.gestionar')->group(function () {
        Route::get('/mi-perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
        Route::put('/mi-perfil', [PerfilController::class, 'update'])->name('perfil.update');
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

    // Oferta médica · CU11 Gestionar tarifario
    Route::prefix('oferta/tarifas')->name('tarifas.')
        ->middleware('permiso:tarifa.gestionar')
        ->controller(TarifaController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/{especialidad}', 'show')->whereNumber('especialidad')->name('show');
        });

    // Oferta médica · CU10 Registrar indisponibilidad médica y reubicar citas
    Route::middleware('permiso:indisponibilidad.registrar')->group(function () {
        Route::get('/oferta/indisponibilidades', [IndisponibilidadController::class, 'index'])->name('indisponibilidades.index');
        Route::post('/oferta/indisponibilidades', [IndisponibilidadController::class, 'store'])->name('indisponibilidades.store');

        // Citas afectadas (paquete de Citas): reubicar o cancelar.
        Route::prefix('/oferta/indisponibilidades/{indisponibilidad}')->whereNumber('indisponibilidad')
            ->name('indisponibilidades.')->controller(ReubicacionController::class)->group(function () {
                Route::get('/citas', 'index')->name('citas');
                Route::post('/citas/{cita}/reubicar', 'reubicar')->name('reubicar');
                Route::post('/citas/{cita}/cancelar', 'cancelar')->name('cancelar');
            });
    });

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

    // Atención · CU21 Registrar atención médica
    Route::prefix('atencion/citas/{cita}')->name('atencion.')
        ->middleware('permiso:atencion.registrar')
        ->controller(AtencionController::class)->group(function () {
            Route::get('/atender', 'create')->name('create');
            Route::post('/atender', 'store')->name('store');
        });

    // Atención · CU22 Consultar historial del paciente
    Route::prefix('atencion/historial')->name('historial.')
        ->middleware('permiso:historial.consultar')
        ->controller(HistorialController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{paciente}', 'show')->whereNumber('paciente')->name('show');
        });

    // Reportes · CU23 Generar reportes (PDF / CSV)
    Route::prefix('reportes')->name('reportes.')
        ->middleware('permiso:reporte.generar')
        ->controller(ReporteController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/exportar/{formato}', 'exportar')->whereIn('formato', ['pdf', 'csv'])->name('exportar');
        });

    // Finanzas · CU18 Gestionar caja
    Route::prefix('caja')->name('caja.')
        ->middleware('permiso:caja.gestionar')
        ->controller(CajaController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/abrir', 'abrir')->name('abrir');
            Route::post('/cerrar', 'cerrar')->name('cerrar');
            Route::get('/{caja}/arqueo', 'arqueo')->whereNumber('caja')->name('arqueo');
        });

    // Finanzas · CU19 Cobrar cita en mostrador y emitir comprobante
    Route::prefix('cobros')->name('cobros.')
        ->middleware('permiso:caja.cobrar')
        ->controller(CobroController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/citas/{cita}', 'create')->name('create');
            Route::post('/citas/{cita}', 'store')->name('store');
            Route::get('/comprobantes/{pago}', 'verComprobante')->whereNumber('pago')->name('comprobante.ver');
            Route::get('/comprobantes/{pago}/pdf', 'comprobante')->whereNumber('pago')->name('comprobante');
            Route::post('/comprobantes/{pago}/enviar', 'enviar')->whereNumber('pago')->name('enviar');
        });
});
