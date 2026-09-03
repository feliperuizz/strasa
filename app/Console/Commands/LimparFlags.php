<?php

namespace App\Console\Commands;

use App\Models\Tag;
use Illuminate\Console\Command;

/**
 * Remove flags que não estão em nenhum card.
 *
 * As padrão (Programar, Captação, Edição, Programado) são sempre preservadas,
 * e as que estiverem aplicadas em pelo menos um card também — apagá-las
 * mudaria conteúdo, não faria limpeza.
 */
class LimparFlags extends Command
{
    protected $signature = 'flags:limpar
                            {--aplicar : Apaga de verdade. Sem esta opção, só mostra o que seria removido.}';

    protected $description = 'Remove flags sem uso, preservando as padrão e as que estão em cards';

    public function handle(): int
    {
        // withoutGlobalScopes porque o comando roda sem usuário logado, e o
        // CompanyScope não filtraria nada — queremos todas as empresas.
        $flags = Tag::withoutGlobalScopes()->withCount('tasks')->orderBy('company_id')->orderBy('name')->get();

        if ($flags->isEmpty()) {
            $this->info('Nenhuma flag cadastrada.');

            return self::SUCCESS;
        }

        $padrao = collect(Tag::PREDEFINIDAS)->keys()->map(fn ($n) => mb_strtolower($n));

        $manter = $flags->filter(
            fn ($f) => $f->tasks_count > 0 || $padrao->contains(mb_strtolower($f->name))
        );

        $remover = $flags->reject(
            fn ($f) => $f->tasks_count > 0 || $padrao->contains(mb_strtolower($f->name))
        );

        $this->newLine();
        $this->line('<fg=green>MANTER</> ('.$manter->count().')');

        foreach ($manter as $f) {
            $motivo = $padrao->contains(mb_strtolower($f->name))
                ? 'padrão'
                : 'em '.$f->tasks_count.' card(s)';

            $this->line(sprintf('  empresa %d  %-24s %s', $f->company_id, $f->name, $motivo));
        }

        $this->newLine();
        $this->line('<fg=red>REMOVER</> ('.$remover->count().')');

        if ($remover->isEmpty()) {
            $this->line('  nada a remover');
            $this->newLine();

            return self::SUCCESS;
        }

        foreach ($remover as $f) {
            $this->line(sprintf('  empresa %d  %-24s sem uso', $f->company_id, $f->name));
        }

        $this->newLine();

        if (! $this->option('aplicar')) {
            $this->warn('Simulação — nada foi apagado.');
            $this->line('Para apagar de verdade: php artisan flags:limpar --aplicar');

            return self::SUCCESS;
        }

        Tag::withoutGlobalScopes()->whereIn('id', $remover->pluck('id'))->delete();

        $this->info($remover->count().' flag(s) removida(s).');

        return self::SUCCESS;
    }
}
