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

// E-mail de Briefing Diário (Todo dia às 09:00 de Brasília — o servidor
// roda em UTC; sem o fuso aqui, saía às 06:00).
Schedule::command('emails:daily-briefing')->dailyAt('09:00')->timezone('America/Sao_Paulo');

// Mensalidades do Financeiro: cria as cobranças do mês seguinte em todas as
// empresas. A tela do Financeiro também gera ao abrir; isto garante que as
// cobranças existam mesmo se ninguém abrir (ex.: para o briefing do dia).
Schedule::command('financeiro:recorrencias')->dailyAt('00:10')->timezone('America/Sao_Paulo');

// Prévias leves das imagens antigas (as novas já nascem com prévia). Um lote
// por vez, começando pelas peças que estão com o cliente; quando todas
// existirem, cada rodada só confere e sai.
Schedule::command('previas:gerar --limite=40')->everyFiveMinutes()->withoutOverlapping(30);

/*
|--------------------------------------------------------------------------
| Estúdio de vídeo
|--------------------------------------------------------------------------
| Worker próprio, na conexão "video" (retry_after de 4 h): um render longo
| não trava convites e avisos da fila padrão, nem é pego duas vezes.
|
| SEM withoutOverlapping, de propósito: com runInBackground a trava só é
| solta por um "schedule:finish" separado; quando o worker morria (limite de
| threads/memória da hospedagem), a trava ficava presa por horas e nenhuma
| edição saía da fila. Não precisa dela: a fila entrega cada edição a um
| worker só (reserva atômica), e o próprio job limita quantas renderizam ao
| mesmo tempo (video.fila.simultaneas). Um worker extra que acorde sem
| trabalho sai na hora (--stop-when-empty).
| --max-jobs=1: processo novo por edição, sem acumular memória.
*/
Schedule::command('video:vigiar')->everyFiveMinutes();

Schedule::command('queue:work video --queue=video --stop-when-empty --max-jobs=1 --tries=1 --timeout=7000 --memory=1024')
    ->everyMinute()
    ->runInBackground();

// Retenção de disco: brutos de edições antigas (o final fica).
Schedule::command('video:limpar')->dailyAt('03:30')->timezone('America/Sao_Paulo');
