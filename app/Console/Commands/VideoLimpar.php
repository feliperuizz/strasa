<?php

namespace App\Console\Commands;

use App\Models\VideoJob;
use App\Video\Armazenamento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Retenção de disco do estúdio (agendado todo dia de madrugada).
 *
 * Vídeo 4K de iPhone ocupa ~400 MB por minuto; sem limpeza o disco da VPS
 * enche e derruba o site junto (sessão e cache são arquivo).
 *
 *  - edições prontas/canceladas há mais de N dias: apaga brutos, proxies e
 *    caches; mantém o vídeo final, a capa e o plano;
 *  - rascunhos abandonados há 7 dias (upload que ninguém terminou): apaga tudo;
 *  - pedaços de upload órfãos (arquivo .parcial esquecido).
 */
class VideoLimpar extends Command
{
    protected $signature = 'video:limpar {--dias= : Sobrepõe video.retencao_dias} {--simular : Só mostra o que faria}';

    protected $description = 'Libera disco do Estúdio de vídeo (brutos e arquivos de trabalho antigos)';

    public function handle(): int
    {
        $dias = (int) ($this->option('dias') ?? config('video.retencao_dias', 21));
        $simular = (bool) $this->option('simular');
        $liberado = 0;

        $antigas = VideoJob::withoutGlobalScopes()
            ->whereIn('status', [VideoJob::PRONTO, VideoJob::CANCELADO, VideoJob::ERRO, VideoJob::REVISAO])
            ->whereNull('limpo_em')
            ->where('updated_at', '<', now()->subDays($dias))
            ->get();

        foreach ($antigas as $job) {
            $pasta = $job->pasta();
            $antes = $pasta->tamanho();

            if (! $simular) {
                $pasta->limparPesados();
                File::deleteDirectory($pasta->base().'/renders/cache');
                $job->forceFill(['limpo_em' => now()])->saveQuietly();
                $job->registrar('sistema', "Retenção: brutos e arquivos de trabalho apagados após {$dias} dias. O vídeo final continua disponível.");
            }

            $liberado += $antes - ($simular ? 0 : $pasta->tamanho());
            $this->line("Edição {$job->id}: ".round($antes / 1048576).' MB');
        }

        $abandonados = VideoJob::withoutGlobalScopes()
            ->where('status', VideoJob::RASCUNHO)
            ->where('updated_at', '<', now()->subDays(7))
            ->get();

        foreach ($abandonados as $job) {
            $liberado += $job->pasta()->tamanho();
            $this->line("Rascunho abandonado {$job->id}: apagado");
            if (! $simular) {
                $job->pasta()->apagarTudo();
                $job->delete();
            }
        }

        // Pastas órfãs: edição excluída enquanto o worker ainda gravava arquivos.
        $existentes = VideoJob::withoutGlobalScopes()->pluck('id')->flip();
        foreach (glob(Armazenamento::raiz().'/company-*/job-*', GLOB_ONLYDIR) ?: [] as $dir) {
            $id = (int) substr(basename($dir), 4);
            if (! $existentes->has($id) && filemtime($dir) < now()->subHours(6)->timestamp) {
                $this->line('Pasta órfã '.basename(dirname($dir)).'/'.basename($dir).': apagada');
                if (! $simular) {
                    File::deleteDirectory($dir);
                }
            }
        }

        $this->info(($simular ? '[simulação] ' : '').'Liberado: '.round($liberado / 1048576).' MB. Livre agora: '.(Armazenamento::espacoLivreGb() ?? '?').' GB.');

        return self::SUCCESS;
    }
}
