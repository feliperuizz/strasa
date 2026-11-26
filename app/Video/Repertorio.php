<?php

namespace App\Video;

use App\Models\VideoJob;
use Illuminate\Support\Facades\File;

/**
 * Repertório de motion por cliente: as cenas que foram para vídeos finais.
 * Entra no contexto do Claude para os vídeos do cliente terem família visual
 * (mesma linguagem, peças que funcionaram) sem repetir o mesmo conceito.
 * Guardado em arquivo (fora do banco): VIDEO_STORAGE_PATH/repertorio/client-N.json
 */
class Repertorio
{
    private const MAXIMO = 40;

    public static function arquivo(int $clientId): string
    {
        return Armazenamento::raiz()."/repertorio/client-{$clientId}.json";
    }

    public static function ler(int $clientId): array
    {
        $f = self::arquivo($clientId);

        return is_file($f) ? (json_decode((string) file_get_contents($f), true) ?: []) : [];
    }

    /** Vídeo final aprovado: as cenas dele entram no repertório do cliente. */
    public static function guardar(VideoJob $job, array $plano): void
    {
        $cenas = array_values(array_filter($plano['elementos'] ?? [], fn ($e) => ($e['tipo'] ?? '') === 'cena'));
        if (! $cenas) {
            return;
        }
        $lista = array_values(array_filter(self::ler($job->client_id), fn ($c) => ($c['edicao'] ?? null) !== $job->id));
        foreach ($cenas as $c) {
            unset($c['inicio'], $c['em']);
            $lista[] = ['edicao' => $job->id, 'titulo' => $job->titulo, 'estilo' => $job->estilo, 'quando' => now()->toDateString(), 'cena' => $c];
        }
        $lista = array_slice($lista, -self::MAXIMO);
        File::ensureDirectoryExists(dirname(self::arquivo($job->client_id)), 0775);
        file_put_contents(self::arquivo($job->client_id), json_encode($lista, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
