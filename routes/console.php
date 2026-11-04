<?php

use App\Services\CitaService;
use App\Services\NotificacionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Temporizador (Scheduler) — se ejecuta cada minuto
|--------------------------------------------------------------------------
| Desarrollo: php artisan schedule:work
| - Marcar Inasistencia las citas Programada con hora + 15 min vencida (RN25).
| - Enviar notificaciones pendientes (recordatorios −24 h, RN23).
| - Ciclo 2: cancelar citas en línea con pago vencido (RN20).
*/
Schedule::call(fn () => app(CitaService::class)->marcarInasistenciasAutomaticas())
    ->name('citas:inasistencias')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::call(fn () => app(NotificacionService::class)->enviarPendientes())
    ->name('notificaciones:enviar')
    ->everyMinute()
    ->withoutOverlapping();
