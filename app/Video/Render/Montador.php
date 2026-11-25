<?php

namespace App\Video\Render;

use App\Video\Ferramentas;
use App\Video\FalhaDeFerramenta;
use App\Video\Identidade;
use App\Video\Plano\Linha;
use Illuminate\Support\Facades\File;

/**
 * Transforma um plano normalizado em MP4 (o "edit-render" do pacote).
 *
 * Duas passadas, pensadas para VPS pequena:
 *
 *  1. SEGMENTOS — cada segmento vira um arquivo intermediário já no tamanho
 *     final, a 30 fps, cor convertida (HDR→SDR), câmera aplicada e cor da
 *     marca. Um decodificador por vez: memória limitada mesmo com 4K HEVC
 *     de iPhone. Cache por conteúdo (hash): num ajuste, só os segmentos que
 *     mudaram são refeitos.
 *
 *  2. MONTAGEM — junta os intermediários (cortes e transições), põe logo,
 *     legendas e animações (ASS), voz, música com ducking, efeitos, e
 *     normaliza a loudness (-14 LUFS, pico -1 dBTP, padrão de redes).
 *
 * Camadas, de baixo para cima: câmera → logo → animações/legendas.
 */
class Montador
{
    private const XFADE = [
        'fade' => 'fade', 'flash' => 'fadewhite', 'zoom' => 'zoomin', 'deslizar' => 'slideleft',
        'wipe' => 'wipeleft', 'desfoque' => 'hblur', 'revelar' => 'smoothleft', 'circulo' => 'circleopen',
        'preto' => 'fadeblack',
    ];

    private int $W;

    private int $H;

    private array $avisos = [];

    /**
     * @param  array  $fontes  chave => [original, proxy, tipo, hdr, largura, altura, tem_audio]
     * @param  array  $palavras  chave => [['w','i','f'], ...] no tempo de origem
     * @param  array|null  $musica  ['caminho' => ..., 'duracao' => ...]
     */
    public function __construct(
        private array $plano,
        private array $fontes,
        private array $id,
        private string $pasta,
        private string $modo = 'previa',
        private int $larguraFinal = 1080,
        private int $alturaFinal = 1920,
        private array $palavras = [],
        private ?array $musica = null,
        private ?string $logo = null,
        private array $arquivosDeFonte = [],
        private bool $temFala = false,
    ) {
        $escala = $modo === 'previa' ? (float) config('video.render.previa_escala', 0.5) : 1.0;
        $this->W = (int) (round($larguraFinal * $escala / 2) * 2);
        $this->H = (int) (round($alturaFinal * $escala / 2) * 2);
        File::ensureDirectoryExists($pasta, 0775);
    }

    /**
     * @return array [duracao, legendas (grupos lgNN), sons, avisos]
     */
    public function renderizar(string $saida, ?callable $progresso = null): array
    {
        $progresso ??= fn (float $f = 0) => null;
        $linha = new Linha($this->plano['segmentos']);
        $itens = $linha->itens;

        // 1. Segmentos (0–55%)
        $arquivos = [];
        foreach ($itens as $n => $item) {
            $arquivos[$n] = $this->segmento($item, function (float $f) use ($n, $itens, $progresso) {
                $progresso(0.55 * ($n + $f) / count($itens));
            });
            $progresso(0.55 * ($n + 1) / count($itens));
        }

        // 2. Legendas e animações
        $ass = new Ass($this->larguraFinal, $this->alturaFinal);
        $elementos = new Elementos($ass, $this->id);
        $sons = $elementos->gerar($this->plano['elementos']);
        $grupos = [];

        if ($this->plano['legendas']['ativo'] && $this->palavras) {
            $grupos = Legendas::gerar($ass, $this->palavrasNaSequencia($linha), $this->id, $this->plano['legendas'], Elementos::ocultamLegenda($this->plano['elementos']));
        }

        $cartao = $this->cartaoFinal($ass, $linha);

        foreach ($linha->cortes() as $corte) {
            $som = match ($corte['trans']) {
                'flash', 'zoom', 'deslizar', 'wipe', 'revelar' => 'whoosh',
                'desfoque', 'circulo' => 'swoosh',
                default => null,
            };
            if ($som) {
                $sons[] = ['t' => $corte['t'], 'som' => $som, 'forca' => 0.55];
            }
        }

        $arquivoAss = null;
        if (! $ass->vazio()) {
            $arquivoAss = 'legendas_'.$this->modo.'.ass';
            file_put_contents($this->pasta.'/'.$arquivoAss, $ass->documento());
            $this->copiarFontes();
        }

        // 3. Montagem (55–100%)
        $this->montar($linha, $arquivos, $saida, $arquivoAss, $this->filtrarSons($sons), $cartao, function (float $f) use ($progresso) {
            $progresso(0.55 + 0.45 * $f);
        });

        return [
            'duracao' => $linha->total,
            'legendas' => $grupos,
            'sons' => count($sons),
            'avisos' => $this->avisos,
            'linha' => array_map(fn ($i) => [
                'id' => $i['id'], 'arquivo' => $i['arquivo'] ?? null, 'tipo' => $i['tipo'],
                'inicio' => $i['inicio'] ?? null, 'fim' => $i['fim'] ?? null,
                'seq_inicio' => $i['seq_inicio'], 'seq_fim' => $i['seq_fim'], 'nota' => $i['nota'] ?? '',
            ], $itens),
        ];
    }

    /* 1ª passada: um arquivo por segmento ------------------------------- */

    private function segmento(array $item, callable $progresso): array
    {
        $fonte = $item['tipo'] === 'cartao' ? null : ($this->fontes[$item['arquivo']] ?? null);
        $origem = $fonte ? (($this->modo === 'previa' && ! empty($fonte['proxy']) && is_file($fonte['proxy'])) ? $fonte['proxy'] : $fonte['original']) : null;
        $usandoProxy = $fonte && $origem === ($fonte['proxy'] ?? null);

        $chave = md5(json_encode([
            'v' => 4, $this->modo, $this->W, $this->H, $item['tipo'], $origem, $origem ? @filemtime($origem) : null,
            $item['inicio'] ?? null, $item['fim'] ?? null, $item['duracao'] ?? null, $item['dur'],
            $item['velocidade'], $item['camera'], $item['audio'] ?? false, $this->id['grade'],
            $item['tipo'] === 'cartao' ? [$this->id['primaria'], $this->id['fundo']] : null,
        ]));

        $cache = $this->pasta.'/cache';
        File::ensureDirectoryExists($cache, 0775);
        $video = "$cache/$chave.mp4";
        $audio = ($item['audio'] ?? false) ? "$cache/$chave.wav" : null;

        if (is_file($video) && (! $audio || is_file($audio))) {
            return ['video' => $video, 'audio' => $audio];
        }

        $quadros = (int) round($item['dur'] * 30);
        $W = $this->W;
        $H = $this->H;
        $preset = $this->modo === 'previa' ? ['-preset', 'veryfast', '-crf', '20'] : ['-preset', 'veryfast', '-crf', '15'];

        if ($item['tipo'] === 'cartao') {
            $entrada = Ferramentas::temFiltro('gradients')
                ? sprintf('gradients=s=%dx%d:r=30:c0=%s:c1=%s:x0=0:y0=0:x1=%d:y1=%d:d=%s:speed=0.015', $W, $H, $this->id['primaria'], $this->id['fundo'], $W, $H, Ferramentas::s($item['dur'] + 0.5))
                : sprintf('color=c=%s:s=%dx%d:r=30:d=%s', $this->id['fundo'], $W, $H, Ferramentas::s($item['dur'] + 0.5));

            Ferramentas::ffmpeg(['-f', 'lavfi', '-i', $entrada, '-vf', "format=yuv420p,trim=end_frame=$quadros,setpts=PTS-STARTPTS", ...$preset, '-an', '-threads', Ferramentas::threads(), $video], $this->pasta, $item['dur'], $progresso);

            return ['video' => $video, 'audio' => null];
        }

        if (! $origem || ! is_file($origem)) {
            throw new FalhaDeFerramenta("O arquivo do segmento {$item['id']} não está mais no servidor (pode ter sido limpo pela retenção).");
        }

        $entrada = [];
        $cadeia = [];

        if ($item['tipo'] === 'foto') {
            $entrada = ['-loop', '1', '-framerate', '30', '-t', Ferramentas::s($item['dur'] + 0.5), '-i', $origem];
        } else {
            $durOrigem = (float) $item['fim'] - (float) $item['inicio'];
            $entrada = ['-ss', Ferramentas::s($item['inicio']), '-t', Ferramentas::s($durOrigem + 0.1), '-i', $origem];

            if (abs($item['velocidade'] - 1.0) > 0.001) {
                $cadeia[] = 'setpts=(PTS-STARTPTS)/'.$item['velocidade'];
            } else {
                $cadeia[] = 'setpts=PTS-STARTPTS';
            }
        }

        $cadeia[] = 'fps=30';

        // HDR do iPhone → SDR BT.709 antes de qualquer escala (os proxies já vêm convertidos).
        if (($fonte['hdr'] ?? false) && ! $usandoProxy) {
            if (Ferramentas::temFiltro('zscale') && Ferramentas::temFiltro('tonemap')) {
                $cadeia[] = 'zscale=t=linear:npl=100,format=gbrpf32le,zscale=p=bt709,tonemap=hable:desat=0,zscale=t=bt709:m=bt709:r=tv';
            } else {
                $this->avisos[] = 'Vídeo HDR sem conversão de cor (o FFmpeg do servidor não tem zscale): as cores podem sair lavadas.';
            }
        }

        $cadeia = array_merge($cadeia, $this->camera($item, $fonte));
        $cadeia[] = Identidade::filtroGrade($this->id['grade']);
        $cadeia[] = 'setsar=1,format=yuv420p';
        $cadeia[] = "tpad=stop_mode=clone:stop_duration=1,trim=end_frame=$quadros,setpts=PTS-STARTPTS";

        $grafo = '[0:v]'.implode(',', $cadeia).'[v]';
        $saidas = ['-map', '[v]', ...$preset, '-pix_fmt', 'yuv420p', '-an', $video];

        if ($audio) {
            $d = Ferramentas::s($item['dur']);
            $grafo .= ";[0:a]asetpts=PTS-STARTPTS,aresample=48000,aformat=channel_layouts=stereo,apad=whole_dur=$d,atrim=end=$d,afade=t=in:d=0.015,afade=t=out:st=".Ferramentas::s(max(0, $item['dur'] - 0.025)).':d=0.025[a]';
            $saidas = array_merge($saidas, ['-map', '[a]', '-c:a', 'pcm_s16le', $audio]);
        }

        $arquivoGrafo = "$cache/$chave.txt";
        file_put_contents($arquivoGrafo, $grafo);

        Ferramentas::ffmpeg([...$entrada, ...Ferramentas::argGrafo($arquivoGrafo), '-threads', Ferramentas::threads(), ...$saidas], $this->pasta, $item['dur'], $progresso);
        @unlink($arquivoGrafo);

        return ['video' => $video, 'audio' => $audio];
    }

    /**
     * Enquadramento + câmera: recorte "cover" para o formato final centrado no
     * foco, depois zoom (aproximação lenta + punches) com zoompan.
     */
    private function camera(array $item, array $fonte): array
    {
        $W = $this->W;
        $H = $this->H;
        $sw = max(2, (int) $fonte['largura']);
        $sh = max(2, (int) $fonte['altura']);
        [$fx, $fy] = $item['camera']['foco'];
        $alvo = $W / $H;

        // Janela do formato final dentro da origem, centrada no foco e presa às bordas.
        if ($sw / $sh > $alvo) {
            $cw = (int) (floor($sh * $alvo / 2) * 2);
            $ch = $sh;
        } else {
            $cw = $sw;
            $ch = (int) (floor($sw / $alvo / 2) * 2);
        }
        $cx = (int) max(0, min($sw - $cw, round($fx * $sw - $cw / 2)));
        $cy = (int) max(0, min($sh - $ch, round($fy * $sh - $ch / 2)));

        // Foco relativo à janela, para onde o zoom aproxima.
        $rx = $cw > 0 ? max(0, min(1, ($fx * $sw - $cx) / $cw)) : 0.5;
        $ry = $ch > 0 ? max(0, min(1, ($fy * $sh - $cy) / $ch)) : 0.5;

        // Em proporções da entrada, não em pixels: a prévia usa o proxy (720p),
        // o final usa o original (4K) — os mesmos números servem para os dois.
        $filtros = [];
        if ($cw !== $sw || $ch !== $sh) {
            $filtros[] = sprintf("crop=w='trunc(iw*%s/2)*2':h='trunc(ih*%s/2)*2':x='iw*%s':y='ih*%s'",
                number_format($cw / $sw, 6, '.', ''), number_format($ch / $sh, 6, '.', ''),
                number_format($cx / $sw, 6, '.', ''), number_format($cy / $sh, 6, '.', ''));
        }

        [$z0, $z1] = $item['camera']['zoom'];

        // Foto parada parece erro: sempre um Ken Burns suave.
        if ($item['tipo'] === 'foto' && abs($z1 - $z0) < 0.001 && empty($item['camera']['punches'])) {
            [$z0, $z1] = [1.0, 1.07];
        }

        $punches = $item['camera']['punches'] ?? [];
        $animado = abs($z1 - $z0) > 0.001 || $punches || $z0 > 1.001;

        if (! $animado) {
            $filtros[] = "scale=$W:$H:flags=lanczos";

            return $filtros;
        }

        // Escala acima do final antes do zoompan: arredondamento em pixel menor = sem tremor.
        $fator = $this->modo === 'previa' ? 2 : 1.5;
        $SW = (int) (round($W * $fator / 2) * 2);
        $SH = (int) (round($H * $fator / 2) * 2);
        $filtros[] = "scale=$SW:$SH:flags=bicubic";

        $N = max(1, (int) round($item['dur'] * 30));
        $T = '(on/30)';
        $z = sprintf('%s+%s*min(on/%d,1)', Ferramentas::s($z0), Ferramentas::s($z1 - $z0), $N);

        foreach ($punches as $p) {
            $tp = $item['tipo'] === 'clip' ? ((float) $p['t'] - (float) $item['inicio']) / $item['velocidade'] : (float) $p['t'];
            // Sobe em 0,18 s com desaceleração (power2.out) e segura até o próximo corte.
            $z .= sprintf('+%s*if(lt(%s,%s),0,1-pow(1-min((%s-%s)/0.18,1),2))', Ferramentas::s($p['forca']), $T, Ferramentas::s($tp), $T, Ferramentas::s($tp));
        }

        $filtros[] = sprintf("zoompan=z='min(%s,2)':x='(iw-iw/zoom)*%s':y='(ih-ih/zoom)*%s':d=1:s=%dx%d:fps=30", $z, Ferramentas::s($rx), Ferramentas::s($ry), $W, $H);

        return $filtros;
    }

    /* Legendas: tempo de origem → sequência ------------------------------ */

    private function palavrasNaSequencia(Linha $linha): array
    {
        $saida = [];

        foreach ($linha->itens as $item) {
            if ($item['tipo'] !== 'clip' || ! ($item['audio'] ?? false)) {
                continue;
            }

            foreach ($this->palavras[$item['arquivo']] ?? [] as $p) {
                $meio = ((float) $p['i'] + (float) $p['f']) / 2;

                if ($meio < $item['inicio'] || $meio > $item['fim']) {
                    continue;
                }

                $i = $item['seq_inicio'] + max(0, (float) $p['i'] - $item['inicio']) / $item['velocidade'];
                $f = $item['seq_inicio'] + min((float) $p['f'] - $item['inicio'], $item['fim'] - $item['inicio']) / $item['velocidade'];
                $saida[] = ['w' => $p['w'], 'i' => round($i, 3), 'f' => round(max($f, $i + 0.05), 3)];
            }
        }

        usort($saida, fn ($a, $b) => $a['i'] <=> $b['i']);

        return $saida;
    }

    /** Textos do cartão final (o logo grande entra pela montagem). */
    private function cartaoFinal(Ass $ass, Linha $linha): ?array
    {
        $seg = $linha->segmento('fim');

        if (! $seg || ! $this->plano['cartao_final']['ativo']) {
            return null;
        }

        $c = $this->plano['cartao_final'];
        $ini = $seg['seq_inicio'] + $seg['trans_dur'];
        $fim = $seg['seq_fim'];
        $W = $this->larguraFinal;
        $H = $this->alturaFinal;
        $k = $ass->k();
        $y = $H * ($this->logo ? 0.56 : 0.42);

        $ass->estilo('Cartao', $this->id['fonte_titulo'], (int) (78 * $k), $this->id['texto'], '#000000', 0, 0, true);

        if ($c['titulo'] !== '') {
            foreach (Ass::quebrar($c['titulo'], 78 * $k, $W * 0.82) as $n => $l) {
                $ass->evento($ini + 0.1, $fim, sprintf('{\\an5\\pos(%d,%d)\\fscx80\\fscy80\\t(0,200,\\fscx100\\fscy100)\\fad(150,0)}%s', $W / 2, $y + $n * 90 * $k, Ass::escapar($l)), 'Cartao', 50);
            }
            $y += 130 * $k;
        }

        foreach ($c['linhas'] as $n => $l) {
            $ass->evento($ini + 0.25 + $n * 0.12, $fim, sprintf('{\\an5\\pos(%d,%d)\\fs%d\\c%s\\fad(180,0)}%s', $W / 2, $y + $n * 70 * $k, (int) (50 * $k), Ass::corTag($n === 0 ? $this->id['destaque'] : $this->id['texto']), Ass::escapar($l)), 'Cartao', 50);
        }

        return ['inicio' => $ini, 'fim' => $fim, 'seq_inicio' => $seg['seq_inicio']];
    }

    /** Sem empilhar o mesmo som; no máximo 60 deixas (cada uma é uma entrada do FFmpeg). */
    private function filtrarSons(array $sons): array
    {
        usort($sons, fn ($a, $b) => $a['t'] <=> $b['t']);
        $saida = [];
        $ultimo = [];

        foreach ($sons as $s) {
            if (! Sons::caminho($s['som'])) {
                continue;
            }
            if (isset($ultimo[$s['som']]) && $s['t'] - $ultimo[$s['som']] < 0.12) {
                continue;
            }
            $ultimo[$s['som']] = $s['t'];
            $saida[] = $s;
        }

        return array_slice($saida, 0, 60);
    }

    private function copiarFontes(): void
    {
        $destino = $this->pasta.'/fontes';
        File::ensureDirectoryExists($destino, 0775);

        foreach ($this->arquivosDeFonte as $arquivo) {
            $alvo = $destino.'/'.basename($arquivo);
            // Recopia se a fonte mudou (reinstalada, trocada no perfil): senão o render usa a velha.
            if (is_file($arquivo) && (! is_file($alvo) || filesize($alvo) !== filesize($arquivo) || filemtime($arquivo) > filemtime($alvo))) {
                @copy($arquivo, $alvo);
            }
        }
    }

    /* 2ª passada: montagem ------------------------------------------- */

    private function montar(Linha $linha, array $arquivos, string $saida, ?string $arquivoAss, array $sons, ?array $cartao, callable $progresso): void
    {
        $W = $this->W;
        $H = $this->H;
        $total = $linha->total;
        $args = [];
        $g = [];
        $n = 0;

        // Vídeo: um input por segmento.
        $rotulos = [];
        foreach ($linha->itens as $i => $item) {
            $args = array_merge($args, ['-i', $arquivos[$i]['video']]);
            // xfade exige base de tempo e taxa idênticas nas duas pontas (inclusive na saída do concat).
            $g[] = "[$n:v]fps=30,settb=AVTB,setsar=1,format=yuv420p[n$i]";
            $rotulos[$i] = "[n$i]";
            $n++;
        }

        $todosCorte = collect($linha->itens)->every(fn ($it, $i) => $i === 0 || $it['trans'] === 'corte');

        if (count($rotulos) === 1) {
            $g[] = $rotulos[0].'null[vb]';
        } elseif ($todosCorte) {
            $g[] = implode('', $rotulos).'concat=n='.count($rotulos).':v=1:a=0[vb]';
        } else {
            $acumulado = $rotulos[0];
            $durAcumulada = $linha->itens[0]['dur'];
            foreach (array_slice($linha->itens, 1, null, true) as $i => $item) {
                $rot = $i === count($linha->itens) - 1 ? '[vb]' : "[x$i]";
                if ($item['trans'] === 'corte') {
                    $g[] = "{$acumulado}{$rotulos[$i]}concat=n=2:v=1:a=0,settb=AVTB$rot";
                    $durAcumulada += $item['dur'];
                } else {
                    $tipo = self::XFADE[$item['trans']] ?? 'fade';
                    $offset = max(0, $durAcumulada - $item['trans_dur']);
                    $g[] = sprintf('%s%sxfade=transition=%s:duration=%s:offset=%s%s', $acumulado, $rotulos[$i], $tipo, Ferramentas::s($item['trans_dur']), Ferramentas::s($offset), $rot);
                    $durAcumulada = $offset + $item['dur'];
                }
                $acumulado = $rot;
            }
        }

        $atual = '[vb]';

        // Logo: marca d'água durante o vídeo + grande no cartão final.
        $posLogo = $this->id['logo_posicao'];
        $marca = $this->logo && $posLogo !== 'nenhum';
        $logoCartao = $this->logo && $cartao;

        if ($marca || $logoCartao) {
            $args = array_merge($args, ['-loop', '1', '-framerate', '30', '-t', Ferramentas::s($total), '-i', $this->logo]);
            $il = $n++;
            $ramos = ($marca ? 1 : 0) + ($logoCartao ? 1 : 0);
            $g[] = "[$il:v]format=rgba".($ramos > 1 ? ',split=2[lgA][lgB]' : '[lgA]');

            $fimMarca = $cartao ? $cartao['seq_inicio'] : $total;

            if ($marca) {
                $lw = (int) (round($W * $this->id['logo_tamanho'] / 100 / 2) * 2);
                $mx = (int) round($W * 0.05);
                $my = (int) round($H * 0.045);
                [$x, $y] = match ($posLogo) {
                    'topo_esquerda' => [$mx, $my],
                    'baixo_esquerda' => [$mx, "H*0.79-h"],
                    'baixo_direita' => ["W-w-$mx", "H*0.79-h"],
                    default => ["W-w-".(int) round($W * 0.06), $my],
                };
                $alfa = Ferramentas::s($this->id['logo_opacidade'] / 100);
                $g[] = "[lgA]scale=$lw:-1,colorchannelmixer=aa={$alfa}[lgm]";
                $g[] = "{$atual}[lgm]overlay=x=$x:y=$y:enable='lt(t,".Ferramentas::s($fimMarca).")'[vl]";
                $atual = '[vl]';
            }

            if ($logoCartao) {
                $lw = (int) (round($W * 0.38 / 2) * 2);
                $fonteRamo = $marca ? '[lgB]' : '[lgA]';
                $g[] = "{$fonteRamo}scale=$lw:-1,fade=t=in:st=".Ferramentas::s($cartao['inicio']).":d=0.45:alpha=1[lgc]";
                $g[] = "{$atual}[lgc]overlay=x=(W-w)/2:y=H*0.36-h/2:enable='gte(t,".Ferramentas::s($cartao['seq_inicio']).")'[vc]";
                $atual = '[vc]';
            }
        }

        if ($arquivoAss) {
            $g[] = "{$atual}ass=".Ferramentas::escFiltro($arquivoAss).':fontsdir=fontes[vout]';
        } else {
            $g[] = "{$atual}null[vout]";
        }

        // Áudio ------------------------------------------------------------
        $mix = [];
        $vozes = [];

        foreach ($linha->itens as $i => $item) {
            if ($arquivos[$i]['audio']) {
                $args = array_merge($args, ['-i', $arquivos[$i]['audio']]);
                $ms = (int) round($item['seq_inicio'] * 1000);
                $g[] = "[$n:a]adelay=$ms:all=1[vz$i]";
                $vozes[] = "[vz$i]";
                $n++;
            }
        }

        $m = $this->plano['musica'];
        $usaMusica = $this->musica && $m['usar'] && is_file($this->musica['caminho']);
        $temVoz = (bool) $vozes;

        if ($temVoz) {
            $cadeiaVoz = 'apad=whole_dur='.Ferramentas::s($total).',atrim=end='.Ferramentas::s($total).($usaMusica ? ',asplit=2[voz][vozsc]' : '[voz]');
            $g[] = count($vozes) > 1
                ? implode('', $vozes).'amix=inputs='.count($vozes).':normalize=0:dropout_transition=0,'.$cadeiaVoz
                : $vozes[0].$cadeiaVoz;
            $mix[] = '[voz]';
        }

        if ($usaMusica) {
            $args = array_merge($args, ['-stream_loop', '-1', '-ss', Ferramentas::s($m['inicio']), '-i', $this->musica['caminho']]);
            $fadeOut = min($m['fade_out'], $total / 3);
            $g[] = sprintf('[%d:a]aresample=48000,aformat=channel_layouts=stereo,atrim=end=%s,asetpts=PTS-STARTPTS,volume=%s,afade=t=in:d=%s,afade=t=out:st=%s:d=%s%s',
                $n, Ferramentas::s($total), Ferramentas::s($m['volume']), Ferramentas::s($m['fade_in']), Ferramentas::s(max(0, $total - $fadeOut)), Ferramentas::s($fadeOut), $temVoz ? '[mus]' : '[musd]');
            if ($temVoz) {
                // Ducking: a música abaixa sozinha quando alguém fala.
                $g[] = '[mus][vozsc]sidechaincompress=threshold=0.012:ratio=9:attack=15:release=320[musd]';
            }
            $mix[] = '[musd]';
            $n++;
        }

        // Efeitos: o pico de cada som no quadro do evento.
        $nivelGeral = $temVoz ? 0.4 : 0.65;
        foreach ($sons as $j => $s) {
            $info = Sons::info($s['som']);
            $inicio = max(0, $s['t'] - $info['pico'] - 1 / 30);
            if ($inicio >= $total) {
                continue;
            }
            $args = array_merge($args, ['-i', Sons::caminho($s['som'])]);
            $ms = (int) round($inicio * 1000);
            $g[] = sprintf('[%d:a]aresample=48000,aformat=channel_layouts=stereo,volume=%s,adelay=%d:all=1[sx%d]', $n, Ferramentas::s($info['nivel'] * $s['forca'] * $nivelGeral * 1.6), $ms, $j);
            $mix[] = "[sx$j]";
            $n++;
        }

        $fimAudio = 'atrim=end='.Ferramentas::s($total).',loudnorm=I='.(int) config('video.render.loudness', -14).':TP=-1:LRA=11,aresample=48000[aout]';

        if (! $mix) {
            $g[] = 'anullsrc=r=48000:cl=stereo,atrim=end='.Ferramentas::s($total).'[aout]';
        } elseif (count($mix) === 1) {
            $g[] = $mix[0].'apad=whole_dur='.Ferramentas::s($total).','.$fimAudio;
        } else {
            $g[] = implode('', $mix).'amix=inputs='.count($mix).':normalize=0:duration=longest:dropout_transition=0,'.$fimAudio;
        }

        $arquivoGrafo = $this->pasta.'/grafo_'.$this->modo.'.txt';
        file_put_contents($arquivoGrafo, implode(";\n", $g));

        $codec = $this->modo === 'previa'
            ? ['-preset', 'veryfast', '-crf', (string) config('video.render.previa_crf', 28), '-b:a', '128k']
            : ['-preset', (string) config('video.render.final_preset', 'medium'), '-crf', (string) config('video.render.final_crf', 20), '-profile:v', 'high', '-b:a', '192k'];

        Ferramentas::ffmpeg([
            ...$args,
            ...Ferramentas::argGrafo($arquivoGrafo),
            '-map', '[vout]', '-map', '[aout]',
            '-c:v', 'libx264', ...$codec, '-pix_fmt', 'yuv420p', '-r', '30',
            '-c:a', 'aac', '-ar', '48000',
            '-movflags', '+faststart',
            '-threads', Ferramentas::threads(), '-filter_complex_threads', Ferramentas::threads(),
            '-t', Ferramentas::s($total),
            $saida,
        ], $this->pasta, $total, $progresso);
    }

    /** Capa do Reels: um quadro do vídeo, no formato final, com o título por cima. */
    public function capa(array $capa, string $saida): void
    {
        $fonte = $this->fontes[$capa['arquivo']] ?? null;

        if (! $fonte || ! is_file($fonte['original'])) {
            return;
        }

        $item = ['tipo' => 'foto', 'dur' => 1, 'camera' => ['zoom' => [1.0, 1.0], 'foco' => [0.5, 0.45], 'punches' => []], 'velocidade' => 1.0];
        $filtros = array_merge(
            ($fonte['hdr'] ?? false) && Ferramentas::temFiltro('zscale') ? ['zscale=t=linear:npl=100,format=gbrpf32le,zscale=p=bt709,tonemap=hable:desat=0,zscale=t=bt709:m=bt709:r=tv'] : [],
            array_filter($this->camera($item, $fonte), fn ($f) => ! str_starts_with($f, 'zoompan')),
            ['scale='.$this->W.':'.$this->H, Identidade::filtroGrade($this->id['grade'])]
        );

        if ($capa['texto'] !== '') {
            $ass = new Ass($this->larguraFinal, $this->alturaFinal);
            $k = $ass->k();
            $tam = (int) (110 * $k);
            $ass->estilo('Capa', $this->id['fonte_titulo'], $tam, $this->id['texto'], '#000000', 7 * $k, 4 * $k, true);
            $linhas = Ass::quebrar(mb_strtoupper($capa['texto']), $tam, $this->larguraFinal * 0.84);
            $ass->evento(0, 10, sprintf('{\\an5\\pos(%d,%d)}%s', $this->larguraFinal / 2, $this->alturaFinal * 0.62, implode('\\N', array_map([Ass::class, 'escapar'], $linhas))), 'Capa');
            file_put_contents($this->pasta.'/capa.ass', $ass->documento());
            $this->copiarFontes();
            $filtros[] = 'ass=capa.ass:fontsdir=fontes';
        }

        $entrada = $fonte['tipo'] === 'imagem' ? ['-i', $fonte['original']] : ['-ss', Ferramentas::s($capa['t']), '-i', $fonte['original']];
        Ferramentas::ffmpeg([...$entrada, '-frames:v', '1', '-vf', implode(',', $filtros), $saida], $this->pasta, null, null, 120);
    }
}
