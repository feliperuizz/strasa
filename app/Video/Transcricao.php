<?php

namespace App\Video;

use Illuminate\Support\Facades\Http;

/**
 * Transcrição com tempo por palavra (edit-preparar).
 *
 * Motores (config video.transcricao.driver):
 *  - api: OpenAI ou compatível (Groq...). Recomendado em VPS pequena: não
 *    gasta CPU/RAM do servidor. Áudio vai em MP3 mono leve.
 *  - faster_whisper: Python local (scripts/video/transcrever.py), CPU int8.
 *  - ffmpeg: filtro whisper do FFmpeg 8 (whisper.cpp). Só dá tempo por
 *    trecho; as palavras são distribuídas dentro do trecho pelo tamanho.
 *  - nenhum: sem legenda automática.
 *
 * Os termos do briefing (nomes, marcas) vão como contexto — mas contexto
 * pode inventar frase, então quem decide o corte confere pela pausa.
 */
class Transcricao
{
    public static function driver(): string
    {
        return (string) config('video.transcricao.driver', 'nenhum');
    }

    public static function disponivel(): bool
    {
        return match (self::driver()) {
            'api' => filled(config('video.transcricao.api_chave')),
            'faster_whisper' => (bool) Ferramentas::localizar((string) config('video.transcricao.python')),
            'ffmpeg' => Ferramentas::temFiltro('whisper') && is_file((string) config('video.transcricao.ffmpeg_modelo')),
            default => false,
        };
    }

    /** @return array [['w' => 'Olá', 'i' => 0.52, 'f' => 0.81], ...] no tempo do arquivo */
    public static function transcrever(string $wav, string $contexto = '', float $duracao = 0): array
    {
        $palavras = match (self::driver()) {
            'api' => self::api($wav, $contexto),
            'faster_whisper' => self::fasterWhisper($wav, $contexto),
            'ffmpeg' => self::ffmpeg($wav, $duracao),
            default => [],
        };

        return array_values(array_filter(array_map(fn ($p) => [
            'w' => trim((string) $p['w']),
            'i' => round(max(0, (float) $p['i']), 3),
            'f' => round(max((float) $p['i'], (float) $p['f']), 3),
        ], $palavras), fn ($p) => $p['w'] !== '' && ! preg_match('/^[[(].*[])]$/u', $p['w'])));
    }

    private static function api(string $wav, string $contexto): array
    {
        $mp3 = $wav.'.mp3';
        if (! is_file($mp3)) {
            Ferramentas::ffmpeg(['-i', $wav, '-ac', '1', '-c:a', 'libmp3lame', '-b:a', '48k', $mp3], dirname($wav), null, null, 600);
        }

        if (filesize($mp3) > 24 * 1024 * 1024) {
            throw new FalhaDeFerramenta('O áudio passou de 24 MB (limite da API de transcrição). Envie trechos menores.');
        }

        $resposta = Http::timeout((int) config('video.transcricao.timeout', 1800))
            ->withToken((string) config('video.transcricao.api_chave'))
            ->attach('file', fopen($mp3, 'r'), 'audio.mp3')
            ->post(rtrim((string) config('video.transcricao.api_url'), '/').'/audio/transcriptions', [
                'model' => (string) config('video.transcricao.api_modelo', 'whisper-1'),
                'language' => (string) config('video.transcricao.idioma', 'pt'),
                'response_format' => 'verbose_json',
                'timestamp_granularities[]' => 'word',
                'prompt' => mb_substr($contexto, 0, 800),
            ]);

        if (! $resposta->successful()) {
            throw new FalhaDeFerramenta('A API de transcrição recusou o pedido ('.$resposta->status().'): '.mb_substr((string) $resposta->json('error.message', $resposta->body()), 0, 300));
        }

        $palavras = array_map(fn ($w) => ['w' => $w['word'] ?? '', 'i' => $w['start'] ?? 0, 'f' => $w['end'] ?? 0], (array) $resposta->json('words', []));

        // Alguns provedores devolvem só os trechos; distribui as palavras neles.
        if (! $palavras) {
            foreach ((array) $resposta->json('segments', []) as $seg) {
                $palavras = array_merge($palavras, self::distribuir((string) ($seg['text'] ?? ''), (float) ($seg['start'] ?? 0), (float) ($seg['end'] ?? 0)));
            }
        }

        return self::reporPontuacao($palavras, (string) $resposta->json('text', ''));
    }

    private static function fasterWhisper(string $wav, string $contexto): array
    {
        $saida = $wav.'.palavras.json';

        Ferramentas::rodar([
            (string) config('video.transcricao.python'), '-I', base_path('scripts/video/transcrever.py'),
            $wav, $saida,
            '--modelo', (string) config('video.transcricao.modelo_local', 'small'),
            '--idioma', (string) config('video.transcricao.idioma', 'pt'),
            '--contexto', mb_substr($contexto, 0, 600),
            '--threads', Ferramentas::threads(),
        ], dirname($wav), (int) config('video.transcricao.timeout', 1800), null, array_merge(['PYTHONIOENCODING' => 'utf-8', 'TOKENIZERS_PARALLELISM' => 'false'], array_fill_keys(
            // Limite de threads da hospedagem compartilhada (ver transcrever.py).
            ['VIDEO_THREADS', 'OMP_NUM_THREADS', 'MKL_NUM_THREADS', 'OPENBLAS_NUM_THREADS', 'RAYON_NUM_THREADS', 'NUMEXPR_NUM_THREADS', 'ORT_NUM_THREADS'],
            Ferramentas::threads()
        )));

        $dados = json_decode((string) @file_get_contents($saida), true);

        if (! is_array($dados)) {
            throw new FalhaDeFerramenta('O transcrever.py não gerou o resultado esperado.');
        }

        return $dados['palavras'] ?? [];
    }

    private static function ffmpeg(string $wav, float $duracao): array
    {
        $saida = basename($wav).'.whisper.json';
        $dir = dirname($wav);
        @unlink("$dir/$saida");

        $modelo = Ferramentas::relativo($dir, (string) config('video.transcricao.ffmpeg_modelo'));
        Ferramentas::ffmpeg([
            '-i', $wav, '-af',
            'whisper=model='.$modelo.':language='.config('video.transcricao.idioma', 'pt').':queue=4:use_gpu=false:destination='.$saida.':format=json',
            '-f', 'null', '-',
        ], $dir, $duracao, null, (int) config('video.transcricao.timeout', 1800));

        $palavras = [];
        foreach (file("$dir/$saida", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $linha) {
            $seg = json_decode($linha, true);
            if (! is_array($seg) || ! isset($seg['text'])) {
                continue;
            }
            // O filtro grava em milissegundos.
            $palavras = array_merge($palavras, self::distribuir((string) $seg['text'], (float) $seg['start'] / 1000, (float) $seg['end'] / 1000));
        }

        return $palavras;
    }

    /** Espalha as palavras de um trecho pelo tempo, proporcional ao tamanho de cada uma. */
    public static function distribuir(string $texto, float $inicio, float $fim): array
    {
        $palavras = preg_split('/\s+/u', trim($texto)) ?: [];
        $palavras = array_values(array_filter($palavras, fn ($p) => $p !== ''));
        $pesos = array_map(fn ($p) => mb_strlen($p) + 2, $palavras);
        $soma = array_sum($pesos) ?: 1;
        $t = $inicio;
        $saida = [];

        foreach ($palavras as $i => $p) {
            $d = ($fim - $inicio) * $pesos[$i] / $soma;
            $saida[] = ['w' => $p, 'i' => $t, 'f' => $t + $d * 0.9];
            $t += $d;
        }

        return $saida;
    }

    /** A API por palavra vem sem pontuação; recupera do texto completo (ajuda a quebrar frases). */
    private static function reporPontuacao(array $palavras, string $texto): array
    {
        $tokens = preg_split('/\s+/u', trim($texto)) ?: [];
        $j = 0;

        foreach ($palavras as &$p) {
            $limpa = mb_strtolower(preg_replace('/[^\p{L}\p{N}]/u', '', $p['w']) ?? '');
            for ($k = $j; $k < min(count($tokens), $j + 4); $k++) {
                if (mb_strtolower(preg_replace('/[^\p{L}\p{N}]/u', '', $tokens[$k]) ?? '') === $limpa) {
                    $p['w'] = $tokens[$k];
                    $j = $k + 1;
                    break;
                }
            }
        }

        return $palavras;
    }

    /**
     * O arquivo que o Claude lê inteiro (prep_frases.py do pacote): uma frase
     * por linha com o tempo de origem, as pausas, hesitações e as notas de
     * edição faladas ("nota de edição … fim da nota") marcadas, e o tempo de
     * cada palavra logo abaixo para cortes e gatilhos precisos.
     */
    public static function frases(array $porArquivo, array $inventario): string
    {
        $saida = [];

        foreach ($porArquivo as $chave => $palavras) {
            $info = $inventario[$chave] ?? [];
            $saida[] = sprintf('## %s — "%s" (%.1f s)', $chave, $info['nome'] ?? $chave, $info['duracao'] ?? 0);

            if (! $palavras) {
                $saida[] = '(sem fala reconhecida)';
                $saida[] = '';

                continue;
            }

            $frase = [];
            $emNota = false;
            $anterior = null;

            $fechar = function () use (&$frase, &$saida, $chave, &$emNota) {
                if (! $frase) {
                    return;
                }
                $texto = implode(' ', array_column($frase, 'w'));
                $marca = '';
                if (preg_match('/nota de edi[cç][aã]o/iu', $texto)) {
                    $emNota = true;
                }
                if ($emNota) {
                    $marca = ' ⚑NOTA DE EDIÇÃO';
                }
                if (preg_match('/fim da nota/iu', $texto)) {
                    $emNota = false;
                }
                if (preg_match('/^(h+u+m+|hm+|a+h+|ã+h*|é+|e+h+)[.,…]*$/iu', $texto)) {
                    $marca .= ' (hesitação)';
                }
                $saida[] = sprintf('[%s %.2f–%.2f] %s%s', $chave, $frase[0]['i'], end($frase)['f'], $texto, $marca);
                $saida[] = '   ⤷ '.implode(' · ', array_map(fn ($p) => $p['w'].' '.number_format($p['i'], 2, '.', ''), $frase));
                $frase = [];
            };

            foreach ($palavras as $p) {
                if ($anterior !== null) {
                    $pausa = $p['i'] - $anterior['f'];
                    if ($pausa > 0.6 || preg_match('/[.!?…]$/u', $anterior['w'])) {
                        $fechar();
                    }
                    if ($pausa > 0.6) {
                        $saida[] = sprintf('   … pausa %.1f s', $pausa);
                    }
                }
                $frase[] = $p;
                $anterior = $p;
            }
            $fechar();
            $saida[] = '';
        }

        return implode("\n", $saida);
    }
}
