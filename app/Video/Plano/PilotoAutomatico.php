<?php

namespace App\Video\Plano;

use App\Models\VideoJob;
use App\Models\VideoProfile;

/**
 * Editor por regras, sem IA. Garante que o estúdio entrega alguma coisa
 * quando o Claude não está disponível (não instalado, fora do ar, limite
 * da assinatura) — e serve de base de teste do motor.
 *
 * Fala: um segmento por frase (quebra nas pausas), sem hesitações, zoom
 * alternando a cada corte, legenda ligada.
 * Montagem: o miolo de cada clipe (evitando as pontas), na ordem enviada,
 * cortes na batida, título com o nome do cliente.
 */
class PilotoAutomatico
{
    private const HESITACAO = '/^(h+u+m+|hm+|a+h+n*|ã+h*|é+|e+h+|tipo|né)[.,…!?]*$/iu';

    public function __construct(private VideoJob $job) {}

    /**
     * @param  array  $inventario  chave => info (do Preparador)
     * @param  array  $palavras  chave => [['w','i','f'], ...]
     */
    public function gerar(array $inventario, array $palavras, ?array $musica): array
    {
        $estilo = $this->job->estiloInfo();
        $perfil = VideoProfile::doCliente($this->job->client);
        $alvo = (float) ($this->job->duracao_alvo ?: $estilo['duracao'][2]);

        $segmentos = $estilo['fala']
            ? $this->segmentosDeFala($inventario, $palavras, $alvo)
            : $this->segmentosDeMontagem($inventario, $alvo, $musica);

        $elementos = [];
        if (! $estilo['fala'] && $segmentos) {
            $elementos[] = ['tipo' => 'titulo', 'texto' => $this->job->client->name, 'em' => 0.3, 'duracao' => 2.4, 'posicao' => 'topo', 'animacao' => 'subir'];
        }

        if ($this->job->estilo === 'antes_depois') {
            foreach ($segmentos as $s) {
                if (in_array($s['papel'] ?? null, ['antes', 'depois'], true) && ! collect($elementos)->contains(fn ($e) => ($e['texto'] ?? '') === mb_strtoupper($s['papel']))) {
                    $elementos[] = ['tipo' => 'etiqueta', 'texto' => mb_strtoupper($s['papel']), 'em' => ['segmento' => $s['id'], 't' => 0.2], 'duracao' => 1.8, 'posicao' => 'topo'];
                }
            }
        }

        $linhas = array_values(array_filter([
            $perfil->instagram ? '@'.ltrim($perfil->instagram, '@') : null,
            $perfil->whatsapp ? 'WhatsApp '.$perfil->whatsapp : null,
            $perfil->endereco,
        ]));

        return [
            'titulo' => $this->job->titulo,
            'resumo' => 'Edição automática por regras (o Claude não estava disponível): '
                .($estilo['fala'] ? 'cortei as pausas e hesitações, liguei a legenda e alternei o zoom a cada corte.' : 'peguei o melhor trecho de cada clipe, na ordem enviada, com cortes na batida da música.')
                .' Peça ajustes ou edite o plano manualmente.',
            'segmentos' => $segmentos,
            'elementos' => $elementos,
            'legendas' => ['ativo' => $estilo['legendas'] && $this->job->opcao('legendas', true)],
            'musica' => ['usar' => (bool) $musica, 'inicio' => 0, 'sincronizar_cortes' => ! $estilo['fala']],
            'cartao_final' => [
                'ativo' => (bool) $this->job->opcao('cartao_final', true),
                'titulo' => $perfil->cta_texto ?: 'Siga para mais',
                'linhas' => $linhas,
            ],
            'avisos' => [],
        ];
    }

    private function segmentosDeFala(array $inventario, array $palavras, float $alvo): array
    {
        $segmentos = [];
        $n = 0;

        foreach ($inventario as $chave => $info) {
            if ($info['tipo'] !== 'video') {
                continue;
            }

            $frases = $this->frases($palavras[$chave] ?? []);

            // Sem transcrição: usa os silêncios para achar onde há fala.
            if (! $frases) {
                $frases = $this->falaPelosSilencios($info);
            }

            foreach ($frases as [$i, $f]) {
                $n++;
                $fechado = $n % 2 === 0;
                $segmentos[] = [
                    'id' => sprintf('s%02d', $n),
                    'arquivo' => $chave,
                    'inicio' => max(0, round($i - 0.08, 3)),
                    'fim' => min($info['duracao'], round($f + 0.1, 3)),
                    'audio' => true,
                    'camera' => ['zoom' => $fechado ? [1.28, 1.32] : [1.0, 1.06], 'foco' => [0.5, 0.4]],
                ];
            }
        }

        return $this->limitar($segmentos, $alvo * 1.6);
    }

    /** Frases = trechos de fala separados por pausa > 0,45 s, sem hesitação solta. */
    private function frases(array $palavras): array
    {
        $frases = [];
        $atual = [];
        $anterior = null;

        foreach ($palavras as $p) {
            if ($anterior && $p['i'] - $anterior['f'] > 0.45 && $atual) {
                $frases[] = $atual;
                $atual = [];
            }
            $atual[] = $p;
            $anterior = $p;
        }
        if ($atual) {
            $frases[] = $atual;
        }

        $saida = [];
        foreach ($frases as $frase) {
            $uteis = array_filter($frase, fn ($p) => ! preg_match(self::HESITACAO, trim($p['w'])));
            if (! $uteis) {
                continue;
            }
            $saida[] = [reset($frase)['i'], end($frase)['f']];
        }

        return $saida;
    }

    private function falaPelosSilencios(array $info): array
    {
        $dur = (float) $info['duracao'];
        $cursor = 0.0;
        $trechos = [];

        foreach ($info['silencios'] ?? [] as [$a, $b]) {
            if ($a - $cursor > 0.5) {
                $trechos[] = [$cursor, $a];
            }
            $cursor = $b;
        }
        if ($dur - $cursor > 0.5) {
            $trechos[] = [$cursor, $dur];
        }

        return $trechos ?: [[0.0, $dur]];
    }

    private function segmentosDeMontagem(array $inventario, float $alvo, ?array $musica): array
    {
        $ordem = collect($inventario)->sortBy(fn ($i) => match ($i['papel']) {
            'antes' => 0, 'depois' => 2, default => 1,
        })->all();

        $quantos = max(1, count($ordem));
        $porClipe = max(1.2, min(3.0, $alvo / $quantos));
        $segmentos = [];
        $n = 0;
        $primeiroDepois = true;

        foreach ($ordem as $chave => $info) {
            if ($info['tipo'] === 'audio') {
                continue;
            }

            $n++;
            $transicao = 'corte';
            if ($info['papel'] === 'depois' && $primeiroDepois) {
                $transicao = 'revelar';
                $primeiroDepois = false;
            }

            $camera = ['zoom' => $n % 2 ? [1.0, 1.12] : [1.14, 1.0], 'foco' => [0.5, 0.5]];

            if ($info['tipo'] === 'imagem') {
                $segmentos[] = ['id' => sprintf('s%02d', $n), 'arquivo' => $chave, 'duracao' => $porClipe, 'camera' => $camera, 'transicao' => $transicao, 'papel' => $info['papel'], 'audio' => false];

                continue;
            }

            // Evita as pontas (mão no botão, câmera se ajeitando): começa em 25% do clipe.
            $dur = (float) $info['duracao'];
            $tamanho = min($porClipe, $dur * 0.7);
            $inicio = max(0, min($dur - $tamanho, $dur * 0.25));

            $segmentos[] = [
                'id' => sprintf('s%02d', $n), 'arquivo' => $chave,
                'inicio' => round($inicio, 3), 'fim' => round($inicio + $tamanho, 3),
                'camera' => $camera, 'transicao' => $transicao, 'papel' => $info['papel'], 'audio' => false,
            ];
        }

        return $this->limitar($segmentos, $alvo * 1.3);
    }

    private function limitar(array $segmentos, float $maximo): array
    {
        $total = 0.0;
        $saida = [];

        foreach ($segmentos as $s) {
            $dur = isset($s['fim']) ? $s['fim'] - $s['inicio'] : ($s['duracao'] ?? 2);
            if ($saida && $total + $dur > $maximo) {
                break;
            }
            $saida[] = $s;
            $total += $dur;
        }

        return $saida;
    }
}
