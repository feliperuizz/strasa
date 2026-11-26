<?php

namespace App\Video\Plano;

use App\Video\Render\Motion;

/**
 * Confere e normaliza o plano de edição (plano.json) antes do render.
 *
 * O plano vem do Claude (ou do piloto automático, ou de um ajuste manual na
 * tela) e NUNCA é executado como código: aqui ele vira dados limpos, com
 * limites. O que dá para consertar é consertado e anotado em "avisos"
 * (aparecem para a equipe); o que não dá vira PlanoInvalido com a lista de
 * problemas — que volta para o Claude corrigir.
 *
 * Esquema completo, com exemplos: resources/video/plano.md
 */
class Validador
{
    public const TRANSICOES = ['corte', 'fade', 'flash', 'zoom', 'deslizar', 'wipe', 'desfoque', 'revelar', 'circulo', 'preto'];

    public const TIPOS_ELEMENTO = [
        'titulo', 'texto', 'palavra', 'lista', 'etiqueta', 'numero', 'preco', 'nome', 'local',
        'sticker', 'tela_cheia', 'flash', 'citacao', 'estrelas',
        // Motor de motion (App\Video\Render\Motion::TIPOS)
        'janela', 'contador', 'rec', 'pilula', 'apps', 'comentario', 'passos', 'cards',
        'notificacao', 'grafico', 'comparacao', 'seguir', 'busca', 'marca',
    ];

    public const FORMAS = ['seta', 'circulo', 'check', 'x', 'estrela', 'brilho', 'coracao', 'raio', 'balao', 'sublinhado', 'fogo'];

    public const POSICOES = ['topo', 'centro', 'baixo', 'topo_esquerda', 'topo_direita', 'baixo_esquerda', 'baixo_direita', 'esquerda', 'direita'];

    public const ANIMACOES = ['pop', 'deslizar', 'digitar', 'fade', 'subir', 'zoom'];

    public const SONS = ['whoosh', 'swoosh', 'pop', 'clique', 'impacto', 'ding', 'riser', 'glitch', 'tique', 'nenhum'];

    private array $avisos = [];

    private array $erros = [];

    /**
     * @param  array  $arquivos  chave (f12) => [tipo, duracao, tem_audio, papel, largura, altura]
     * @param  array  $ctx  estilo, duracao_max, tem_musica, fala
     */
    public function normalizar(array $plano, array $arquivos, array $ctx = []): array
    {
        $this->avisos = [];
        $this->erros = [];
        $duracaoMax = (float) ($ctx['duracao_max'] ?? 180);

        $segmentos = $this->segmentos($plano['segmentos'] ?? [], $arquivos, (bool) ($ctx['fala'] ?? false));

        if (! $segmentos) {
            $this->erros[] = 'O plano não tem nenhum segmento válido. Cada segmento precisa de "arquivo" (uma das chaves listadas, ex.: f12) e "inicio"/"fim" dentro da duração do arquivo.';
        }

        $cartao = $this->cartaoFinal($plano['cartao_final'] ?? null);

        // Cartão no fim da lista + cartão final = dois fundos vazios seguidos.
        while ($cartao['ativo'] && count($segmentos) > 1 && end($segmentos)['tipo'] === 'cartao') {
            array_pop($segmentos);
            $this->avisos[] = 'Tirei o segmento "cartao" do fim: o cartão final já entra sozinho (cartao_final).';
        }

        if ($cartao['ativo'] && $segmentos) {
            $segmentos[] = [
                'id' => 'fim', 'tipo' => 'cartao', 'arquivo' => 'cartao', 'duracao' => $cartao['duracao'],
                'velocidade' => 1.0, 'camera' => ['zoom' => [1.0, 1.0], 'foco' => [0.5, 0.5], 'punches' => []],
                'transicao' => $cartao['transicao'], 'transicao_duracao' => 0.4, 'audio' => false,
                'papel' => null, 'nota' => 'cartão final',
            ];
        }

        if ($this->erros) {
            throw new PlanoInvalido($this->erros);
        }

        $linha = new Linha($segmentos);

        if ($linha->total > $duracaoMax) {
            $this->erros[] = sprintf('O vídeo ficou com %.1f s; o máximo para este formato é %d s. Corte segmentos.', $linha->total, $duracaoMax);
            throw new PlanoInvalido($this->erros);
        }

        $normal = [
            'versao_esquema' => 1,
            'titulo' => $this->texto($plano['titulo'] ?? '', 120),
            'resumo' => $this->texto($plano['resumo'] ?? '', 1200),
            'gancho' => $this->texto($plano['gancho'] ?? '', 300),
            'segmentos' => $segmentos,
            'elementos' => $this->elementos($plano['elementos'] ?? [], $linha),
            'legendas' => $this->legendas($plano['legendas'] ?? [], $ctx),
            'musica' => $this->musica($plano['musica'] ?? [], $ctx),
            'narracao' => $this->narracao($plano['narracao'] ?? null, $linha),
            'cartao_final' => $cartao,
            'capa' => $this->capa($plano['capa'] ?? null, $arquivos),
            'legenda_post' => $this->texto($plano['legenda_post'] ?? '', 2200),
            'aprendizados' => array_values(array_filter(array_map(fn ($a) => $this->texto($a, 300), array_slice((array) ($plano['aprendizados'] ?? []), 0, 10)))),
            'avisos' => array_values(array_filter(array_map(fn ($a) => $this->texto($a, 300), array_slice((array) ($plano['avisos'] ?? []), 0, 20)))),
        ];

        $normal['correcoes_automaticas'] = array_values(array_unique($this->avisos));

        return $normal;
    }

    public function avisos(): array
    {
        return $this->avisos;
    }

    /* Segmentos -------------------------------------------------------- */

    private function segmentos(mixed $lista, array $arquivos, bool $fala = true): array
    {
        $saida = [];
        $ids = [];

        foreach (array_slice(is_array($lista) ? $lista : [], 0, 300) as $n => $seg) {
            if (! is_array($seg)) {
                continue;
            }

            $id = $this->id($seg['id'] ?? null, 's', $n + 1, $ids);
            $chave = (string) ($seg['arquivo'] ?? '');
            $rotulo = "Segmento {$id}";

            if ($chave === 'cartao') {
                $saida[] = array_merge($this->base($seg, $id), ['tipo' => 'cartao', 'arquivo' => 'cartao', 'duracao' => $this->entre((float) ($seg['duracao'] ?? 2), 0.5, 8)]);

                continue;
            }

            if (! isset($arquivos[$chave])) {
                $this->erros[] = "{$rotulo}: o arquivo \"{$chave}\" não existe. Use uma das chaves: ".implode(', ', array_keys($arquivos)).'.';

                continue;
            }

            $info = $arquivos[$chave];

            if ($info['tipo'] === 'audio') {
                $this->erros[] = "{$rotulo}: \"{$chave}\" é só áudio, não pode ser segmento de imagem.";

                continue;
            }

            if ($info['tipo'] === 'imagem') {
                $saida[] = array_merge($this->base($seg, $id), [
                    'tipo' => 'foto', 'arquivo' => $chave,
                    'duracao' => $this->entre((float) ($seg['duracao'] ?? (isset($seg['fim'], $seg['inicio']) ? $seg['fim'] - $seg['inicio'] : 2.5)), 0.4, 10),
                    'audio' => false,
                ]);

                continue;
            }

            $dur = (float) $info['duracao'];
            $inicio = (float) ($seg['inicio'] ?? 0);
            $fim = (float) ($seg['fim'] ?? $dur);

            if ($inicio < 0 || $fim > $dur + 0.05) {
                $this->avisos[] = "{$rotulo}: tempo fora do arquivo {$chave} (0–".round($dur, 2).' s), ajustado.';
            }

            $inicio = $this->entre($inicio, 0, max(0, $dur - 0.1));
            $fim = $this->entre($fim, 0, $dur);

            if ($fim - $inicio < 0.25) {
                $this->avisos[] = "{$rotulo}: curto demais (".round($fim - $inicio, 2).' s), removido.';

                continue;
            }

            $velocidade = $this->entre((float) ($seg['velocidade'] ?? 1.0), 0.25, 4.0);
            $audio = array_key_exists('audio', $seg) ? (bool) $seg['audio'] : ($fala && (bool) $info['tem_audio']);

            if ($audio && ! $info['tem_audio']) {
                $audio = false;
            }

            if ($audio && abs($velocidade - 1.0) > 0.01) {
                // Fala acelerada/lenta soa errado; câmera lenta é para imagem.
                $this->avisos[] = "{$rotulo}: segmento com som original não muda de velocidade; som desligado neste trecho.";
                $audio = false;
            }

            $saida[] = array_merge($this->base($seg, $id, $inicio, $fim), [
                'tipo' => 'clip', 'arquivo' => $chave,
                'inicio' => round($inicio, 3), 'fim' => round($fim, 3),
                'velocidade' => $velocidade, 'audio' => $audio,
            ]);
        }

        return $saida;
    }

    private function base(array $seg, string $id, ?float $inicio = null, ?float $fim = null): array
    {
        $camera = is_array($seg['camera'] ?? null) ? $seg['camera'] : [];
        $zoom = $camera['zoom'] ?? [1.0, 1.0];
        $zoom = is_array($zoom) ? array_values($zoom) : [(float) $zoom, (float) $zoom];
        $foco = is_array($camera['foco'] ?? null) ? array_values($camera['foco']) : [0.5, 0.5];

        $punches = [];
        foreach (array_slice((array) ($camera['punches'] ?? []), 0, 20) as $p) {
            if (! is_array($p) || ! isset($p['t'])) {
                continue;
            }
            $t = (float) $p['t'];
            if ($inicio !== null && ($t < $inicio || $t > $fim)) {
                continue;
            }
            $punches[] = ['t' => round($t, 3), 'forca' => $this->entre((float) ($p['forca'] ?? 0.08), 0.02, 0.2)];
        }

        $transicao = (string) ($seg['transicao'] ?? 'corte');
        if (! in_array($transicao, self::TRANSICOES, true)) {
            $this->avisos[] = "Segmento {$id}: transição \"{$transicao}\" não existe, usei corte seco.";
            $transicao = 'corte';
        }

        return [
            'id' => $id,
            'velocidade' => 1.0,
            'camera' => [
                'zoom' => [$this->entre((float) ($zoom[0] ?? 1), 1.0, 1.8), $this->entre((float) ($zoom[1] ?? $zoom[0] ?? 1), 1.0, 1.8)],
                'foco' => [$this->entre((float) ($foco[0] ?? 0.5), 0, 1), $this->entre((float) ($foco[1] ?? 0.5), 0, 1)],
                'punches' => $punches,
            ],
            'transicao' => $transicao,
            'transicao_duracao' => $this->entre((float) ($seg['transicao_duracao'] ?? ($transicao === 'flash' ? 0.2 : 0.35)), 0.1, 1.0),
            'audio' => false,
            'papel' => isset($seg['papel']) ? $this->texto($seg['papel'], 20) : null,
            'nota' => $this->texto($seg['nota'] ?? '', 160),
        ];
    }

    /* Elementos (animações por cima) ------------------------------------ */

    private function elementos(mixed $lista, Linha $linha): array
    {
        $saida = [];
        $ids = [];

        foreach (array_slice(is_array($lista) ? $lista : [], 0, 120) as $n => $el) {
            if (! is_array($el)) {
                continue;
            }

            $id = $this->id($el['id'] ?? null, 'mo', $n + 1, $ids);
            $tipo = (string) ($el['tipo'] ?? '');

            if (! in_array($tipo, self::TIPOS_ELEMENTO, true)) {
                $this->avisos[] = "{$id}: tipo \"{$tipo}\" desconhecido, ignorado.";

                continue;
            }

            $duracao = $this->entre((float) ($el['duracao'] ?? ($tipo === 'flash' ? 0.2 : (Motion::DURACAO[$tipo] ?? 2.5))), 0.15, 20);
            $inicio = $linha->resolver($el['em'] ?? null, $duracao);

            if ($inicio === null) {
                $this->avisos[] = "{$id}: o momento indicado caiu num trecho cortado ou não existe; o elemento ficou de fora.";

                continue;
            }

            $duracao = min($duracao, max(0.15, $linha->total - $inicio));

            $normal = [
                'id' => $id,
                'tipo' => $tipo,
                'em' => $el['em'],
                'inicio' => round($inicio, 3),
                'duracao' => round($duracao, 3),
                'texto' => $this->texto($el['texto'] ?? '', 140),
                'subtexto' => $this->texto($el['subtexto'] ?? '', 100),
                'posicao' => $this->posicao($el['posicao'] ?? null, $tipo),
                'animacao' => in_array($el['animacao'] ?? null, self::ANIMACOES, true) ? $el['animacao'] : 'pop',
                'destaque' => array_values(array_filter(array_map(fn ($d) => $this->texto($d, 40), array_slice((array) ($el['destaque'] ?? []), 0, 6)))),
                'cor' => in_array($el['cor'] ?? null, ['destaque', 'primaria', 'texto', 'fundo'], true) ? $el['cor'] : null,
                'som' => in_array($el['som'] ?? null, self::SONS, true) ? $el['som'] : null,
                'tamanho' => in_array($el['tamanho'] ?? null, ['p', 'm', 'g'], true) ? $el['tamanho'] : 'm',
            ];

            // Componentes do motor de motion: cada um tem os próprios campos.
            if (Motion::eh($tipo)) {
                unset($normal['cor'], $normal['destaque'], $normal['animacao']);
                $normal = Motion::normalizar($el, $normal, $linha, fn ($v, $max) => $this->texto($v, $max));
                if ($normal === null) {
                    $this->avisos[] = "{$id}: {$tipo} sem o conteúdo que precisa (itens/texto/logo), ignorado.";

                    continue;
                }
                $saida[] = $normal;

                continue;
            }

            if ($tipo === 'lista') {
                $itens = [];
                foreach (array_slice((array) ($el['itens'] ?? []), 0, 6) as $k => $item) {
                    $textoItem = is_array($item) ? ($item['texto'] ?? '') : $item;
                    $em = is_array($item) && isset($item['em']) ? $linha->resolver($item['em']) : null;
                    $itens[] = [
                        'texto' => $this->texto($textoItem, 60),
                        'inicio' => round($em !== null ? max($inicio, $em) : $inicio + $k * min(0.6, $duracao / max(1, count((array) $el['itens']) + 1)), 3),
                    ];
                }
                $normal['itens'] = array_values(array_filter($itens, fn ($i) => $i['texto'] !== ''));
                if (! $normal['itens']) {
                    $this->avisos[] = "{$id}: lista sem itens, ignorada.";

                    continue;
                }
            }

            if ($tipo === 'tela_cheia' && ! empty($el['itens'])) {
                $normal['itens'] = array_values(array_filter(array_map(fn ($i) => ['texto' => $this->texto(is_array($i) ? ($i['texto'] ?? '') : $i, 60), 'inicio' => $inicio], array_slice((array) $el['itens'], 0, 5)), fn ($i) => $i['texto'] !== ''));
            }

            if ($tipo === 'numero') {
                $normal['de'] = (float) ($el['de'] ?? 0);
                $normal['ate'] = (float) ($el['ate'] ?? 0);
                $normal['prefixo'] = $this->texto($el['prefixo'] ?? '', 10);
                $normal['sufixo'] = $this->texto($el['sufixo'] ?? '', 10);
                $normal['decimais'] = (int) $this->entre((float) ($el['decimais'] ?? 0), 0, 2);
            }

            if ($tipo === 'preco') {
                $normal['antes'] = $this->texto($el['antes'] ?? '', 30);
            }

            if ($tipo === 'citacao') {
                $normal['autor'] = $this->texto($el['autor'] ?? ($el['subtexto'] ?? ''), 80);
            }

            if ($tipo === 'sticker') {
                $forma = (string) ($el['forma'] ?? 'brilho');
                $normal['forma'] = in_array($forma, self::FORMAS, true) ? $forma : 'brilho';
                $normal['angulo'] = (float) $this->entre((float) ($el['angulo'] ?? 0), -360, 360);
                $normal['xy'] = $this->xy($el['posicao'] ?? null);
            }

            if (in_array($tipo, ['titulo', 'texto', 'palavra', 'etiqueta', 'preco', 'nome', 'local', 'citacao'], true) && $normal['texto'] === '') {
                $this->avisos[] = "{$id}: {$tipo} sem texto, ignorado.";

                continue;
            }

            $saida[] = $normal;
        }

        usort($saida, fn ($a, $b) => $a['inicio'] <=> $b['inicio']);

        return $saida;
    }

    private function posicao(mixed $posicao, string $tipo): string
    {
        if (is_string($posicao) && in_array($posicao, self::POSICOES, true)) {
            return $posicao;
        }

        return match ($tipo) {
            'nome', 'local' => 'baixo_esquerda',
            'lista', 'titulo', 'etiqueta' => 'topo',
            default => 'centro',
        };
    }

    /** Sticker aceita [x, y] em 0–1 do quadro, além das posições com nome. */
    private function xy(mixed $posicao): array
    {
        if (is_array($posicao) && count($posicao) >= 2) {
            return [$this->entre((float) $posicao[0], 0.05, 0.95), $this->entre((float) $posicao[1], 0.05, 0.95)];
        }

        return match ($posicao) {
            'topo' => [0.5, 0.18], 'baixo' => [0.5, 0.72], 'esquerda' => [0.2, 0.45], 'direita' => [0.78, 0.45],
            'topo_esquerda' => [0.2, 0.18], 'topo_direita' => [0.78, 0.18],
            'baixo_esquerda' => [0.2, 0.7], 'baixo_direita' => [0.78, 0.7],
            default => [0.5, 0.45],
        };
    }

    /* Blocos simples ---------------------------------------------------- */

    private function legendas(mixed $bloco, array $ctx): array
    {
        $bloco = is_array($bloco) ? $bloco : [];
        $correcoes = [];

        foreach (array_slice((array) ($bloco['correcoes'] ?? []), 0, 100, true) as $de => $para) {
            if (is_string($de) && is_string($para) && trim($de) !== '') {
                $correcoes[mb_substr(trim($de), 0, 60)] = mb_substr(trim($para), 0, 60);
            }
        }

        return [
            'ativo' => (bool) ($bloco['ativo'] ?? (($ctx['fala'] ?? false) || ($ctx['narracao'] ?? false))),
            'correcoes' => $correcoes,
            'destaques' => array_values(array_filter(array_map(fn ($d) => $this->texto($d, 40), array_slice((array) ($bloco['destaques'] ?? []), 0, 60)))),
            'estilo' => isset($bloco['estilo']) && in_array($bloco['estilo'], ['karaoke', 'pop', 'caixa', 'simples'], true) ? $bloco['estilo'] : null,
            'posicao' => isset($bloco['posicao']) && in_array($bloco['posicao'], ['baixo', 'centro', 'alto'], true) ? $bloco['posicao'] : null,
            'tamanho' => $this->entre((float) ($bloco['tamanho'] ?? 1.0), 0.6, 1.6),
        ];
    }

    private function musica(mixed $bloco, array $ctx): array
    {
        $bloco = is_array($bloco) ? $bloco : [];
        $tem = (bool) ($ctx['tem_musica'] ?? false);

        return [
            'usar' => $tem && (bool) ($bloco['usar'] ?? true),
            'inicio' => $this->entre((float) ($bloco['inicio'] ?? 0), 0, 3600),
            'volume' => $this->entre((float) ($bloco['volume'] ?? (($ctx['fala'] ?? false) || ($ctx['narracao'] ?? false) ? 0.18 : 0.85)), 0.02, 1.0),
            'sincronizar_cortes' => (bool) ($bloco['sincronizar_cortes'] ?? ! ($ctx['fala'] ?? false)),
            'fade_in' => $this->entre((float) ($bloco['fade_in'] ?? 0.3), 0, 5),
            'fade_out' => $this->entre((float) ($bloco['fade_out'] ?? 1.2), 0, 8),
        ];
    }

    /** Falas da voz gerada. O áudio sai depois (App\Video\Narracao); aqui só texto e âncora. */
    private function narracao(mixed $bloco, Linha $linha): array
    {
        $bloco = is_array($bloco) ? $bloco : [];
        $falas = [];
        $ids = [];

        foreach (array_slice((array) ($bloco['falas'] ?? []), 0, 40) as $n => $f) {
            if (! is_array($f)) {
                continue;
            }
            $id = $this->id($f['id'] ?? null, 'n', $n + 1, $ids);
            $texto = $this->texto($f['texto'] ?? '', 500);

            if ($texto === '') {
                continue;
            }

            $em = $f['em'] ?? null;
            if ($linha->resolver($em) === null) {
                // Sem momento válido: entra logo depois da fala anterior.
                $this->avisos[] = "Narração {$id}: momento inválido ou num trecho cortado; entra depois da fala anterior.";
                $em = $falas ? end($falas)['em'] : 'inicio';
            }

            $falas[] = ['id' => $id, 'em' => $em, 'texto' => $texto];
        }

        return [
            'ativo' => $falas && (bool) ($bloco['ativo'] ?? true),
            'velocidade' => $this->entre((float) ($bloco['velocidade'] ?? 1.0), 0.8, 1.3),
            'volume' => $this->entre((float) ($bloco['volume'] ?? 1.0), 0.3, 1.5),
            'falas' => $falas,
        ];
    }

    private function cartaoFinal(mixed $bloco): array
    {
        $bloco = is_array($bloco) ? $bloco : [];
        $transicao = (string) ($bloco['transicao'] ?? 'fade');

        return [
            'ativo' => (bool) ($bloco['ativo'] ?? false),
            'duracao' => $this->entre((float) ($bloco['duracao'] ?? 2.5), 1.0, 6.0),
            'titulo' => $this->texto($bloco['titulo'] ?? '', 60),
            'linhas' => array_values(array_filter(array_map(fn ($l) => $this->texto($l, 60), array_slice((array) ($bloco['linhas'] ?? []), 0, 4)))),
            'transicao' => in_array($transicao, self::TRANSICOES, true) ? $transicao : 'fade',
        ];
    }

    private function capa(mixed $bloco, array $arquivos): ?array
    {
        if (! is_array($bloco) || ! isset($arquivos[$bloco['arquivo'] ?? ''])) {
            return null;
        }

        $info = $arquivos[$bloco['arquivo']];

        return [
            'arquivo' => $bloco['arquivo'],
            't' => $info['tipo'] === 'imagem' ? 0.0 : $this->entre((float) ($bloco['t'] ?? 0), 0, max(0, (float) $info['duracao'] - 0.05)),
            'texto' => $this->texto($bloco['texto'] ?? '', 60),
        ];
    }

    /* Utilidades ------------------------------------------------------- */

    private function id(mixed $id, string $prefixo, int $n, array &$usados): string
    {
        $id = is_string($id) && preg_match('/^[a-z]{1,4}\d{1,4}$/i', $id) ? strtolower($id) : sprintf('%s%02d', $prefixo, $n);

        while (isset($usados[$id])) {
            $id .= 'b';
        }

        $usados[$id] = true;

        return $id;
    }

    private function texto(mixed $valor, int $max): string
    {
        if (! is_scalar($valor)) {
            return '';
        }

        $texto = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', mb_scrub((string) $valor, 'UTF-8')) ?? '');

        return mb_substr($texto, 0, $max);
    }

    private function entre(float $v, float $min, float $max): float
    {
        return round(max($min, min($max, is_finite($v) ? $v : $min)), 3);
    }
}
