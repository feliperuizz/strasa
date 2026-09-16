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

// Disparo de notificações Push Agendadas (Resumo Diário e Postagens)
Schedule::command('notifications:send-scheduled')->everyMinute();

// Lembrete de 5 minutos antes da publicação
Schedule::command('notifications:publish-reminders')->everyMinute();

// E-mail de Briefing Diário (Todo dia às 09:00)
Schedule::command('emails:daily-briefing')->dailyAt('09:00');

// Mensalidades do Financeiro: cria as cobranças do mês seguinte em todas as
// empresas. A tela do Financeiro também gera ao abrir; isto garante que as
// cobranças existam mesmo se ninguém abrir (ex.: para o briefing do dia).
Schedule::command('financeiro:recorrencias')->dailyAt('00:10');
