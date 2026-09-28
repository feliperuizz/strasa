<?php

namespace App\Console\Commands;

use App\Models\TaskAttachment;
use App\Models\TaskApproval;
use App\Services\PreviaDeImagem;
use Illuminate\Console\Command;

/**
 * Gera as prévias que faltam, aos poucos.
 *
 * Imagens enviadas depois desta versão já nascem com prévia. Este comando
 * cobre as antigas: roda pelo agendador a cada 5 minutos, um lote por vez,
 * começando pelas peças que estão com o cliente aguardando aprovação.
 */
class GerarPreviasDeImagem extends Command
{
    protected $signature = 'previas:gerar {--limite=40 : Quantas imagens gerar nesta rodada (0 = todas)}';

    protected $description = 'Gera as prévias leves das imagens anexadas que ainda não têm';

    public function handle(PreviaDeImagem $previas): int
    {
        $limite = (int) $this->option('limite');
        set_time_limit(0);

        $emAprovacao = TaskApproval::withoutGlobalScopes()
            ->where('status', TaskApproval::PENDING)
            ->pluck('task_id')
            ->unique()
            ->all();

        $geradas = 0;
        $falhas = 0;

        $consulta = TaskAttachment::withoutGlobalScopes()
            ->where('is_image', true)
            // Peças aguardando o cliente primeiro; depois das mais novas às mais antigas.
            ->when($emAprovacao, fn ($q) => $q->orderByRaw(
                'CASE WHEN task_id IN ('.implode(',', array_map('intval', $emAprovacao)).') THEN 0 ELSE 1 END'
            ))
            ->orderByDesc('id');

        foreach ($consulta->cursor() as $anexo) {
            if (! $previas->suportada($anexo) || $previas->falhouRecentemente($anexo)) {
                continue;
            }

            $faltam = array_values(array_filter(
                array_keys(PreviaDeImagem::TAMANHOS),
                fn ($t) => ! $previas->existente($anexo, $t)
            ));

            if (! $faltam) {
                continue;
            }

            $feitas = $previas->gerarTodas($anexo, null, $faltam);
            count($feitas) ? $geradas++ : $falhas++;

            if ($limite > 0 && ($geradas + $falhas) >= $limite) {
                break;
            }
        }

        $this->info("Prévias geradas para {$geradas} imagem(ns)".($falhas ? "; {$falhas} ficam com o original (formato não suportado ou grande demais)." : '.'));

        return self::SUCCESS;
    }
}
