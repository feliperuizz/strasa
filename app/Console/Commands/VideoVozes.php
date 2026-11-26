<?php

namespace App\Console\Commands;

use App\Video\FalhaDeFerramenta;
use App\Video\Narracao;
use Illuminate\Console\Command;

/**
 * Narração do estúdio: mostra as vozes instaladas e gera a amostra de cada
 * uma (o botão "Ouvir" da tela toca esse arquivo).
 *
 *   php artisan video:vozes            gera só as que faltam
 *   php artisan video:vozes --refazer  gera todas de novo
 *   php artisan video:vozes --testar="Texto qualquer" --voz=dora   gera um teste e diz onde salvou
 */
class VideoVozes extends Command
{
    protected $signature = 'video:vozes {--refazer : Gera todas as amostras de novo} {--testar= : Gera um áudio com este texto} {--voz= : Voz do teste}';

    protected $description = 'Lista as vozes da narração e gera as amostras para ouvir na tela';

    public function handle(): int
    {
        $vozes = Narracao::vozes();
        $this->table(['voz', 'descrição', 'motor', 'instalada'], array_map(
            fn ($id, $v) => [$id, $v['descricao'], $v['motor'], Narracao::motorPronto($v['motor']) ? 'sim' : 'não'],
            array_keys($vozes), $vozes
        ));

        if (! Narracao::disponivel()) {
            $this->error('Nenhuma voz instalada. Rode: bash scripts/video/instalar-servidor.sh');

            return self::FAILURE;
        }

        try {
            if ($texto = $this->option('testar')) {
                $voz = $this->option('voz') ?: (string) config('video.narracao.voz_padrao');
                $inicio = microtime(true);
                $r = Narracao::sintetizar([['id' => 'teste', 'texto' => $texto]], $voz, 1.0, storage_path('app/video-teste'));
                $this->info(sprintf('Pronto: %s (%.1f s de voz em %.1f s).', $r['teste']['arquivo'], $r['teste']['duracao'], microtime(true) - $inicio));

                return self::SUCCESS;
            }

            $this->line('Gerando as amostras (a primeira vez demora um pouco)…');
            foreach (Narracao::gerarAmostras((bool) $this->option('refazer')) as $voz => $arquivo) {
                $this->info("  {$voz}: {$arquivo}");
            }
        } catch (FalhaDeFerramenta $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
