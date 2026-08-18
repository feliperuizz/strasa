<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Agendamento (cron do cPanel chama `php artisan schedule:run` a cada minuto)
|--------------------------------------------------------------------------
| Em hospedagem compartilhada não há um worker permanente, então a cada
| minuto processamos a fila (envio de convites) até esvaziá-la e encerramos.
*/
Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();

// Limpeza de jobs em lote antigos (opcional, mantém o banco enxuto).
Schedule::command('queue:prune-batches --hours=48')->daily();
