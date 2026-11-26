<?php

namespace App\Video;

use App\Models\VideoJob;
use App\Models\VideoProfile;
use App\Video\Plano\Linha;
use Illuminate\Support\Facades\File;

/**
 * Narração com voz gerada: o Claude escreve as falas no plano
 * (plano.narracao.falas, cada uma ancorada num momento do vídeo) e aqui
 * elas viram áudio — local e gratuito (scripts/video/narrar.py).
 *
 * Cada fala vira um WAV com cache por conteúdo (texto + voz + velocidade):
 * num ajuste, só a fala que mudou é gerada de novo. Depois as falas são
 * encaixadas na linha do tempo sem se atropelar, e as palavras ganham tempo
 * (distribuído pelo tamanho) para a legenda acompanhar a voz.
 */
class Narracao
{
    /** Vozes do catálogo (config video.narracao.vozes). */
    public static function vozes(): array
    {
        return (array) config('video.narracao.vozes', []);
    }

    /** Só as vozes cujo motor está instalado neste servidor. */
    public static function disponiveis(): array
    {
        return array_filter(self::vozes(), fn ($v) => self::motorPronto($v['motor']));
    }

    public static function disponivel(): bool
    {
        return (bool) self::disponiveis();
    }

    public static function python(string $motor): ?string
    {
        $bin = (string) config($motor === 'chatterbox' ? 'video.narracao.chatterbox_python' : 'video.narracao.python');

        return $bin !== '' ? Ferramentas::localizar($bin) : null;
    }

    public static function motorPronto(string $motor): bool
    {
        if (! self::python($motor)) {
            return false;
        }

        $m = self::pastaModelos();

        return match ($motor) {
            'kokoro' => (is_file("$m/kokoro-v1.0.onnx") || is_file("$m/kokoro-v1.0.int8.onnx")) && is_file("$m/voices-v1.0.bin"),
            'chatterbox' => true, // baixa o modelo no primeiro uso (o instalador já adianta)
            default => false,
        };
    }

    public static function pastaModelos(): string
    {
        return rtrim(str_replace('\\', '/', (string) config('video.narracao.modelos')), '/');
    }

    /** A voz desta edição: a escolhida no vídeo → a do cliente → a padrão → a primeira instalada. */
    public static function vozDaEdicao(VideoJob $job): ?string
    {
        $disponiveis = self::disponiveis();
        $perfil = VideoProfile::doCliente($job->client);

        foreach ([$job->opcao('voz'), $perfil->voz_narracao ?? null, config('video.narracao.voz_padrao')] as $voz) {
            if (is_string($voz) && isset($disponiveis[$voz])) {
                return $voz;
            }
        }

        return array_key_first($disponiveis);
    }

    public static function amostra(string $voz): ?string
    {
        $arquivo = self::pastaModelos()."/amostras/{$voz}.mp3";

        return is_file($arquivo) ? $arquivo : null;
    }

    /** Gera (ou refaz) a amostra de cada voz instalada. Devolve voz => caminho. */
    public static function gerarAmostras(bool $refazer = false): array
    {
        $texto = 'Olá! Esta é a voz que vai narrar os vídeos da sua marca. Três quartos, varanda gourmet e vista para o parque. Agende sua visita ainda hoje!';
        $pasta = self::pastaModelos().'/amostras';
        File::ensureDirectoryExists($pasta, 0775);
        $feitas = [];

        foreach (self::disponiveis() as $voz => $info) {
            $mp3 = "$pasta/$voz.mp3";
            if (is_file($mp3) && ! $refazer) {
                $feitas[$voz] = $mp3;

                continue;
            }
            [$wav] = array_values(self::sintetizar([['id' => 'a', 'texto' => $texto]], $voz, 1.0, $pasta));
            Ferramentas::ffmpeg(['-i', $wav['arquivo'], '-ac', '1', '-c:a', 'libmp3lame', '-b:a', '96k', $mp3], $pasta, null, null, 300);
            @unlink($wav['arquivo']);
            $feitas[$voz] = $mp3;
        }

        return $feitas;
    }

    /**
     * Gera o áudio das falas do plano e encaixa na linha do tempo.
     *
     * @return array{falas: array, avisos: array}
     */
    public static function preparar(array $plano, string $voz, string $pasta, ?callable $aoGerar = null): array
    {
        $n = $plano['narracao'] ?? null;

        if (! $n || ! $n['ativo'] || ! $n['falas']) {
            return ['falas' => [], 'avisos' => []];
        }

        $linha = new Linha($plano['segmentos']);
        $avisos = [];

        $audios = self::sintetizar(array_map(fn ($f) => ['id' => $f['id'], 'texto' => $f['texto']], $n['falas']), $voz, (float) $n['velocidade'], $pasta, $aoGerar);

        // Onde cada fala entra (âncora resolvida na linha atual).
        $falas = [];
        foreach ($n['falas'] as $f) {
            $inicio = $linha->resolver($f['em'] ?? null);
            if ($inicio === null) {
                $avisos[] = "Narração {$f['id']}: o momento indicado caiu num trecho cortado; a fala ficou de fora.";

                continue;
            }
            $falas[] = array_merge($f, ['inicio' => $inicio, 'arquivo' => $audios[$f['id']]['arquivo'], 'duracao' => $audios[$f['id']]['duracao']]);
        }

        usort($falas, fn ($a, $b) => $a['inicio'] <=> $b['inicio']);

        // Sem atropelar: uma fala só começa quando a anterior terminou (+ respiro).
        $fimAnterior = 0.0;
        foreach ($falas as &$f) {
            if ($f['inicio'] < $fimAnterior + 0.15) {
                if ($fimAnterior - $f['inicio'] > 0.6) {
                    $avisos[] = sprintf('Narração %s: a fala anterior ainda não tinha acabado; empurrei esta %.1f s para depois.', $f['id'], $fimAnterior + 0.15 - $f['inicio']);
                }
                $f['inicio'] = $fimAnterior + 0.15;
            }
            $f['inicio'] = round($f['inicio'], 3);
            $fimAnterior = $f['inicio'] + $f['duracao'];
            $f['palavras'] = self::palavrasNoTempo($f['texto'], $f['inicio'], $f['duracao']);
        }
        unset($f);

        $sobra = $fimAnterior - $linha->total;
        if ($sobra > 0.3) {
            $avisos[] = sprintf('A narração passou %.1f s do fim do vídeo e foi cortada: encurte o texto ou alongue os trechos.', $sobra);
        }

        return ['falas' => $falas, 'avisos' => $avisos];
    }

    /**
     * Texto → WAV (com cache). Uma chamada do Python para todas as falas
     * que faltam (o modelo carrega uma vez só).
     *
     * @param  array  $falas  [['id' => ..., 'texto' => ...]]
     * @return array id => ['arquivo' => ..., 'duracao' => ...]
     */
    public static function sintetizar(array $falas, string $voz, float $velocidade, string $pasta, ?callable $aoGerar = null): array
    {
        $info = self::vozes()[$voz] ?? null;

        if (! $info || ! self::motorPronto($info['motor'])) {
            throw new FalhaDeFerramenta("A voz \"{$voz}\" não está instalada neste servidor (veja php artisan video:diagnostico).");
        }

        $dir = rtrim($pasta, '/').'/narracao';
        File::ensureDirectoryExists($dir, 0775);
        $velocidade = round(max(0.8, min(1.3, $velocidade)), 2);
        $saida = [];
        $faltam = [];

        foreach ($falas as $f) {
            $falado = self::textoFalado($f['texto']);
            $arquivo = $dir.'/'.md5(json_encode([1, $info['motor'], $info['id'], $velocidade, $falado])).'.wav';
            $saida[$f['id']] = ['arquivo' => $arquivo, 'duracao' => null];
            if (! is_file($arquivo)) {
                $faltam[] = ['id' => (string) $f['id'], 'texto' => $falado, 'saida' => $arquivo];
            }
        }

        if ($faltam) {
            if ($aoGerar) {
                $aoGerar(count($faltam));
            }
            self::rodar($info, $velocidade, $faltam, $dir);
        }

        foreach ($saida as $id => &$s) {
            $s['duracao'] = self::duracaoWav($s['arquivo']);
            if ($s['duracao'] <= 0) {
                throw new FalhaDeFerramenta("A narração da fala {$id} saiu vazia.");
            }
        }

        return $saida;
    }

    private static function rodar(array $info, float $velocidade, array $falas, string $dir): void
    {
        $motor = $info['motor'];
        $pedido = $dir.'/pedido_'.getmypid().'.json';
        $resultado = $dir.'/resultado_'.getmypid().'.json';
        $referencia = $motor === 'chatterbox' && $info['id'] !== 'padrao' ? self::pastaModelos()."/referencias/{$info['id']}.wav" : null;

        file_put_contents($pedido, json_encode([
            'motor' => $motor, 'voz' => $info['id'], 'velocidade' => $velocidade,
            'modelos' => self::pastaModelos(), 'referencia' => $referencia,
            'threads' => (int) Ferramentas::threads(), 'falas' => $falas,
        ], JSON_UNESCAPED_UNICODE));

        $threads = Ferramentas::threads();

        try {
            Ferramentas::rodar(
                [self::python($motor), '-I', base_path('scripts/video/narrar.py'), $pedido, $resultado],
                $dir, (int) config('video.narracao.timeout', 1800), null,
                array_merge(['PYTHONIOENCODING' => 'utf-8', 'TOKENIZERS_PARALLELISM' => 'false', 'HF_HOME' => self::pastaModelos().'/hf'], array_fill_keys(
                    ['VIDEO_THREADS', 'OMP_NUM_THREADS', 'MKL_NUM_THREADS', 'OPENBLAS_NUM_THREADS', 'RAYON_NUM_THREADS', 'NUMEXPR_NUM_THREADS', 'ORT_NUM_THREADS'],
                    $threads
                ))
            );
        } finally {
            @unlink($pedido);
            @unlink($resultado);
        }

        // O Chatterbox não tem controle de velocidade: ajusta no FFmpeg sem mudar o tom.
        if ($motor === 'chatterbox' && abs($velocidade - 1.0) > 0.01) {
            foreach ($falas as $f) {
                if (is_file($f['saida'])) {
                    $tmp = $f['saida'].'.tmp.wav';
                    Ferramentas::ffmpeg(['-i', $f['saida'], '-af', 'atempo='.Ferramentas::s($velocidade), $tmp], $dir, null, null, 300);
                    rename($tmp, $f['saida']);
                }
            }
        }
    }

    /**
     * O que a voz deve LER: símbolos e abreviações viram palavras
     * ("R$ 39,90" → "39 reais e 90 centavos", "80m²" → "80 metros quadrados").
     */
    public static function textoFalado(string $texto): string
    {
        $t = mb_scrub($texto, 'UTF-8');
        // Emojis e símbolos que a voz leria como lixo.
        $t = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}]/u', '', $t) ?? $t;

        $t = preg_replace_callback('/R\$\s*(\d{1,3}(?:\.\d{3})*|\d+)(?:,(\d{2}))?/u', function ($m) {
            $reais = str_replace('.', '', $m[1]);
            $centavos = isset($m[2]) ? (int) $m[2] : 0;

            return $reais.' '.($reais === '1' ? 'real' : 'reais').($centavos ? " e {$centavos} centavos" : '');
        }, $t) ?? $t;

        $t = preg_replace('/(\d)\s*m²/u', '$1 metros quadrados', $t) ?? $t;
        $t = preg_replace('/(\d)\s*m2\b/u', '$1 metros quadrados', $t) ?? $t;
        $t = preg_replace('/(\d)\s*km\b/u', '$1 quilômetros', $t) ?? $t;
        $t = preg_replace('/(\d)\s*%/u', '$1 por cento', $t) ?? $t;
        $t = preg_replace('/\b(\d{1,2})h(\d{2})\b/u', '$1 e $2', $t) ?? $t;
        $t = preg_replace('/\b(\d{1,2})h\b/u', '$1 horas', $t) ?? $t;
        $t = preg_replace('/\bn[º°]\s*/u', 'número ', $t) ?? $t;
        $t = preg_replace('/(\d)\.(\d{3})\b/u', '$1$2', $t) ?? $t; // 1.200 → 1200 (senão lê "um ponto duzentos")
        $t = str_replace(['&', '@', '+', '/', '#'], [' e ', 'arroba ', ' mais ', ' ', ''], $t);

        return trim(preg_replace('/\s+/u', ' ', $t) ?? $t);
    }

    /** Tempo de cada palavra (pelo tamanho, com pausa na pontuação) para a legenda. */
    public static function palavrasNoTempo(string $texto, float $inicio, float $duracao): array
    {
        $palavras = preg_split('/\s+/u', trim($texto), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (! $palavras) {
            return [];
        }

        $pesos = array_map(function ($w) {
            $p = max(2, mb_strlen(preg_replace('/[^\p{L}\p{N}]/u', '', $w) ?? $w));
            if (preg_match('/[.!?…]$/u', $w)) {
                $p += 5;
            } elseif (preg_match('/[,;:]$/u', $w)) {
                $p += 2;
            }

            return $p;
        }, $palavras);

        $total = array_sum($pesos);
        $t = $inicio;
        $saida = [];

        foreach ($palavras as $k => $w) {
            $d = $duracao * $pesos[$k] / $total;
            // A pausa da pontuação fica depois da palavra, não dentro dela.
            $fala = preg_match('/[.!?…,;:]$/u', $w) ? $d * 0.7 : $d;
            $saida[] = ['w' => $w, 'i' => round($t, 3), 'f' => round($t + max(0.08, $fala), 3)];
            $t += $d;
        }

        return $saida;
    }

    private static function duracaoWav(string $arquivo): float
    {
        if (! is_file($arquivo) || filesize($arquivo) < 46) {
            return 0.0;
        }

        $h = fopen($arquivo, 'rb');
        $cab = fread($h, 44);
        fclose($h);
        $info = unpack('vcanais/Vtaxa/Vbytes', substr($cab, 22, 12));

        return $info['bytes'] > 0 ? round((filesize($arquivo) - 44) / $info['bytes'], 3) : 0.0;
    }
}
