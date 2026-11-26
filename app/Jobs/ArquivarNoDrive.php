<?php

namespace App\Jobs;

use App\Models\VideoJob;
use App\Video\Nuvem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Depois do vídeo final: sobe para o Drive e libera o servidor.
 * Roda na fila "video" (o upload pode levar minutos). Se falhar, o final
 * continua no servidor e a tela oferece "enviar ao Drive de novo".
 */
class ArquivarNoDrive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public int $timeout = 3600;

    public function __construct(public int $jobId)
    {
        $this->onConnection(config('video.fila.conexao', 'video'));
        $this->onQueue(config('video.fila.nome', 'video'));
    }

    public function handle(): void
    {
        $job = VideoJob::withoutGlobalScopes()->find($this->jobId);

        if (! $job || ! Nuvem::ativo() || ! $job->final_path || $job->nuvem_status === 'salvo') {
            return;
        }

        $job->forceFill(['nuvem_status' => 'enviando', 'nuvem_erro' => null])->save();
        DB::disconnect();
        Nuvem::arquivar($job);
    }

    public function failed(?\Throwable $e): void
    {
        $job = VideoJob::withoutGlobalScopes()->find($this->jobId);

        if ($job && $job->nuvem_status !== 'salvo') {
            $msg = mb_substr($e?->getMessage() ?? 'erro desconhecido', 0, 450);
            $job->forceFill(['nuvem_status' => 'erro', 'nuvem_erro' => $msg])->save();
            $job->registrar('erro', 'Não consegui salvar o vídeo final no Drive (ele continua no servidor): '.$msg);
        }
    }
}
