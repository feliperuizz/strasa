<?php

namespace App\Video;

use App\Models\VideoJob;
use App\Video\Plano\Linha;
use App\Video\Render\Motion;
use Illuminate\Support\Facades\File;

/**
 * O Claude enxerga o que fez: para cada animação do plano, uma folha com a
 * entrada, o auge e a saída desenhados sobre o QUADRO REAL do vídeo naquele
 * instante, com o rosto (verde) e as zonas que o app cobre (vermelho)
 * marcados. É o "check → snapshot → olhar" do pacote de edição, sem Chrome.
 *
 * E o mapa do rosto (YuNet): onde está o rosto em cada trecho de cada
 * arquivo — para posicionar animações, centralizar a câmera e marcar as folhas.
 */
class Revisao
{
    /** Mapa do rosto de um arquivo (cache em trabalho/rosto_fN.json). Null se não houver detector. */
    public static function mapearRosto(Armazenamento $pasta, string $chave): ?array
    {
        $saida = $pasta->absoluto("trabalho/rosto_{$chave}.json");
        if (is_file($saida)) {
            return json_decode((string) file_get_contents($saida), true) ?: null;
        }
        $proxy = $pasta->absoluto("trabalho/proxy_{$chave}.mp4");
        if (! Motion::rostoDisponivel() || ! is_file($proxy)) {
            return null;
        }
        try {
            Ferramentas::rodar([Motion::python(), '-I', base_path('scripts/video/motion/rosto.py'), $proxy, $saida, Motion::pastaIcones().'/yunet.onnx', '--fps', '2', '--threads', Ferramentas::threads()],
                dirname($saida), 900, null, ['OMP_NUM_THREADS' => Ferramentas::threads(), 'OPENBLAS_NUM_THREADS' => Ferramentas::threads()]);
        } catch (\Throwable) {
            return null; // sem rosto não é motivo para perder a edição
        }

        return is_file($saida) ? (json_decode((string) file_get_contents($saida), true) ?: null) : null;
    }

    /** Rosto no instante t do arquivo (o quadro amostrado mais próximo), [x, y, w, h] em 0–1. */
    public static function rostoEm(?array $mapa, float $t): ?array
    {
        $melhor = null;
        foreach ($mapa['quadros'] ?? [] as $q) {
            if ($melhor === null || abs($q['t'] - $t) < abs($melhor['t'] - $t)) {
                $melhor = $q;
            }
        }

        return $melhor && abs($melhor['t'] - $t) < 1.5 ? $melhor['r'] : null;
    }

    /** Resumo legível para o contexto do Claude: trechos com o rosto no mesmo lugar. */
    public static function resumoRosto(?array $mapa): string
    {
        $grupos = [];
        foreach ($mapa['quadros'] ?? [] as $q) {
            $r = $q['r'];
            $c = $r ? [round($r[0] + $r[2] / 2, 2), round($r[1] + $r[3] / 2, 2), round($r[3], 2)] : null;
            $ultimo = $grupos ? $grupos[count($grupos) - 1] : null;
            $igual = $ultimo && (($c === null && $ultimo['c'] === null) || ($c && $ultimo['c'] && abs($c[0] - $ultimo['c'][0]) < 0.08 && abs($c[1] - $ultimo['c'][1]) < 0.06));
            if ($igual) {
                $grupos[count($grupos) - 1]['fim'] = $q['t'];
            } else {
                $grupos[] = ['ini' => $q['t'], 'fim' => $q['t'], 'c' => $c];
            }
        }

        return implode(' · ', array_map(fn ($g) => sprintf('%s–%ss: %s', $g['ini'], $g['fim'],
            $g['c'] ? "rosto em x {$g['c'][0]} y {$g['c'][1]} (altura {$g['c'][2]})" : 'sem rosto'), array_slice($grupos, 0, 40)));
    }

    /**
     * Gera as folhas de revisão das animações do plano.
     *
     * @return array caminhos relativos à pasta do Claude (revisao/mo03.png …)
     */
    public static function folhas(VideoJob $job, array $plano, string $dirClaude, array $id, array $arquivosDeFonte, ?string $logo): array
    {
        $motion = array_values(array_filter($plano['elementos'] ?? [], fn ($e) => Motion::eh($e['tipo'] ?? '')));
        if (! $motion || ! Motion::disponivel()) {
            return [];
        }

        $dir = "$dirClaude/revisao";
        File::deleteDirectory($dir);
        File::ensureDirectoryExists("$dir/fundos", 0775);

        $pasta = $job->pasta();
        $linha = new Linha($plano['segmentos']);
        $dim = $job->dimensoes();
        $vertical = $dim['altura'] > $dim['largura'];
        $larguraFolha = $dim['largura'] > $dim['altura'] ? 960 : 540;
        $arquivos = [];
        foreach ($job->arquivos()->where('status', 'pronto')->get() as $a) {
            $arquivos[$a->chave()] = $a;
        }
        $segs = collect($plano['segmentos'])->keyBy('id');
        $mapas = [];
        $folhas = [];
        $n = 0;

        foreach (array_slice($motion, 0, 18) as $el) {
            $ini = (float) $el['inicio'];
            $dur = (float) $el['duracao'];
            $quadros = [];
            foreach (['entrada' => $ini + min(0.4, $dur * 0.2), 'auge' => $ini + $dur * 0.6, 'saída' => $ini + max(0.1, $dur - 0.12)] as $nome => $t) {
                $q = ['t' => round($t, 2), 'nome' => $nome];
                $origem = $linha->paraOrigem($t);
                $arq = $origem ? ($arquivos[$origem['arquivo']] ?? null) : null;
                if ($arq) {
                    $seg = $segs[$origem['segmento']] ?? [];
                    $png = "$dir/fundos/q".(++$n).'.png';
                    $rosto = null;
                    if ($arq->tipo === 'imagem') {
                        $fonte = $pasta->absoluto("trabalho/foto_{$arq->chave()}.png");
                        $tFonte = null;
                    } else {
                        $fonte = $pasta->absoluto("trabalho/proxy_{$arq->chave()}.mp4");
                        $tFonte = (float) $origem['t'];
                        $mapas[$arq->chave()] ??= self::mapearRosto($pasta, $arq->chave());
                        $rosto = self::rostoEm($mapas[$arq->chave()], $tFonte);
                    }
                    [$filtro, $mapear] = self::recorte((int) $arq->largura, (int) $arq->altura, $dim['largura'] / $dim['altura'], $seg['camera']['foco'] ?? [0.5, 0.5]);
                    if (is_file($fonte)) {
                        try {
                            Ferramentas::ffmpeg(array_merge($tFonte !== null ? ['-ss', Ferramentas::s($tFonte)] : [], ['-i', $fonte, '-frames:v', '1', '-vf', "$filtro,scale={$larguraFolha}:-2", $png]), $dir, null, null, 60);
                            $q['fundo'] = $png;
                        } catch (\Throwable) {
                        }
                    }
                    if ($rosto) {
                        $q['rosto'] = $mapear($rosto);
                    }
                }
                $quadros[] = $q;
            }
            $saida = "$dir/{$el['id']}.png";
            $folhas[] = ['saida' => $saida, 'rotulo' => $el['id'].' '.$el['tipo'], 'quadros' => $quadros];
        }

        $pedido = Motion::pedido($motion, $id, $dim['largura'], $dim['altura'], $larguraFolha / $dim['largura'], $linha->total, $logo, $arquivosDeFonte);
        $pedido['folhas'] = $folhas;
        $pedido['zonas'] = $vertical; // zonas dos botões do app: só Reels/Stories
        try {
            Motion::rodarPedido($pedido, $dir);
        } catch (\Throwable $e) {
            $job->registrar('sistema', 'Não consegui gerar as folhas de revisão das animações: '.mb_substr($e->getMessage(), 0, 200));

            return [];
        }

        return array_values(array_map(fn ($f) => 'revisao/'.basename($f['saida']), array_filter($folhas, fn ($f) => is_file($f['saida']))));
    }

    /**
     * Recorte "cover" do formato final dentro da origem, centrado no foco
     * (o mesmo que a montagem faz, sem o zoom). Devolve o filtro do FFmpeg e
     * a função que leva um retângulo da origem (0–1) para o quadro final (0–1).
     */
    private static function recorte(int $sw, int $sh, float $alvo, array $foco): array
    {
        $sw = max(2, $sw);
        $sh = max(2, $sh);
        if ($sw / $sh > $alvo) {
            $cw = $sh * $alvo;
            $ch = $sh;
        } else {
            $cw = $sw;
            $ch = $sw / $alvo;
        }
        $cx = max(0, min($sw - $cw, (float) ($foco[0] ?? 0.5) * $sw - $cw / 2));
        $cy = max(0, min($sh - $ch, (float) ($foco[1] ?? 0.5) * $sh - $ch / 2));
        $p = fn ($v) => number_format($v, 6, '.', '');
        $filtro = sprintf("crop=w='trunc(iw*%s/2)*2':h='trunc(ih*%s/2)*2':x='iw*%s':y='ih*%s'", $p($cw / $sw), $p($ch / $sh), $p($cx / $sw), $p($cy / $sh));
        $mapear = fn (array $r) => [
            round(($r[0] * $sw - $cx) / $cw, 4), round(($r[1] * $sh - $cy) / $ch, 4), round($r[2] * $sw / $cw, 4), round($r[3] * $sh / $ch, 4),
        ];

        return [$filtro, $mapear];
    }
}
