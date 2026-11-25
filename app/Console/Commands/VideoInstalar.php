<?php

namespace App\Console\Commands;

use App\Models\VideoProfile;
use App\Video\Armazenamento;
use App\Video\Ferramentas;
use App\Video\Render\Sons;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Prepara o servidor para o estúdio: pastas, kit de efeitos sonoros
 * (sintetizado pelo FFmpeg, sem download) e fontes (Google Fonts via
 * Fontsource, licença OFL — livre para uso comercial).
 *
 * Idempotente: rodar de novo só completa o que falta. Não instala programa
 * nenhum no sistema (FFmpeg e Claude: veja scripts/video/instalar-servidor.sh).
 */
class VideoInstalar extends Command
{
    protected $signature = 'video:instalar {--sem-fontes : Não baixa fontes} {--forcar : Regera os sons e baixa as fontes de novo}';

    protected $description = 'Prepara pastas, efeitos sonoros e fontes do Estúdio de vídeo';

    /** Pesos baixados por família (as de um peso só ficam só com ele). */
    private const PESOS = [
        'Montserrat' => [400, 700, 800], 'Poppins' => [400, 700, 800], 'Bebas Neue' => [400], 'Anton' => [400],
        'Inter' => [400, 700, 800], 'Sora' => [400, 700], 'Playfair Display' => [400, 700], 'Oswald' => [400, 700],
        'Archivo Black' => [400], 'DM Serif Display' => [400],
    ];

    public function handle(): int
    {
        if (! Ferramentas::podeExecutar()) {
            $this->error('proc_open está desligado no PHP CLI deste servidor. Veja leia/estudio-de-video.md.');

            return self::FAILURE;
        }

        $this->info('Pastas');
        foreach ([Armazenamento::raiz(), Armazenamento::biblioteca(), Sons::pasta(), self::pastaFontes()] as $pasta) {
            File::ensureDirectoryExists($pasta, 0775);
            $this->line("  ✓ $pasta");
        }

        $this->info('Efeitos sonoros (sintetizados)');
        try {
            $gerados = Sons::gerar((bool) $this->option('forcar'));
            $this->line('  ✓ '.($gerados ? implode(', ', $gerados) : 'já existiam'));
        } catch (\Throwable $e) {
            $this->error('  ✗ '.$e->getMessage());
        }

        if (! $this->option('sem-fontes')) {
            $this->info('Fontes (Google Fonts / OFL)');
            $this->fontes();
        }

        $this->newLine();
        $this->info('Pronto. Confira tudo com: php artisan video:diagnostico');

        return self::SUCCESS;
    }

    public static function pastaFontes(): string
    {
        return rtrim(str_replace('\\', '/', (string) config('video.ferramentas.fontes')), '/');
    }

    private function fontes(): void
    {
        foreach (VideoProfile::FONTES as $familia) {
            $id = Str::slug($familia);
            $ok = [];

            foreach (self::PESOS[$familia] ?? [400, 700] as $peso) {
                $destino = self::pastaFontes()."/{$id}-{$peso}.ttf";

                if (is_file($destino) && ! $this->option('forcar')) {
                    $ok[] = $peso;

                    continue;
                }

                // Subconjunto "latin" = alfabeto básico + Latin-1 (á, ã, ç, é, õ…): todo o
                // português. ATENÇÃO: "latin-ext" NÃO tem o alfabeto básico — com ele o
                // libass trocava letra por letra pela fonte reserva ("TESOURa").
                $url = "https://cdn.jsdelivr.net/fontsource/fonts/{$id}@latest/latin-{$peso}-normal.ttf";

                try {
                    $resposta = Http::timeout(30)->retry(2, 500)->get($url);

                    if ($resposta->successful() && strlen($resposta->body()) > 5000) {
                        file_put_contents($destino, $resposta->body());
                        $ok[] = $peso;
                    }
                } catch (\Throwable) {
                    // Tenta de novo abaixo.
                }

                if (! in_array($peso, $ok, true)) {
                    try {
                        $resposta = Http::timeout(30)->get($url);
                        if ($resposta->successful() && strlen($resposta->body()) > 5000) {
                            file_put_contents($destino, $resposta->body());
                            $ok[] = $peso;
                        }
                    } catch (\Throwable) {
                    }
                }
            }

            $this->line(($ok ? '  ✓ ' : '  ✗ ').$familia.($ok ? ' ('.implode(', ', $ok).')' : ' — não baixou (sem internet de saída?)'));
        }
    }
}
