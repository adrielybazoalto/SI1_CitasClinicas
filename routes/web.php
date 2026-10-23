<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Rutas del sistema agrupadas por paquete
|--------------------------------------------------------------------------
| Cada ruta protegida lleva ->middleware('permiso:codigo').
*/
Route::middleware('auth')->group(function () {
    // Seguridad (CU02, CU03, bitácora)
    // Pacientes (CU05, CU06; CU04 ciclo 2)
    // Oferta médica (CU07, CU08, CU09; CU10, CU11 ciclo 2)
    // Citas (CU12, CU13, CU15, CU16, CU17; CU14 ciclo 2)
    // Atención (CU20, CU21, CU22)
    // Reportes (CU23)
    // Caja (CU18, CU19 ciclo 2)
});
