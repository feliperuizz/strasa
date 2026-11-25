<?php

namespace App\Video\Claude;

use App\Models\VideoJob;
use App\Models\VideoLearning;
use App\Models\VideoProfile;
use App\Video\Estilos;
use App\Video\Plano\Linha;
use Illuminate\Support\Facades\File;

/**
 * Monta a pasta de trabalho do Claude para uma edição:
 *
 *   claude/contexto.md     tudo o que ele precisa saber (cliente, briefing, material)
 *   claude/guias/          regras.md, estilo.md, plano.md (o ofício)
 *   claude/transcricao.txt frases com tempo (quando há fala)
 *   claude/plano.json      onde ele escreve (e, nos ajustes, o plano atual)
 *   claude/pedido.md       só nos ajustes
 *   claude/mapa.txt        só nos ajustes: tempo da prévia ↔ tempo de origem
 */
class Contexto
{
    public function __construct(private VideoJob $job) {}

    public function pasta(): string
    {
        return $this->job->pasta()->dir('claude');
    }

    /** Prepara tudo para a primeira edição. Devolve o prompt. */
    public function paraEdicao(array $inventario, ?string $frases, ?array $musica): string
    {
        $dir = $this->pasta();
        $this->copiarGuias($dir);
        @unlink("$dir/plano.json");

        if ($frases) {
            file_put_contents("$dir/transcricao.txt", $frases);
        }

        file_put_contents("$dir/contexto.md", $this->documento($inventario, (bool) $frases, $musica));

        return 'Leia contexto.md nesta pasta e siga as instruções dele, na ordem. '
            .'Sua entrega é o arquivo plano.json nesta pasta, conforme guias/plano.md. '
            .'Termine com 2 a 4 frases para a equipe dizendo o que você fez.';
    }

    /** Prepara um ajuste pedido pela equipe sobre a prévia atual. Devolve o prompt. */
    public function paraAjuste(string $pedido, array $planoAtual, Linha $linha, array $palavras, array $inventario, ?array $musica, ?string $frases): string
    {
        $dir = $this->pasta();
        $this->copiarGuias($dir);

        if (! is_file("$dir/contexto.md")) {
            file_put_contents("$dir/contexto.md", $this->documento($inventario, (bool) $frases, $musica));
        }
        if ($frases && ! is_file("$dir/transcricao.txt")) {
            file_put_contents("$dir/transcricao.txt", $frases);
        }

        // O plano atual sem os campos gerados pelo sistema (o Claude edita só o que é dele).
        $plano = $planoAtual;
        unset($plano['correcoes_automaticas'], $plano['versao_esquema']);
        $plano['segmentos'] = array_values(array_filter($plano['segmentos'], fn ($s) => ($s['id'] ?? '') !== 'fim'));
        $plano['aprendizados'] = [];
        foreach ($plano['elementos'] as &$el) {
            unset($el['inicio']);
        }
        unset($el);

        file_put_contents("$dir/plano.json", json_encode($plano, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        file_put_contents("$dir/mapa.txt", $this->mapa($linha, $palavras));

        $historico = $this->job->eventos()->whereIn('tipo', ['pedido', 'claude'])->latest('id')->limit(8)->get()->reverse()
            ->map(fn ($e) => ($e->tipo === 'pedido' ? '- Equipe: ' : '- Você: ').str_replace("\n", ' ', mb_substr($e->texto, 0, 400)))
            ->implode("\n");

        file_put_contents("$dir/pedido.md", implode("\n", [
            '# Pedido de ajuste da equipe',
            '',
            "A equipe assistiu à prévia v{$this->job->previa_versao} e pediu:",
            '',
            '> '.str_replace("\n", "\n> ", trim($pedido)),
            '',
            '## Como fazer',
            '- Os tempos que a equipe cita ("em 0:12") são do vídeo da prévia. Converta com mapa.txt (cada segmento com o tempo na prévia e o tempo de origem, e as falas no tempo da prévia).',
            '- Itens citados por número ("mo04", "s07") são os ids do plano.',
            '- Edite plano.json (é o plano atual) aplicando SÓ o que foi pedido. Preserve todo o resto.',
            '- Se o pedido vale para os próximos vídeos deste cliente (gosto, estilo), escreva em "aprendizados" uma frase com contexto. Pedido só deste vídeo não entra.',
            '- Se o pedido for impossível com o material, faça o mais próximo e explique em "avisos".',
            '- Consulte contexto.md e os guias se precisar. Na resposta final, diga em 1 a 3 frases o que mudou.',
            '',
            $historico ? "## Conversa até aqui\n$historico" : '',
        ]));

        return 'Leia pedido.md nesta pasta e aplique o ajuste editando plano.json. Termine com 1 a 3 frases dizendo o que mudou.';
    }

    private function copiarGuias(string $dir): void
    {
        File::ensureDirectoryExists("$dir/guias", 0775);
        copy(resource_path('video/regras.md'), "$dir/guias/regras.md");
        copy(resource_path('video/plano.md'), "$dir/guias/plano.md");
        file_put_contents("$dir/guias/estilo.md", Estilos::guia($this->job->estilo));
    }

    private function documento(array $inventario, bool $temTranscricao, ?array $musica): string
    {
        $job = $this->job;
        $client = $job->client;
        $estilo = $job->estiloInfo();
        $perfil = VideoProfile::doCliente($client);
        $dim = $job->dimensoes();
        [$min, $max, $alvoPadrao] = $estilo['duracao'];
        $alvo = $job->duracao_alvo ?: $alvoPadrao;

        $aprendizados = VideoLearning::withoutGlobalScopes()
            ->where('client_id', $client->id)->where('status', 'aplicado')
            ->latest()->limit(40)->pluck('texto');

        $l = [];
        $l[] = "# Edição: {$job->titulo}";
        $l[] = '';
        $l[] = "- **Cliente:** {$client->name}".($client->segment ? " ({$client->segment})" : '');
        $l[] = "- **Estilo:** {$estilo['nome']} — {$estilo['resumo']}";
        $l[] = "- **Formato:** {$job->formato} ({$dim['largura']}x{$dim['altura']}, 30 fps) — {$dim['nome']}";
        $l[] = "- **Duração:** mire em ~{$alvo} s (faixa aceitável {$min}–{$max} s, sem contar o cartão final).";
        $l[] = '';
        $l[] = '## Passo a passo';
        $l[] = '1. Leia `guias/regras.md` (ofício geral), `guias/estilo.md` (este estilo) e `guias/plano.md` (o formato da entrega).';
        $l[] = '2. Leia a identidade, os aprendizados e o briefing abaixo.';
        $l[] = $temTranscricao
            ? '3. Leia `transcricao.txt` inteiro (frases com tempo de origem e o tempo de cada palavra).'
            : '3. Este estilo não tem fala para transcrever: a decisão é pela imagem e pela música.';
        $l[] = '4. Abra as folhas de quadros listadas (ferramenta Read) para ver o material.';
        $l[] = '5. Escreva `plano.json`. Releia uma vez conferindo: chaves de arquivo certas, tempos dentro da duração de cada arquivo, JSON válido.';
        $l[] = '';

        $l[] = '## Identidade do cliente (siga sempre)';
        foreach ($perfil->resumoParaClaude() as $chave => $valor) {
            $l[] = '- **'.str_replace('_', ' ', $chave).':** '.(is_array($valor) ? json_encode($valor, JSON_UNESCAPED_UNICODE) : $valor);
        }
        $ajustes = array_filter((array) $job->opcao('identidade', []), fn ($v) => $v !== null && $v !== '');
        if ($ajustes) {
            $l[] = '- **Só nesta edição a equipe mudou:** '.json_encode($ajustes, JSON_UNESCAPED_UNICODE).' (vale isto, não o de cima).';
        }
        if (! $job->opcao('logo', true)) {
            $l[] = '- **Esta edição vai SEM logo** (nem no canto, nem no cartão final). Não planeje nada que dependa da logo.';
        }
        $l[] = '- O render já aplica cores, fonte, logo e estilo de legenda. Você escolhe o quê e quando.';
        $l[] = '';

        if ($aprendizados->isNotEmpty()) {
            $l[] = '## Aprendizados deste cliente (correções de edições anteriores — valem mais que os guias)';
            foreach ($aprendizados as $a) {
                $l[] = "- $a";
            }
            $l[] = '';
        }

        $l[] = '## Briefing da equipe';
        $l[] = filled($job->briefing) ? '> '.str_replace("\n", "\n> ", trim($job->briefing)) : '(sem briefing: use o bom senso e o estilo)';
        $l[] = '';

        $l[] = '## Opções desta edição';
        $l[] = '- Legendas: '.($estilo['legendas'] && $job->opcao('legendas', true) ? 'sim' : 'não');
        $l[] = '- Cartão final: '.($job->opcao('cartao_final', true) ? 'sim (use os dados da identidade)' : 'não');
        if ($musica) {
            $l[] = sprintf('- Música: "%s" — %s, %s BPM%s.', $musica['nome'], Linha::formatar($musica['duracao'] ?? 0), $musica['bpm'] ?? '?', $musica['humor'] ? ", {$musica['humor']}" : '');
            if (! empty($musica['batidas'])) {
                $intervalo = count($musica['batidas']) > 1 ? round($musica['batidas'][1] - $musica['batidas'][0], 3) : null;
                $l[] = "  - Batidas a cada ~{$intervalo} s. Primeiras: ".implode(', ', array_slice($musica['batidas'], 0, 24)).' …';
            }
        } else {
            $l[] = '- Música: nenhuma escolhida (use `"musica": {"usar": false}`).';
        }
        $l[] = '';

        $l[] = '## Material (inventário)';
        $l[] = '| chave | arquivo enviado | papel | tipo | duração | orientação | HDR | som | trocas de cena (s) |';
        $l[] = '|---|---|---|---|---|---|---|---|---|';
        foreach ($inventario as $chave => $i) {
            $l[] = sprintf('| %s | %s | %s | %s | %s | %s | %s | %s | %s |',
                $chave, str_replace('|', '/', $i['nome']), $i['papel'], $i['tipo'],
                $i['tipo'] === 'imagem' ? 'foto' : number_format($i['duracao'], 2, ',', '').' s',
                $i['orientacao'] ?? '-', $i['hdr'] ? 'sim' : 'não',
                $i['tem_audio'] ? (isset($i['nivel_audio']) ? 'sim ('.$i['nivel_audio'].' dB)' : 'sim') : 'não',
                implode(', ', array_slice($i['cenas'] ?? [], 0, 15)) ?: '-');
        }
        $l[] = '';

        $l[] = '## Folhas de quadros (abra com Read)';
        $l[] = 'Cada folha tem até 12 miniaturas (4 colunas x 3 linhas, da esquerda para a direita), com a chave e o tempo de origem gravados embaixo.';
        foreach ($inventario as $chave => $i) {
            foreach ($i['folhas'] ?? [] as $f) {
                $l[] = "- `{$f['imagem']}` — {$chave}, quadros em ".implode(', ', $f['tempos']).' s';
            }
        }
        $l[] = '';

        $silencios = array_filter(array_map(fn ($i) => $i['silencios'] ?? [], $inventario));
        if ($silencios) {
            $l[] = '## Silêncios (início–fim, s) — ajudam a achar bordas de corte';
            foreach ($silencios as $chave => $lista) {
                $l[] = "- {$chave}: ".implode(' · ', array_map(fn ($s) => "{$s[0]}–{$s[1]}", array_slice($lista, 0, 60)));
            }
            $l[] = '';
        }

        return implode("\n", $l);
    }

    /** Tempo da prévia ↔ tempo de origem, e as falas já no tempo da prévia. */
    private function mapa(Linha $linha, array $palavras): string
    {
        $l = ['# Mapa da prévia atual', '', '| segmento | na prévia | origem | nota |', '|---|---|---|---|'];

        foreach ($linha->itens as $item) {
            $origem = $item['tipo'] === 'clip'
                ? sprintf('%s %.2f–%.2f s%s', $item['arquivo'], $item['inicio'], $item['fim'], abs($item['velocidade'] - 1) > 0.01 ? " (vel. {$item['velocidade']})" : '')
                : ($item['tipo'] === 'foto' ? "{$item['arquivo']} (foto)" : 'cartão');
            $l[] = sprintf('| %s | %s–%s | %s | %s |', $item['id'], Linha::formatar($item['seq_inicio']), Linha::formatar($item['seq_fim']), $origem, $item['nota'] ?? '');
        }

        $l[] = '';
        $l[] = '## Falas no tempo da prévia';

        foreach ($linha->itens as $item) {
            if ($item['tipo'] !== 'clip' || empty($palavras[$item['arquivo']])) {
                continue;
            }
            $texto = [];
            foreach ($palavras[$item['arquivo']] as $p) {
                $meio = ($p['i'] + $p['f']) / 2;
                if ($meio >= $item['inicio'] && $meio <= $item['fim']) {
                    $texto[] = $p['w'];
                }
            }
            if ($texto) {
                $l[] = sprintf('- [%s %s] %s', $item['id'], Linha::formatar($item['seq_inicio']), implode(' ', $texto));
            }
        }

        return implode("\n", $l);
    }
}
