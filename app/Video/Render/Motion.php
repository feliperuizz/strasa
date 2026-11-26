<?php

namespace App\Video\Render;

use App\Video\FalhaDeFerramenta;
use App\Video\Ferramentas;
use App\Video\Plano\Linha;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Motion design de verdade (scripts/video/motion): componentes animados
 * desenhados com Skia — janela de terminal digitando, contador gigante,
 * moldura REC, apps riscados, caixa de comentário, passo a passo, cards,
 * notificação, gráfico, comparação, seguir, busca e logo de marca.
 *
 * O Claude escolhe o componente e a palavra-gatilho no plano (tipo + em +
 * props); aqui os props são limpos (nunca vira código), os sons são
 * marcados e o Python gera um vídeo com transparência que a montagem sobrepõe.
 */
class Motion
{
    public const TIPOS = [
        'janela', 'contador', 'rec', 'pilula', 'apps', 'comentario', 'passos', 'cards',
        'notificacao', 'grafico', 'comparacao', 'seguir', 'busca', 'marca',
    ];

    /** Duração padrão (s) quando o plano não diz. */
    public const DURACAO = [
        'janela' => 4.5, 'contador' => 2.8, 'rec' => 3.0, 'pilula' => 2.5, 'apps' => 3.2, 'comentario' => 3.5,
        'passos' => 5.0, 'cards' => 4.0, 'notificacao' => 3.0, 'grafico' => 3.5, 'comparacao' => 4.0,
        'seguir' => 3.2, 'busca' => 3.5, 'marca' => 2.6,
    ];

    /** Onde cada um fica se o plano não disser (os cartões em cima, longe da legenda). */
    private const POSICAO = [
        'contador' => 'centro', 'comentario' => 'baixo', 'seguir' => 'centro', 'rec' => 'centro',
    ];

    public static function eh(string $tipo): bool
    {
        return in_array($tipo, self::TIPOS, true);
    }

    public static function python(): ?string
    {
        $bin = (string) config('video.motion.python');

        return $bin !== '' ? Ferramentas::localizar($bin) : null;
    }

    public static function pastaIcones(): string
    {
        return rtrim(str_replace('\\', '/', (string) config('video.motion.icones')), '/');
    }

    public static function disponivel(): bool
    {
        return self::python() && is_file(base_path('scripts/video/motion/render.py')) && is_dir(self::pastaIcones().'/lucide');
    }

    /** Lista de logos e ícones para o Claude procurar com Grep (gerada uma vez). */
    public static function indiceIcones(): ?string
    {
        $pasta = self::pastaIcones();
        $arquivo = "$pasta/indice.txt";

        if (is_file($arquivo) && filemtime($arquivo) >= (int) @filemtime("$pasta/marcas.json")) {
            return $arquivo;
        }

        if (! is_dir("$pasta/lucide")) {
            return null;
        }

        $l = ['# Ícones disponíveis no motor de motion', '# marca: <slug> — <nome da marca>   → use em "logo"', '# lucide: <nome>                   → use em "icone"', ''];
        foreach (json_decode((string) @file_get_contents("$pasta/marcas.json"), true) ?: [] as $m) {
            if (isset($m['slug']) && is_file("$pasta/marcas/{$m['slug']}.svg")) {
                $l[] = "marca: {$m['slug']} — {$m['title']}";
            }
        }
        foreach (glob("$pasta/lucide/*.svg") ?: [] as $f) {
            $l[] = 'lucide: '.basename($f, '.svg');
        }
        file_put_contents($arquivo, implode("\n", $l)."\n");

        return $arquivo;
    }

    /* Validação (chamada pelo Validador) -------------------------------- */

    /**
     * Completa o elemento normalizado com os campos do componente.
     *
     * @param  array  $el  como veio do plano
     * @param  array  $normal  já com id, tipo, em, inicio, duracao, texto, subtexto…
     * @param  callable  $texto  fn($valor, $max): string (limpeza do Validador)
     */
    public static function normalizar(array $el, array $normal, Linha $linha, callable $texto): ?array
    {
        $tipo = $normal['tipo'];
        $t = fn ($em) => ($v = $linha->resolver($em)) === null ? null : round($v, 3);
        $hex = fn ($v) => is_string($v) && preg_match('/^#[0-9a-f]{6}$/i', $v) ? strtoupper($v) : null;
        $slug = fn ($v) => is_string($v) && preg_match('/^[a-z0-9][a-z0-9.\-]{0,40}$/i', $v) ? strtolower($v) : null;
        $lista = fn ($v, $max) => array_slice(is_array($v) ? array_values($v) : [], 0, $max);
        $posicao = $el['posicao'] ?? null;

        if (is_array($posicao) && count($posicao) >= 2) {
            $normal['posicao'] = [max(0.1, min(0.9, (float) $posicao[0])), max(0.12, min(0.78, (float) $posicao[1]))];
        } elseif (! in_array($posicao, ['topo', 'centro', 'baixo', 'topo_esquerda', 'topo_direita', 'centro_esquerda', 'centro_direita', 'baixo_esquerda', 'baixo_direita'], true)) {
            $normal['posicao'] = self::POSICAO[$tipo] ?? 'topo';
        }

        // "logo": o nome da marca como se escreve ("CapCut", "Adobe Premiere Pro") ou o slug do
        // Simple Icons; o servidor acha o logo (App\Video\Logos). "site": domínio, para empresas.
        $nomeMarca = fn ($v) => is_string($v) && preg_match("/^[\p{L}\p{N}][\p{L}\p{N} .&+'’\-]{0,39}$/u", trim($v)) ? trim($v) : null;
        $dominio = fn ($v) => is_string($v) && preg_match('#^(https?://)?([a-z0-9-]+\.)+[a-z]{2,}(/.*)?$#i', trim($v)) ? strtolower(trim($v)) : null;
        $marca = fn (array $i) => array_filter([
            'logo' => $nomeMarca($i['logo'] ?? null), 'site' => $dominio($i['site'] ?? null), 'sigla' => $texto($i['sigla'] ?? '', 4) ?: null,
            'fundo' => $hex($i['fundo'] ?? null), 'cor' => $hex($i['cor'] ?? null),
        ], fn ($v) => $v !== null);

        switch ($tipo) {
            case 'janela':
                $normal['titulo'] = $texto($el['titulo'] ?? '', 50) ?: 'claude — ~/videos/reel';
                $normal['linhas'] = array_values(array_filter(array_map(fn ($l) => is_array($l) ? array_filter([
                    'tipo' => in_array($l['tipo'] ?? '', ['comando', 'progresso', 'ok', 'texto'], true) ? $l['tipo'] : 'texto',
                    'texto' => $texto($l['texto'] ?? '', 40),
                    't' => isset($l['em']) ? $t($l['em']) : null, 'em' => $l['em'] ?? null,
                    'duracao' => isset($l['duracao']) ? max(0.3, min(6, (float) $l['duracao'])) : null,
                ], fn ($v) => $v !== null) : null, $lista($el['linhas'] ?? [], 6))));
                if (! $normal['linhas']) {
                    return null;
                }
                // Linha sem palavra-gatilho entra depois da anterior (mesma conta do motor).
                $tl = $normal['inicio'] + 0.35;
                foreach ($normal['linhas'] as &$l) {
                    $tl = $l['t'] ?? $tl;
                    $l['t'] = round($tl, 3);
                    $tl += ($l['duracao'] ?? match ($l['tipo']) {
                        'comando' => mb_strlen($l['texto'] ?? '') / 24 + 0.25, 'progresso' => 1.5, default => 0.4,
                    }) + 0.15;
                }
                unset($l);
                break;

            case 'contador':
                $normal['de'] = (float) ($el['de'] ?? 0);
                $normal['ate'] = (float) ($el['ate'] ?? 100);
                $normal['prefixo'] = $texto($el['prefixo'] ?? '', 6);
                $normal['sufixo'] = $texto($el['sufixo'] ?? '', 6);
                $normal['decimais'] = max(0, min(2, (int) ($el['decimais'] ?? 0)));
                $normal['fundo'] = ($el['fundo'] ?? 'escuro') === 'nenhum' ? 'nenhum' : 'escuro';
                break;

            case 'pilula':
                if ($normal['texto'] === '') {
                    return null;
                }
                $normal['texto'] = mb_substr($normal['texto'], 0, 28);
                break;

            case 'apps':
                $normal['itens'] = array_values(array_filter(array_map(fn ($i) => is_array($i) ? array_merge($marca($i), array_filter([
                    'nome' => $texto($i['nome'] ?? '', 18), 't' => isset($i['em']) ? $t($i['em']) : null, 'em' => $i['em'] ?? null,
                ], fn ($v) => $v !== null)) : null, $lista($el['itens'] ?? [], 4))));
                $normal['acao'] = in_array($el['acao'] ?? '', ['riscar', 'check'], true) ? $el['acao'] : 'nenhum';
                $normal['t_acao'] = isset($el['em_acao']) ? $t($el['em_acao']) : null;
                $normal['em_acao'] = $el['em_acao'] ?? null;
                if (! $normal['itens']) {
                    return null;
                }
                break;

            case 'comentario':
                $normal['texto'] = mb_substr($normal['texto'] ?: 'EU QUERO', 0, 22);
                $normal['rotulo'] = $texto($el['rotulo'] ?? 'comente', 18);
                break;

            case 'passos':
                $normal['texto'] = mb_substr($normal['texto'] ?: 'Passo a passo', 0, 28);
                $normal['itens'] = array_values(array_filter(array_map(fn ($i) => ($tx = $texto(is_array($i) ? ($i['texto'] ?? '') : $i, 34)) !== '' ? array_filter([
                    'texto' => $tx, 't' => is_array($i) && isset($i['em']) ? $t($i['em']) : null, 'em' => is_array($i) ? ($i['em'] ?? null) : null,
                ], fn ($v) => $v !== null) : null, $lista($el['itens'] ?? [], 5))));
                $normal['codigo'] = array_values(array_filter(array_map(fn ($c) => $texto($c, 34), $lista($el['codigo'] ?? [], 3))));
                if (! $normal['itens']) {
                    return null;
                }
                break;

            case 'cards':
                $normal['itens'] = array_values(array_filter(array_map(fn ($i) => is_array($i) ? array_merge($marca($i), array_filter([
                    'icone' => $slug($i['icone'] ?? null), 'texto' => $texto($i['texto'] ?? '', 26),
                    't' => isset($i['em']) ? $t($i['em']) : null, 'em' => $i['em'] ?? null,
                ], fn ($v) => $v !== null)) : null, $lista($el['itens'] ?? [], 4))));
                $normal['destaque'] = isset($el['destaque']) && is_numeric($el['destaque']) ? max(0, min(count($normal['itens']) - 1, (int) $el['destaque'])) : null;
                if (! $normal['itens']) {
                    return null;
                }
                break;

            case 'notificacao':
                $normal = array_merge($normal, $marca($el));
                $normal['app'] = $texto($el['app'] ?? '', 20);
                $normal['quando'] = $texto($el['quando'] ?? 'agora', 12);
                $normal['texto'] = mb_substr($normal['texto'], 0, 60);
                $normal['subtexto'] = $texto($el['subtexto'] ?? '', 90);
                if ($normal['texto'] === '') {
                    return null;
                }
                break;

            case 'grafico':
                $normal['sufixo'] = $texto($el['sufixo'] ?? '', 6);
                $normal['itens'] = array_values(array_filter(array_map(fn ($i) => is_array($i) && is_numeric($i['valor'] ?? null) ? array_filter([
                    'rotulo' => $texto($i['rotulo'] ?? '', 24), 'valor' => max(0, (float) $i['valor']),
                    't' => isset($i['em']) ? $t($i['em']) : null, 'em' => $i['em'] ?? null,
                ], fn ($v) => $v !== null) : null, $lista($el['itens'] ?? [], 5))));
                if (! $normal['itens']) {
                    return null;
                }
                break;

            case 'comparacao':
                foreach (['esquerda', 'direita'] as $lado) {
                    $b = is_array($el[$lado] ?? null) ? $el[$lado] : [];
                    $normal[$lado] = [
                        'titulo' => $texto($b['titulo'] ?? '', 18),
                        'itens' => array_values(array_filter(array_map(fn ($i) => ($tx = $texto(is_array($i) ? ($i['texto'] ?? '') : $i, 40)) !== '' ? array_filter([
                            'texto' => $tx, 't' => is_array($i) && isset($i['em']) ? $t($i['em']) : null, 'em' => is_array($i) ? ($i['em'] ?? null) : null,
                        ], fn ($v) => $v !== null) : null, $lista($b['itens'] ?? [], 4)))),
                    ];
                }
                if (! $normal['esquerda']['itens'] && ! $normal['direita']['itens']) {
                    return null;
                }
                break;

            case 'seguir':
                $normal['texto'] = mb_substr($normal['texto'], 0, 30);
                $normal['subtexto'] = mb_substr($normal['subtexto'], 0, 40);
                break;

            case 'busca':
                $normal['texto'] = mb_substr($normal['texto'], 0, 48);
                $normal['tema'] = ($el['tema'] ?? '') === 'claro' ? 'claro' : 'escuro';
                if ($normal['texto'] === '') {
                    return null;
                }
                break;

            case 'marca':
                $normal = array_merge($normal, $marca($el));
                $normal['texto'] = mb_substr($normal['texto'], 0, 30);
                if (empty($normal['logo']) && empty($normal['sigla'])) {
                    return null;
                }
                break;
        }

        return $normal;
    }

    /* Som: o que se vê, se ouve ------------------------------------------- */

    public static function sons(array $el): array
    {
        if (($el['som'] ?? null) === 'nenhum') {
            return [];
        }

        $i = $el['inicio'];
        $s = fn ($t, $som, $forca = 0.6) => ['t' => round($t, 3), 'som' => $som, 'forca' => $forca];
        $itens = $el['itens'] ?? [];

        $lista = match ($el['tipo']) {
            'janela' => array_merge([$s($i, 'swoosh', 0.5)], array_map(fn ($l) => $s($l['t'] ?? $i + 0.4, match ($l['tipo']) {
                'ok' => 'ding', 'comando' => 'clique', default => 'tique',
            }, 0.5), array_filter($el['linhas'], fn ($l) => isset($l['t'])))),
            'contador' => [$s($i, 'riser', 0.5), $s($i + min(1.8, max(0.6, $el['duracao'] - 0.9)) + 0.1, 'impacto', 0.8)],
            'rec' => [$s($i, 'clique', 0.6)],
            'pilula' => [$s($i, 'pop', 0.55)],
            'apps' => array_merge(
                array_map(fn ($k) => $s($itens[$k]['t'] ?? $i + $k * 0.14, 'pop', 0.55), array_keys($itens)),
                $el['acao'] !== 'nenhum' ? [$s($el['t_acao'] ?? $i + 0.6 + count($itens) * 0.14, $el['acao'] === 'riscar' ? 'whoosh' : 'ding', 0.6)] : []
            ),
            'comentario' => [$s($i, 'pop', 0.5), $s($i + 0.7 + mb_strlen($el['texto']) / 11, 'ding', 0.65)],
            'passos', 'grafico' => array_merge([$s($i, 'whoosh', 0.5)], array_map(fn ($k) => $s($itens[$k]['t'] ?? $i + 0.4 + $k * 0.45, 'pop', 0.5), array_keys($itens))),
            'cards' => array_merge([$s($i, 'whoosh', 0.5)], array_map(fn ($it) => $s($it['t'], 'tique', 0.5), array_filter($itens, fn ($it) => isset($it['t'])))),
            'notificacao' => [$s($i + 0.1, 'ding', 0.7)],
            'comparacao' => [$s($i, 'whoosh', 0.55)],
            'seguir' => [$s($i, 'pop', 0.5), $s($i + min(1.5, $el['duracao'] * 0.45), 'clique', 0.7)],
            'busca' => [$s($i, 'whoosh', 0.45), $s($i + 0.35 + mb_strlen($el['texto']) / 16 + 0.3, 'clique', 0.55)],
            'marca' => [$s($i, 'impacto', 0.7)],
            default => [],
        };

        return array_values(array_filter($lista, fn ($x) => $x['t'] >= 0));
    }

    /** Componentes que já mostram o que é dito: a legenda some enquanto estão na tela. */
    public static function ocultamLegenda(array $elementos): array
    {
        return array_values(array_map(fn ($e) => [$e['inicio'], $e['inicio'] + $e['duracao']], array_filter($elementos,
            fn ($e) => ($e['tipo'] === 'contador' && ($e['fundo'] ?? 'escuro') === 'escuro') || $e['tipo'] === 'comentario')));
    }

    /* Render -------------------------------------------------------------- */

    /**
     * Gera o vídeo com transparência de todos os componentes (com cache).
     *
     * @return string caminho do .mov (QuickTime RLE ARGB, alfa pré-multiplicado)
     */
    public static function renderizar(array $elementos, array $id, int $largura, int $altura, float $escala, float $total, string $pasta, ?string $logo, array $arquivosDeFonte): string
    {
        if (! self::disponivel()) {
            throw new FalhaDeFerramenta('O motor de motion não está instalado neste servidor (rode o instalador do estúdio de novo).');
        }

        $pedido = [
            'largura' => $largura, 'altura' => $altura, 'escala' => round($escala, 4), 'fps' => 30, 'duracao' => round($total, 3),
            'identidade' => ['destaque' => $id['destaque'], 'texto' => $id['texto'], 'primaria' => $id['primaria'], 'fundo' => $id['fundo']],
            'fontes' => self::fontes($id, $arquivosDeFonte),
            'icones' => self::pastaIcones(),
            'logo' => $logo,
            'elementos' => array_values($elementos),
            'threads' => (int) Ferramentas::threads(),
            'ffmpeg' => Ferramentas::ffmpegBin(),
        ];

        // Cache por conteúdo + versão do motor: num ajuste que não mexe nos componentes, nada é refeito.
        $versao = implode('|', array_map(fn ($f) => @filemtime($f), glob(base_path('scripts/video/motion/*.py')) ?: []));
        $chave = md5(json_encode([$pedido, $versao]));
        File::ensureDirectoryExists("$pasta/cache", 0775);
        $saida = "$pasta/cache/motion_$chave.mov";

        if (is_file($saida) && filesize($saida) > 1000) {
            return $saida;
        }

        $pedido['saida'] = $saida.'.parcial.mov';
        $arquivoPedido = "$pasta/cache/motion_$chave.json";
        file_put_contents($arquivoPedido, json_encode($pedido, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        try {
            Ferramentas::rodar(
                [self::python(), '-I', base_path('scripts/video/motion/render.py'), $arquivoPedido],
                $pasta, (int) config('video.limites.ffmpeg_timeout', 3000), null,
                array_merge(['PYTHONIOENCODING' => 'utf-8'], array_fill_keys(['OMP_NUM_THREADS', 'OPENBLAS_NUM_THREADS', 'MKL_NUM_THREADS'], Ferramentas::threads()))
            );
        } finally {
            @unlink($arquivoPedido);
        }

        if (! is_file($pedido['saida'])) {
            throw new FalhaDeFerramenta('O motor de motion terminou sem gerar o vídeo.');
        }
        rename($pedido['saida'], $saida);

        return $saida;
    }

    /**
     * Fontes do motor: títulos grandes na fonte da marca; a interface dos
     * cartões em Inter (lê bem pequena e combina com qualquer marca); código em mono.
     */
    public static function fontes(array $id, array $arquivos): array
    {
        $achar = function (string $familia, array $pesos) use ($arquivos): ?string {
            $slug = Str::slug($familia);
            foreach ($pesos as $peso) {
                foreach ($arquivos as $a) {
                    if (strtolower(basename($a)) === "{$slug}-{$peso}.ttf") {
                        return $a;
                    }
                }
            }

            return null;
        };

        // Fonte própria do cliente (enviada na identidade) não segue o padrão de nome.
        $propria = collect($arquivos)->first(fn ($a) => ! preg_match('/^[a-z0-9\-]+-\d{3}\.ttf$/', strtolower(basename($a))));
        $titulo = $achar($id['fonte_titulo'] ?? 'Montserrat', [800, 700, 400]) ?? $propria ?? $achar('Inter', [800, 700]);

        return array_filter([
            'titulo' => $titulo,
            'forte' => $achar('Inter', [700, 800]),
            'texto' => $achar('Inter', [600, 400]),
            'mono' => $achar('JetBrains Mono', [400, 700]),
        ]);
    }
}
