<?php

namespace App\Video;

use App\Models\VideoJob;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Arquivo do vídeo final no Google Drive (ou outro disco do Laravel).
 *
 * A edição acontece no servidor (upload, render, ajustes: tudo local e rápido).
 * Quando o final fica pronto, ele sobe para o Drive e sai do servidor; o
 * player e o download passam a buscar de lá. A capa fica dos dois lados
 * (é pequena e aparece em toda lista). Os brutos seguem a retenção normal
 * (ainda servem para ajustes e "refazer").
 */
class Nuvem
{
    public static function disco(): ?string
    {
        $disco = (string) config('video.nuvem.disco');

        if ($disco === 'nenhum') {
            return null;
        }
        if ($disco !== '') {
            return config("filesystems.disks.$disco") ? $disco : null;
        }

        return filled(config('filesystems.disks.google.refreshToken')) ? 'google' : null;
    }

    public static function ativo(): bool
    {
        return self::disco() !== null;
    }

    /** Sobe o final (e a capa) e libera o servidor. Lança exceção se falhar (o local continua). */
    public static function arquivar(VideoJob $job): void
    {
        $disco = self::disco();
        $final = $job->final_path ? $job->pasta()->absoluto($job->final_path) : null;

        if (! $disco || ! $final || ! is_file($final)) {
            throw new FalhaDeFerramenta('Não há vídeo final no servidor para enviar ao Drive.');
        }

        $client = $job->client()->withoutGlobalScopes()->first();
        $pasta = trim((string) config('video.nuvem.pasta'), '/').'/'.self::limpo($client?->name ?: 'Cliente');
        $nome = now()->format('Y-m-d').' '.self::limpo($job->titulo).' v'.$job->final_versao;

        $caminhoFinal = "$pasta/$nome.mp4";
        $stream = fopen($final, 'rb');
        try {
            $ok = Storage::disk($disco)->writeStream($caminhoFinal, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
        if (! $ok) {
            throw new FalhaDeFerramenta('O Drive recusou o envio do vídeo final.');
        }

        $caminhoCapa = null;
        if ($job->capa_path && is_file($capa = $job->pasta()->absoluto($job->capa_path))) {
            $caminhoCapa = "$pasta/$nome - capa.png";
            rescue(fn () => Storage::disk($disco)->put($caminhoCapa, file_get_contents($capa)), null, false);
        }

        $tamanho = filesize($final);
        $job->forceFill([
            'nuvem_disco' => $disco, 'nuvem_final' => $caminhoFinal, 'nuvem_capa' => $caminhoCapa,
            'nuvem_tamanho' => $tamanho, 'nuvem_status' => 'salvo', 'nuvem_erro' => null, 'nuvem_em' => now(),
        ])->save();

        // Libera o servidor: o final (agora no Drive), prévias antigas e o cache do render.
        $liberado = $tamanho;
        @unlink($final);
        foreach (glob($job->pasta()->absoluto('renders/previa_v*.mp4')) ?: [] as $previa) {
            if ($job->previa_path && str_ends_with(str_replace('\\', '/', $previa), $job->previa_path)) {
                continue; // a prévia atual fica (a aba de revisão mostra)
            }
            $liberado += (int) @filesize($previa);
            @unlink($previa);
        }
        $cache = $job->pasta()->absoluto('renders/cache');
        if (is_dir($cache)) {
            foreach (File::allFiles($cache) as $f) {
                $liberado += $f->getSize();
            }
            File::deleteDirectory($cache);
        }

        $job->registrar('sistema', 'Vídeo final salvo no Drive ('.$caminhoFinal.'). '.round($liberado / 1048576).' MB liberados no servidor.');
    }

    /** Stream do arquivo no Drive (o final que já saiu do servidor). */
    public static function abrir(VideoJob $job, string $qual = 'final')
    {
        $caminho = $qual === 'capa' ? $job->nuvem_capa : $job->nuvem_final;
        abort_unless($job->nuvem_disco && $caminho, 404);

        return Storage::disk($job->nuvem_disco)->readStream($caminho);
    }

    /** Baixa o final do Drive para um arquivo temporário (ex.: anexar ao card). */
    public static function baixarTemporario(VideoJob $job): string
    {
        $stream = self::abrir($job);
        abort_if(! $stream, 404, 'O vídeo não está mais no Drive.');
        $destino = $job->pasta()->dir('renders').'/drive_temp_'.Str::random(6).'.mp4';
        $saida = fopen($destino, 'wb');
        stream_copy_to_stream($stream, $saida);
        fclose($saida);
        if (is_resource($stream)) {
            fclose($stream);
        }

        return $destino;
    }

    public static function apagar(VideoJob $job): void
    {
        if (! $job->nuvem_disco) {
            return;
        }
        foreach (array_filter([$job->nuvem_final, $job->nuvem_capa]) as $caminho) {
            rescue(fn () => Storage::disk($job->nuvem_disco)->delete($caminho), null, false);
        }
    }

    private static function limpo(string $texto): string
    {
        $t = preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/u', ' ', $texto) ?? $texto;

        return Str::limit(trim(preg_replace('/\s+/u', ' ', $t) ?? $t), 80, '');
    }
}
