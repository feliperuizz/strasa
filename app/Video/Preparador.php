<?php

namespace App\Video;

use App\Models\VideoJob;
use App\Models\VideoJobFile;
use App\Models\VideoProfile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Transforma os brutos em algo que o Claude consegue "ler" (edit-preparar):
 *
 *  - proxy leve por arquivo (SDR, 30 fps, lado menor 720, keyframe a cada
 *    0,5 s): prévias rápidas e análise sem tocar no original 4K;
 *  - áudio 16 kHz mono para a transcrição;
 *  - silêncios e trocas de cena;
 *  - folhas de quadros numeradas (4x3 miniaturas com arquivo e tempo
 *    gravados) — é assim que o Claude "vê" o material sem assistir;
 *  - logo do cliente em PNG e batidas da música.
 *
 * Cada passo é pulado se a saída já existe: se o processo cair no meio,
 * a nova tentativa continua de onde parou.
 */
class Preparador
{
    private Armazenamento $pasta;

    public function __construct(private VideoJob $job)
    {
        $this->pasta = $job->pasta();
        $this->pasta->garantir();
    }

    public function executar(callable $progresso): array
    {
        $arquivos = $this->job->arquivos()->where('status', 'pronto')->get();
        $estilo = $this->job->estiloInfo();
        $trabalho = $this->pasta->dir('trabalho');
        $this->fonteDosRotulos($trabalho);

        $limiteFolhas = $estilo['fala'] ? 1 : 2; // folhas por arquivo
        $inventario = [];
        $total = max(1, $arquivos->count());

        foreach ($arquivos->values() as $n => $arq) {
            $base = $n / $total;
            $passo = fn (float $f, string $msg) => $progresso($base + $f / $total, $msg);
            $chave = $arq->chave();
            $origem = $arq->caminhoAbsoluto();

            if (! is_file($origem)) {
                throw new FalhaDeFerramenta("O arquivo \"{$arq->nome_original}\" sumiu do servidor.");
            }

            $item = [
                'chave' => $chave,
                'nome' => $arq->nome_original,
                'papel' => $arq->papel,
                'tipo' => $arq->tipo,
                'duracao' => (float) $arq->duracao,
                'largura' => $arq->largura,
                'altura' => $arq->altura,
                'orientacao' => $arq->largura && $arq->altura ? ($arq->altura > $arq->largura ? 'vertical' : ($arq->altura == $arq->largura ? 'quadrado' : 'horizontal')) : null,
                'hdr' => $arq->hdr,
                'tem_audio' => $arq->tem_audio,
            ];

            if ($arq->tipo === 'imagem') {
                $passo(0.2, "Preparando a foto {$arq->nome_original}");
                $foto = "$trabalho/foto_{$chave}.png";
                if (! is_file($foto)) {
                    Ferramentas::ffmpeg(['-i', $origem, '-frames:v', '1', '-vf', "scale='min(2560,iw)':'min(2560,ih)':force_original_aspect_ratio=decrease", $foto], $trabalho, null, null, 120);
                }
                $item['folhas'] = [$this->folhaDeFoto($arq, $foto, $trabalho)];
                $inventario[$chave] = $item;

                continue;
            }

            if ($arq->tipo === 'video') {
                $passo(0.05, "Criando cópia leve de {$arq->nome_original}");
                $proxy = "$trabalho/proxy_{$chave}.mp4";
                if (! is_file($proxy)) {
                    $this->proxy($arq, $origem, $proxy, fn ($f) => $passo(0.05 + 0.55 * $f, "Criando cópia leve de {$arq->nome_original}"));
                }

                $passo(0.65, "Procurando trocas de cena em {$arq->nome_original}");
                $item['cenas'] = $this->cenas($proxy, "$trabalho/cenas_{$chave}.txt");

                $passo(0.75, "Montando a folha de quadros de {$arq->nome_original}");
                $item['folhas'] = $this->folhas($arq, $proxy, $trabalho, $limiteFolhas);
            }

            if ($arq->tem_audio) {
                $wav = "$trabalho/audio_{$chave}.wav";
                if (! is_file($wav)) {
                    $passo(0.85, "Separando o áudio de {$arq->nome_original}");
                    Ferramentas::ffmpeg(['-i', $origem, '-vn', '-ac', '1', '-ar', '16000', '-c:a', 'pcm_s16le', $wav], $trabalho, (float) $arq->duracao);
                }
                $item['silencios'] = $this->silencios($wav);
                $item['nivel_audio'] = $this->nivel($wav);
            }

            $inventario[$chave] = $item;
        }

        $progresso(0.96, 'Preparando a logo e a música');
        $this->logo($trabalho);
        $this->musica();

        file_put_contents("$trabalho/inventario.json", json_encode($inventario, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $inventario;
    }

    /* Passos ------------------------------------------------------------ */

    private function proxy(VideoJobFile $arq, string $origem, string $destino, callable $progresso): void
    {
        $vf = [];
        if ($arq->hdr && Ferramentas::temFiltro('zscale') && Ferramentas::temFiltro('tonemap')) {
            $vf[] = 'zscale=t=linear:npl=100,format=gbrpf32le,zscale=p=bt709,tonemap=hable:desat=0,zscale=t=bt709:m=bt709:r=tv';
        }
        $vf[] = 'fps=30';
        $vf[] = "scale='if(gt(iw,ih),-2,720)':'if(gt(iw,ih),720,-2)':flags=bicubic";
        $vf[] = 'format=yuv420p';

        $temp = $destino.'.parcial.mp4';
        Ferramentas::ffmpeg([
            '-i', $origem, '-vf', implode(',', $vf),
            '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '23', '-g', '15', '-keyint_min', '15',
            '-c:a', 'aac', '-b:a', '128k', '-ar', '48000', '-ac', '2',
            '-movflags', '+faststart', '-threads', Ferramentas::threads(), $temp,
        ], dirname($destino), (float) $arq->duracao, $progresso);

        // Só vira "pronto" inteiro: um proxy pela metade (processo morto) não é reaproveitado.
        rename($temp, $destino);
    }

    private function cenas(string $proxy, string $saida): array
    {
        if (! is_file($saida)) {
            Ferramentas::ffmpeg(['-i', $proxy, '-an', '-vf', "select='gt(scene,0.32)',metadata=print:file=".basename($saida), '-f', 'null', '-'], dirname($saida), null, null, 600);
        }

        preg_match_all('/pts_time:([\d.]+)/', (string) @file_get_contents($saida), $m);

        return array_map(fn ($t) => round((float) $t, 2), array_slice($m[1], 0, 80));
    }

    /**
     * Folhas 4x3 com o arquivo e o tempo gravados em cada quadro. O intervalo
     * cresce com a duração para caber em $maxFolhas folhas.
     */
    private function folhas(VideoJobFile $arq, string $proxy, string $trabalho, int $maxFolhas): array
    {
        $dir = "$trabalho/folhas";
        File::ensureDirectoryExists($dir, 0775);
        $chave = $arq->chave();
        $dur = max(0.5, (float) $arq->duracao);
        $porFolha = 12;
        $intervalo = max(0.75, $dur / ($porFolha * $maxFolhas));
        $quadros = min($porFolha * $maxFolhas, (int) ceil($dur / $intervalo));
        $folhas = (int) ceil($quadros / $porFolha);
        $vertical = ($arq->altura ?? 0) >= ($arq->largura ?? 0);
        $mini = $vertical ? 'scale=270:-2' : 'scale=360:-2';

        $existentes = glob("$dir/{$chave}_*.png") ?: [];

        if (! $existentes) {
            // Rótulo (arquivo + tempo) só se o FFmpeg tiver drawtext; o estático do
            // servidor pode vir sem. Os tempos vão por escrito no contexto de qualquer jeito.
            $rotulo = is_file("$trabalho/rotulo.ttf") && Ferramentas::temFiltro('drawtext')
                ? ",drawbox=x=0:y=ih-34:w=iw:h=34:color=black@0.65:t=fill,drawtext=fontfile=rotulo.ttf:fontsize=22:fontcolor=white:x=8:y=h-28:text='{$chave}  %{pts\\:flt}s'"
                : '';

            Ferramentas::ffmpeg([
                '-i', $proxy, '-an',
                '-vf', 'fps=1/'.Ferramentas::s($intervalo).",$mini$rotulo,tile=4x3:padding=4:color=0x111111",
                '-frames:v', (string) $folhas, "folhas/{$chave}_%02d.png",
            ], $trabalho, null, null, 600);

            $existentes = glob("$dir/{$chave}_*.png") ?: [];
        }

        sort($existentes);
        $tempos = [];
        for ($i = 0; $i < $quadros; $i++) {
            $tempos[] = round($i * $intervalo, 2);
        }

        return array_values(array_map(fn ($f, $k) => [
            'imagem' => $f,
            'tempos' => array_slice($tempos, $k * $porFolha, $porFolha),
        ], $existentes, array_keys($existentes)));
    }

    private function folhaDeFoto(VideoJobFile $arq, string $foto, string $trabalho): array
    {
        $dir = "$trabalho/folhas";
        File::ensureDirectoryExists($dir, 0775);
        $saida = "$dir/{$arq->chave()}_01.png";

        if (! is_file($saida)) {
            Ferramentas::ffmpeg(['-i', $foto, '-frames:v', '1', '-vf', 'scale=720:720:force_original_aspect_ratio=decrease', $saida], $trabalho, null, null, 60);
        }

        return ['imagem' => $saida, 'tempos' => [0]];
    }

    /** Pausas acima de 0,3 s (só mapeia; quem decide corte é o plano). */
    private function silencios(string $wav): array
    {
        try {
            $stderr = Ferramentas::ffmpeg(['-i', $wav, '-af', 'silencedetect=n=-34dB:d=0.3', '-f', 'null', '-'], dirname($wav), null, null, 300);
        } catch (FalhaDeFerramenta) {
            return [];
        }

        preg_match_all('/silence_start: ([\d.]+)/', $stderr, $ini);
        preg_match_all('/silence_end: ([\d.]+)/', $stderr, $fim);
        $saida = [];
        foreach ($ini[1] as $i => $s) {
            if (isset($fim[1][$i])) {
                $saida[] = [round((float) $s, 2), round((float) $fim[1][$i], 2)];
            }
        }

        return array_slice($saida, 0, 300);
    }

    /** Volume médio (dB): avisa áudio baixo demais ou estourado. */
    private function nivel(string $wav): ?float
    {
        try {
            $stderr = Ferramentas::ffmpeg(['-i', $wav, '-af', 'volumedetect', '-f', 'null', '-'], dirname($wav), null, null, 300);
        } catch (FalhaDeFerramenta) {
            return null;
        }

        return preg_match('/mean_volume: (-?[\d.]+) dB/', $stderr, $m) ? (float) $m[1] : null;
    }

    /** Logo de vídeo do perfil (PNG transparente) ou a logo do cliente, convertida para PNG. */
    private function logo(string $trabalho): void
    {
        $destino = "$trabalho/logo.png";

        if (is_file($destino)) {
            return;
        }

        $perfil = VideoProfile::doCliente($this->job->client);
        $origem = $perfil->caminhoLogo();
        $temp = null;

        if (! $origem || ! is_file($origem)) {
            $client = $this->job->client;
            if (! $client->logo_path) {
                return;
            }
            try {
                $conteudo = Storage::disk($client->logo_disk ?: 's3')->get($client->logo_path);
            } catch (\Throwable) {
                $conteudo = null;
            }
            if (! $conteudo) {
                return;
            }
            $temp = "$trabalho/logo_original.".(pathinfo($client->logo_path, PATHINFO_EXTENSION) ?: 'img');
            file_put_contents($temp, $conteudo);
            $origem = $temp;
        }

        try {
            Ferramentas::ffmpeg(['-i', $origem, '-frames:v', '1', '-vf', "scale='min(1000,iw)':-1,format=rgba", $destino], $trabalho, null, null, 60);
        } catch (FalhaDeFerramenta) {
            // Logo em formato que o FFmpeg não lê (SVG): o vídeo sai sem logo, com aviso.
            $this->job->registrar('sistema', 'Não consegui usar a logo do cliente (formato não suportado). Envie um PNG na identidade de vídeo.');
        } finally {
            if ($temp) {
                @unlink($temp);
            }
        }
    }

    private function musica(): void
    {
        $musica = $this->job->musica;

        if (! $musica || $musica->batidas !== null || ! is_file($musica->caminhoAbsoluto())) {
            return;
        }

        try {
            $r = Batidas::analisar($musica->caminhoAbsoluto());
            $musica->forceFill(['bpm' => $r['bpm'], 'batidas' => $r['batidas'], 'duracao' => $musica->duracao ?: $r['duracao']])->save();
        } catch (\Throwable) {
            $musica->forceFill(['batidas' => []])->save();
        }
    }

    /** Fonte para gravar arquivo e tempo nas miniaturas (relativa, sem caminho com ":" no filtro). */
    private function fonteDosRotulos(string $trabalho): void
    {
        $destino = "$trabalho/rotulo.ttf";
        $fonte = rtrim(str_replace('\\', '/', (string) config('video.ferramentas.fontes')), '/').'/montserrat-700.ttf';

        if (! is_file($destino) && is_file($fonte)) {
            @copy($fonte, $destino);
        }
    }
}
