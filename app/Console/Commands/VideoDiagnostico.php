<?php

namespace App\Console\Commands;

use App\Video\Diagnostico;
use Illuminate\Console\Command;

class VideoDiagnostico extends Command
{
    protected $signature = 'video:diagnostico {--teste : Faz um render de 2 s e uma pergunta real ao Claude}';

    protected $description = 'Confere se o servidor está pronto para o Estúdio de vídeo e diz como resolver o que falta';

    public function handle(): int
    {
        $r = (new Diagnostico)->rodar((bool) $this->option('teste'));
        $grupo = null;

        foreach ($r['itens'] as [$g, $nome, $status, $detalhe, $resolver]) {
            if ($g !== $grupo) {
                $this->newLine();
                $this->line("<options=bold>$g</>");
                $grupo = $g;
            }

            $marca = match ($status) {
                'ok' => '<fg=green>✓</>', 'aviso' => '<fg=yellow>!</>', default => '<fg=red>✗</>',
            };
            $this->line("  $marca $nome: $detalhe");

            if ($status !== 'ok' && $resolver) {
                $this->line("    <fg=gray>→ $resolver</>");
            }
        }

        $this->newLine();
        $r['erros']
            ? $this->error("{$r['erros']} problema(s) impedem o estúdio de funcionar.")
            : $this->info('Estúdio pronto'.($r['avisos'] ? " (com {$r['avisos']} aviso(s))." : '.'));

        return $r['erros'] ? self::FAILURE : self::SUCCESS;
    }
}
