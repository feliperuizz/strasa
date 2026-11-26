<?php

namespace App\Video;

use App\Models\VideoAsset;
use App\Video\Render\Motion;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Acha o logo de uma marca/app pelo nome, para os componentes de motion.
 *
 * Ordem (o primeiro que achar vale):
 *   1. Biblioteca da agência (logo enviado pela equipe) — a palavra final;
 *   2. Simple Icons no servidor (vetor, ~3.400 marcas);
 *   3. cache de buscas anteriores (vale para todos os clientes);
 *   4. App Store (API pública da Apple): o ícone oficial do app em 512 px —
 *      resolve CapCut, Premiere, Photoshop, Canva, ChatGPT…;
 *   5. Wikipédia (pt/en): só arquivo que se declara logo/ícone (a imagem
 *      principal da página muitas vezes é foto da fachada);
 *   6. site da empresa (apple-touch-icon / manifest), quando o plano dá o domínio.
 * Nada achado → o motor desenha o bloco com a sigla (nunca quebra o vídeo).
 *
 * Quem busca é o servidor, não o Claude: o Claude roda sem internet (o vídeo
 * do cliente pode ter instrução escondida) e só escreve o nome da marca.
 */
class Logos
{
    private const AGENTE = 'StrasaEstudio/1.0 (+video editor)';

    /**
     * Apps que aparecem muito nos vídeos e têm nome ambíguo: busca pelo nome
     * oficial e exige o fabricante ("Premiere" sozinho achava o app de futebol).
     * chave => [nome oficial, palavra que tem que estar no fabricante]
     */
    private const CONHECIDOS = [
        'premiere' => ['Adobe Premiere', 'adobe'], 'premierepro' => ['Adobe Premiere', 'adobe'],
        'adobepremiere' => ['Adobe Premiere', 'adobe'], 'adobepremierepro' => ['Adobe Premiere', 'adobe'],
        'photoshop' => ['Adobe Photoshop', 'adobe'], 'adobephotoshop' => ['Adobe Photoshop', 'adobe'],
        'lightroom' => ['Adobe Lightroom', 'adobe'], 'adobelightroom' => ['Adobe Lightroom', 'adobe'],
        'illustrator' => ['Adobe Illustrator', 'adobe'], 'adobeillustrator' => ['Adobe Illustrator', 'adobe'],
        'aftereffects' => ['Adobe After Effects', 'adobe'], 'adobeaftereffects' => ['Adobe After Effects', 'adobe'],
        'adobeexpress' => ['Adobe Express', 'adobe'], 'finalcut' => ['Final Cut Pro', 'apple'], 'finalcutpro' => ['Final Cut Pro', 'apple'],
        'capcut' => ['CapCut', 'bytedance'], 'canva' => ['Canva', 'canva'], 'chatgpt' => ['ChatGPT', 'openai'],
        'gemini' => ['Google Gemini', 'google'], 'whatsappbusiness' => ['WhatsApp Business', 'whatsapp'],
        'inshot' => ['InShot', 'inshot'], 'imovie' => ['iMovie', 'apple'],
    ];

    /** Busca que não achou nada não tenta de novo por este tempo (dias). */
    private const ESPERA_NEGATIVA_DIAS = 7;

    public static function chave(string $nome): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii($nome))) ?? '';
    }

    public static function pastaWeb(): string
    {
        return Motion::pastaIcones().'/web';
    }

    /**
     * Resolve os logos de todos os componentes do plano (apps, cards, notificação, marca).
     * Devolve o plano com 'imagem'/'forma' ou 'logo' (slug) preenchidos.
     */
    public static function resolverPlano(array $plano, int $companyId, ?int $clientId, ?callable $aoBuscar = null): array
    {
        $cache = [];
        $resolver = function (array $item) use (&$cache, $companyId, $clientId, $aoBuscar): array {
            $nome = trim((string) ($item['logo'] ?? ''));
            if ($nome === '' || ! empty($item['sigla'])) {
                return $item;
            }
            $k = self::chave($nome).'|'.($item['site'] ?? '');
            $cache[$k] ??= self::resolver($nome, $item['site'] ?? null, $companyId, $clientId, $aoBuscar);
            $r = $cache[$k];
            unset($item['imagem'], $item['forma']);
            if ($r && $r['tipo'] === 'marca') {
                $item['logo'] = $r['slug'];
            } elseif ($r) {
                $item['imagem'] = $r['arquivo'];
                $item['forma'] = $r['forma'];
            }

            return $item;
        };

        foreach ($plano['elementos'] ?? [] as $n => $el) {
            if (! Motion::eh($el['tipo'] ?? '')) {
                continue;
            }
            if (in_array($el['tipo'], ['notificacao', 'marca'], true)) {
                $plano['elementos'][$n] = $resolver($el);
            }
            if (isset($el['itens']) && in_array($el['tipo'], ['apps', 'cards'], true)) {
                $plano['elementos'][$n]['itens'] = array_map(fn ($i) => is_array($i) ? $resolver($i) : $i, $el['itens']);
            }
            // Cena livre: camadas "logo" em qualquer nível (grupos dentro de grupos).
            if ($el['tipo'] === 'cena' && ! empty($el['camadas'])) {
                $andar = function (array $camadas) use (&$andar, $resolver): array {
                    return array_map(function ($c) use (&$andar, $resolver) {
                        if (($c['tipo'] ?? '') === 'logo') {
                            $c = $resolver($c);
                        }
                        if (! empty($c['filhos'])) {
                            $c['filhos'] = $andar($c['filhos']);
                        }

                        return $c;
                    }, $camadas);
                };
                $plano['elementos'][$n]['camadas'] = $andar($el['camadas']);
            }
        }

        return $plano;
    }

    /** @return array{tipo: string, slug?: string, arquivo?: string, forma?: string, fonte?: string}|null */
    public static function resolver(string $nome, ?string $site, int $companyId, ?int $clientId, ?callable $aoBuscar = null): ?array
    {
        $chave = self::chave($nome);
        if ($chave === '') {
            return null;
        }

        // 1. Biblioteca da agência.
        $asset = VideoAsset::withoutGlobalScopes()->where('company_id', $companyId)->where('tipo', 'logo')
            ->where(fn ($q) => $q->whereNull('client_id')->when($clientId, fn ($x) => $x->orWhere('client_id', $clientId)))
            ->orderByDesc('client_id')->get()->first(fn ($a) => self::chave($a->nome) === $chave);
        if ($asset && is_file($asset->caminhoAbsoluto())) {
            return ['tipo' => 'imagem', 'arquivo' => $asset->caminhoAbsoluto(), 'forma' => $asset->humor === 'app' ? 'app' : 'logo', 'fonte' => 'biblioteca'];
        }

        // 2. Simple Icons (pelo slug ou pelo nome da marca).
        if ($slug = self::simpleIcons($chave)) {
            return ['tipo' => 'marca', 'slug' => $slug];
        }

        // 3. Cache.
        $pasta = self::pastaWeb();
        File::ensureDirectoryExists($pasta, 0775);
        $meta = "$pasta/$chave.json";
        if (is_file($meta)) {
            $m = json_decode((string) file_get_contents($meta), true) ?: [];
            if (! empty($m['arquivo']) && is_file("$pasta/{$m['arquivo']}")) {
                return ['tipo' => 'imagem', 'arquivo' => "$pasta/{$m['arquivo']}", 'forma' => $m['forma'] ?? 'logo', 'fonte' => $m['fonte'] ?? ''];
            }
            if (! empty($m['nada']) && time() - (int) ($m['quando'] ?? 0) < self::ESPERA_NEGATIVA_DIAS * 86400 && ($m['site'] ?? null) === $site) {
                return null;
            }
        }

        if ($aoBuscar) {
            $aoBuscar($nome);
        }

        // 4–6. Internet (apps conhecidos: pelo nome oficial e com o fabricante certo).
        [$termo, $fabricante] = self::CONHECIDOS[$chave] ?? [$nome, null];
        foreach ([fn () => self::appStore($termo, $fabricante), fn () => self::wikipedia($termo), fn () => $site ? self::site($site) : null] as $busca) {
            try {
                $achado = $busca();
            } catch (\Throwable) {
                $achado = null;
            }
            if ($achado && ($arquivo = self::baixar($achado['url'], "$pasta/$chave"))) {
                file_put_contents($meta, json_encode(['nome' => $nome, 'arquivo' => basename($arquivo), 'forma' => $achado['forma'],
                    'fonte' => $achado['fonte'], 'url' => $achado['url'], 'titulo' => $achado['titulo'] ?? null, 'quando' => time()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

                return ['tipo' => 'imagem', 'arquivo' => $arquivo, 'forma' => $achado['forma'], 'fonte' => $achado['fonte']];
            }
        }

        file_put_contents($meta, json_encode(['nome' => $nome, 'nada' => true, 'site' => $site, 'quando' => time()]));

        return null;
    }

    private static function simpleIcons(string $chave): ?string
    {
        $pasta = Motion::pastaIcones();
        if (is_file("$pasta/marcas/$chave.svg")) {
            return $chave;
        }
        static $porNome = null;
        if ($porNome === null) {
            $porNome = [];
            foreach (json_decode((string) @file_get_contents("$pasta/marcas.json"), true) ?: [] as $m) {
                $porNome[self::chave($m['title'] ?? '')] ??= $m['slug'];
            }
        }

        return $porNome[$chave] ?? null;
    }

    /**
     * Ícone oficial do app (App Store; iPhone e depois Mac). O nome tem que
     * estar no começo do título (antes do ":"/"-") ou no fabricante — senão
     * "After Effects" casava com "Shot Fx - After Effects Video".
     */
    private static function appStore(string $nome, ?string $fabricante = null): ?array
    {
        $palavras = self::palavras($nome);
        if (! $palavras) {
            return null;
        }

        foreach ([['software', 'br'], ['software', 'us'], ['macSoftware', 'us']] as [$tipo, $pais]) {
            $r = Http::timeout(10)->withUserAgent(self::AGENTE)->get('https://itunes.apple.com/search', [
                'term' => $nome, 'entity' => $tipo, 'country' => $pais, 'limit' => 8,
            ]);
            if (! $r->successful()) {
                continue;
            }
            $melhor = null;
            foreach ((array) $r->json('results', []) as $i => $app) {
                $vendedor = self::palavras($app['sellerName'] ?? '');
                if ($fabricante && ! in_array($fabricante, $vendedor, true)) {
                    continue;
                }
                // Só o começo do título: "Premiere: Jogos ao vivo" → "premiere"; "Shot Fx - After Effects" → "shot fx".
                $cabeca = self::palavras(preg_split('/\s[-–|]\s|:\s|\s\|/u', (string) ($app['trackName'] ?? ''))[0] ?? '');
                $doFabricante = ! array_diff($palavras, $vendedor);
                if (array_diff($palavras, array_merge($cabeca, $vendedor)) || (! in_array($palavras[0], $cabeca, true) && ! $doFabricante)) {
                    continue;
                }
                $nota = 10 - $i + ($cabeca === $palavras || $cabeca === array_slice($palavras, -max(1, count($cabeca))) ? 8 : 0) + ($doFabricante ? 4 : 0);
                if (! $melhor || $nota > $melhor[0]) {
                    $melhor = [$nota, $app];
                }
            }
            if ($melhor && ! empty($melhor[1]['artworkUrl512'] ?? $melhor[1]['artworkUrl100'] ?? null)) {
                $url = $melhor[1]['artworkUrl512'] ?? preg_replace('#/\d+x\d+bb\.#', '/512x512bb.', $melhor[1]['artworkUrl100']);
                $url = preg_replace('#/\d+x\d+bb\.(jpg|png|webp)$#', '/512x512bb.png', (string) $url);

                return ['url' => $url, 'forma' => 'app', 'fonte' => 'appstore', 'titulo' => $melhor[1]['trackName'] ?? null];
            }
        }

        return null;
    }

    /** Logo pela Wikipédia: só arquivo com "logo"/"icon" no nome e página com o nome da marca. */
    private static function wikipedia(string $nome): ?array
    {
        $palavras = self::palavras($nome);

        foreach (['pt', 'en'] as $lingua) {
            $r = Http::timeout(10)->withUserAgent(self::AGENTE)->get("https://$lingua.wikipedia.org/w/api.php", [
                'action' => 'query', 'format' => 'json', 'generator' => 'search', 'gsrsearch' => $nome, 'gsrlimit' => 3,
                'prop' => 'pageimages', 'piprop' => 'thumbnail|name', 'pithumbsize' => 512, 'pilicense' => 'any',
            ]);
            $paginas = (array) $r->json('query.pages', []);
            usort($paginas, fn ($a, $b) => ($a['index'] ?? 9) <=> ($b['index'] ?? 9));
            foreach ($paginas as $p) {
                $arquivo = (string) ($p['pageimage'] ?? '');
                $url = $p['thumbnail']['source'] ?? null;
                if (! $url || ! preg_match('/logo|icon|ícone|símbolo|marca/iu', $arquivo) || array_diff($palavras, self::palavras($p['title'] ?? ''))) {
                    continue;
                }

                return ['url' => $url, 'forma' => preg_match('/icon/i', $arquivo) ? 'app' : 'logo', 'fonte' => "wikipedia-$lingua", 'titulo' => $p['title']];
            }
        }

        return null;
    }

    /** Ícone do site (apple-touch-icon/manifest), com o serviço de favicons do Google de reserva. */
    private static function site(string $site): ?array
    {
        $dominio = strtolower(preg_replace('#^https?://|/.*$#', '', trim($site)));
        if (! preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $dominio)) {
            return null;
        }
        $base = "https://$dominio";
        $html = (string) rescue(fn () => Http::timeout(10)->withUserAgent(self::AGENTE)->get($base)->body(), '', false);

        $melhor = null;
        if (preg_match_all('/<link\b[^>]*>/i', $html, $tags)) {
            foreach ($tags[0] as $tag) {
                if (! preg_match('/rel=["\']?([^"\'>]+)/i', $tag, $rel) || ! preg_match('/href=["\']?([^"\'\s>]+)/i', $tag, $href)) {
                    continue;
                }
                $rel = strtolower($rel[1]);
                $tam = preg_match('/sizes=["\']?(\d+)/i', $tag, $s) ? (int) $s[1] : (str_contains($rel, 'apple-touch') ? 180 : 32);
                if (str_contains($rel, 'icon') && $tam >= 120 && (! $melhor || $tam > $melhor[0])) {
                    $melhor = [$tam, $href[1]];
                }
            }
        }
        if ($melhor) {
            $url = str_starts_with($melhor[1], 'http') ? $melhor[1] : (str_starts_with($melhor[1], '//') ? "https:{$melhor[1]}" : rtrim($base, '/').'/'.ltrim($melhor[1], '/'));

            return ['url' => html_entity_decode($url), 'forma' => 'app', 'fonte' => 'site'];
        }

        return ['url' => "https://www.google.com/s2/favicons?domain=$dominio&sz=256", 'forma' => 'app', 'fonte' => 'favicon'];
    }

    /** Baixa e converte para PNG (no máximo 512 px). Devolve o caminho ou null. */
    private static function baixar(string $url, string $destinoSemExt): ?string
    {
        $r = Http::timeout(15)->withUserAgent(self::AGENTE)->get($url);
        $corpo = $r->body();
        if (! $r->successful() || strlen($corpo) < 300 || strlen($corpo) > 6 * 1024 * 1024 || ! str_starts_with((string) $r->header('Content-Type'), 'image/') || str_contains((string) $r->header('Content-Type'), 'svg')) {
            return null;
        }
        $bruto = $destinoSemExt.'.baixado';
        file_put_contents($bruto, $corpo);
        $png = $destinoSemExt.'.png';
        try {
            Ferramentas::ffmpeg(['-i', $bruto, '-frames:v', '1', '-vf', "scale='min(512,iw)':'min(512,ih)':force_original_aspect_ratio=decrease", $png], dirname($png), null, null, 60);
        } catch (\Throwable) {
            return null;
        } finally {
            @unlink($bruto);
        }

        // Favicon genérico (o globo do Google) ou ícone minúsculo não serve.
        $info = @getimagesize($png);
        if (! $info || $info[0] < 96) {
            @unlink($png);

            return null;
        }

        return $png;
    }

    private static function palavras(string $texto): array
    {
        $s = Str::lower(Str::ascii($texto));
        $s = preg_replace('/[^a-z0-9]+/', ' ', $s) ?? $s;

        return array_values(array_filter(explode(' ', $s), fn ($p) => strlen($p) > 1 && ! in_array($p, ['de', 'da', 'do', 'the', 'app', 'e', 'and', 'pro', 'cc', 'plus'], true)));
    }
}
