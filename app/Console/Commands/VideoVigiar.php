<?php

namespace App\Console\Commands;

use App\Models\VideoJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Vigia do estúdio (agendado a cada 5 min):
 *
 *  - edição "editando" sem sinal de vida há N minutos = o processo morreu
 *    (falta de memória, reinício da VPS, kill do painel). Vira erro com
 *    botão de tentar de novo, que retoma de onde parou;
 *  - se nada está vivo, destrava as vagas e travas que o processo morto
 *    deixou presas (senão o estúdio inteiro esperaria 2 h);
 *  - marca que o agendador está rodando (o diagnóstico confere).
 */
class VideoVigiar extends Command
{
    protected $signature = 'video:vigiar';

    protected $description = 'Recupera edições interrompidas e destrava a fila do Estúdio de vídeo';

    public function handle(): int
    {
        Cache::put('video:agenda:ultimo', now()->toIso8601String(), now()->addDay());

        $limite = now()->subMinutes(max(5, (int) config('video.fila.sem_sinal_minutos', 20)));
        $emCurso = VideoJob::withoutGlobalScopes()->whereIn('status', [VideoJob::PROCESSANDO, VideoJob::AJUSTANDO, VideoJob::FINALIZANDO])->get();

        $mortas = $emCurso->filter(fn (VideoJob $j) => ($j->sinal_em ?? $j->updated_at)->lt($limite));
        $vivas = $emCurso->count() - $mortas->count();

        foreach ($mortas as $job) {
            $voltaPara = $job->status !== VideoJob::PROCESSANDO && $job->previa_path ? VideoJob::REVISAO : VideoJob::ERRO;
            $job->forceFill([
                'status' => $voltaPara,
                'erro' => 'O processamento parou sem aviso (o servidor encerrou o processo — falta de memória ou reinício). '
                    .($voltaPara === VideoJob::REVISAO ? 'A prévia anterior continua valendo: é só pedir de novo.' : 'Clique em "Tentar de novo": ele continua de onde parou.'),
                'mensagem' => null,
            ])->save();
            $job->registrar('erro', 'Processamento interrompido pelo servidor. Pode tentar de novo.');
            Cache::lock("video:edicao:{$job->id}")->forceRelease();
            $this->warn("Edição {$job->id} interrompida → {$voltaPara}");
        }

        if ($vivas === 0) {
            for ($i = 1; $i <= max(1, (int) config('video.fila.simultaneas', 1)); $i++) {
                Cache::lock("video:vaga:$i")->forceRelease();
            }
        }

        return self::SUCCESS;
    }
}
