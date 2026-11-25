<?php

namespace App\Video;

/**
 * Andamento (BPM) e batidas de uma música, em PHP puro — sem Python/librosa.
 *
 * Método clássico e leve: energia em janelas de ~23 ms → "fluxo" (quanto a
 * energia subiu) → autocorrelação para achar o período (70–180 BPM) → fase
 * que mais coincide com os picos. Para cortar o "tour do espaço" na batida
 * é o bastante; não é análise musical de estúdio.
 */
class Batidas
{
    private const TAXA = 11025;

    private const SALTO = 256; // ~23 ms

    public static function analisar(string $arquivo, float $maxSegundos = 240): array
    {
        // Para arquivo, não para o stdout: o rodar() só guarda o fim da saída.
        $temp = tempnam(sys_get_temp_dir(), 'batidas');

        try {
            Ferramentas::rodar([
                Ferramentas::ffmpegBin(), '-hide_banner', '-nostdin', '-y', '-v', 'error', '-t', (string) $maxSegundos,
                '-threads', Ferramentas::threads(), '-i', $arquivo, '-ac', '1', '-ar', (string) self::TAXA, '-threads', Ferramentas::threads(), '-f', 's16le', $temp,
            ], null, 180);

            return self::deAmostras((string) file_get_contents($temp));
        } finally {
            @unlink($temp);
        }
    }

    /** @param  string  $pcm  s16le mono a 11025 Hz */
    public static function deAmostras(string $pcm): array
    {
        $amostras = strlen($pcm) >> 1;
        $janelas = intdiv($amostras, self::SALTO);

        if ($janelas < 100) {
            return ['bpm' => null, 'batidas' => [], 'duracao' => round($amostras / self::TAXA, 3)];
        }

        // Energia (log) por janela, lendo em blocos para não estourar memória.
        $energia = [];
        for ($j = 0; $j < $janelas; $j++) {
            $bloco = unpack('s*', substr($pcm, $j * self::SALTO * 2, self::SALTO * 2));
            $soma = 0.0;
            foreach ($bloco as $v) {
                $soma += $v * $v;
            }
            $energia[] = log(1e-6 + $soma / self::SALTO);
        }

        // Fluxo positivo, com a média local subtraída (realça ataques).
        $fluxo = [0.0];
        for ($j = 1; $j < $janelas; $j++) {
            $fluxo[] = max(0.0, $energia[$j] - $energia[$j - 1]);
        }
        $media = array_sum($fluxo) / count($fluxo);
        $fluxo = array_map(fn ($v) => max(0.0, $v - $media), $fluxo);

        // Autocorrelação nos períodos de 70 a 180 BPM, com leve preferência perto de 115.
        $porSegundo = self::TAXA / self::SALTO;
        $minLag = (int) floor($porSegundo * 60 / 180);
        $maxLag = (int) ceil($porSegundo * 60 / 70);
        $melhorLag = $minLag;
        $melhor = -INF;
        $n = count($fluxo);

        for ($lag = $minLag; $lag <= $maxLag; $lag++) {
            $s = 0.0;
            for ($i = 0; $i + $lag < $n; $i++) {
                $s += $fluxo[$i] * $fluxo[$i + $lag];
            }
            $bpm = 60 * $porSegundo / $lag;
            $peso = exp(-0.5 * (log($bpm / 115, 2) / 0.9) ** 2);
            if ($s * $peso > $melhor) {
                $melhor = $s * $peso;
                $melhorLag = $lag;
            }
        }

        // Refino: o lag inteiro quantiza o período em 23 ms (120 BPM virava 117,5
        // e a batida derivava 0,4 s em 20 s). Busca fina de período e fase com um
        // "pente" sobre a música inteira, somando o fluxo nas batidas previstas.
        $melhorPeriodo = (float) $melhorLag;
        $melhorFase = 0.0;
        $melhor = -INF;

        for ($p = $melhorLag * 0.96; $p <= $melhorLag * 1.04; $p += 0.02) {
            for ($fase = 0.0; $fase < $p; $fase += 0.5) {
                $s = 0.0;
                for ($x = $fase; $x < $n - 1; $x += $p) {
                    $i = (int) $x;
                    $s += $fluxo[$i] + 0.5 * ($fluxo[$i + 1] ?? 0);
                }
                if ($s > $melhor) {
                    $melhor = $s;
                    $melhorPeriodo = $p;
                    $melhorFase = $fase;
                }
            }
        }

        $periodo = $melhorPeriodo / $porSegundo;
        $batidas = [];
        for ($t = $melhorFase / $porSegundo; $t < $amostras / self::TAXA; $t += $periodo) {
            $batidas[] = round($t, 3);
        }

        return [
            'bpm' => round(60 / $periodo, 1),
            'batidas' => $batidas,
            'duracao' => round($amostras / self::TAXA, 3),
        ];
    }

    /**
     * Ajusta a duração dos segmentos SEM SOM ORIGINAL para cada corte cair numa
     * batida (montagem do tour do espaço / antes e depois). Fala nunca é mexida.
     *
     * @param  array  $batidas  tempos da música (s), já descontado o início usado
     */
    public static function sincronizar(array $segmentos, array $batidas, array $duracoes): array
    {
        if (count($batidas) < 4) {
            return $segmentos;
        }

        $cursor = 0.0;

        foreach ($segmentos as $i => &$seg) {
            $vel = (float) ($seg['velocidade'] ?? 1.0);
            $dur = $seg['tipo'] === 'clip' ? ($seg['fim'] - $seg['inicio']) / $vel : (float) $seg['duracao'];
            $alvo = $cursor + $dur;
            $ultimo = $i === array_key_last($segmentos);

            if (! ($seg['audio'] ?? false) && ! $ultimo && $seg['tipo'] !== 'cartao') {
                // Batida mais próxima do fim desejado, sem encurtar abaixo de 0,5 s.
                $perto = null;
                foreach ($batidas as $b) {
                    if ($b - $cursor < 0.5) {
                        continue;
                    }
                    if ($perto === null || abs($b - $alvo) < abs($perto - $alvo)) {
                        $perto = $b;
                    }
                    if ($b > $alvo + 2) {
                        break;
                    }
                }

                if ($perto !== null && abs($perto - $alvo) < 0.6) {
                    $novaDur = $perto - $cursor;
                    if ($seg['tipo'] === 'clip') {
                        $disponivel = ((float) ($duracoes[$seg['arquivo']] ?? $seg['fim']) - $seg['inicio']) / $vel;
                        $novaDur = min($novaDur, $disponivel);
                        $seg['fim'] = round($seg['inicio'] + $novaDur * $vel, 3);
                    } else {
                        $seg['duracao'] = round($novaDur, 3);
                    }
                    $dur = $novaDur;
                }
            }

            // Transição sobrepõe: o próximo começa antes.
            $proxima = $segmentos[$i + 1] ?? null;
            $sobra = $proxima && ($proxima['transicao'] ?? 'corte') !== 'corte' ? (float) ($proxima['transicao_duracao'] ?? 0.35) : 0.0;
            $cursor += $dur - $sobra;
        }
        unset($seg);

        return $segmentos;
    }
}
