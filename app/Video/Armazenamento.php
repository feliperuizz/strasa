<?php

namespace App\Video;

use App\Models\VideoJob;
use Illuminate\Support\Facades\File;

/**
 * Pastas de uma edição no disco local do estúdio.
 *
 *   <raiz>/company-7/job-42/
 *       brutos/    o que a equipe enviou (nunca alterado)
 *       trabalho/  proxies, áudio, transcrição, folhas de quadros, cenas
 *       claude/    pasta onde o Claude trabalha (contexto.md, plano.json)
 *       renders/   prévias e finais versionados (_v01, _v02…), nunca sobrescritos
 *   <raiz>/biblioteca/company-7/   músicas, efeitos, logos e fontes de vídeo
 *
 * Inspirado na regra do pacote de skills: o que a pessoa pôs não se toca;
 * tudo o que é gerado tem pasta própria e versão.
 */
class Armazenamento
{
    public const SUBPASTAS = ['brutos', 'trabalho', 'claude', 'renders'];

    public function __construct(private VideoJob $job) {}

    public static function raiz(): string
    {
        return rtrim(str_replace('\\', '/', (string) config('video.armazenamento')), '/');
    }

    public static function biblioteca(string $relativo = ''): string
    {
        return self::raiz().'/biblioteca'.($relativo !== '' ? '/'.ltrim($relativo, '/') : '');
    }

    public function base(): string
    {
        return self::raiz()."/company-{$this->job->company_id}/job-{$this->job->id}";
    }

    public function dir(string $sub): string
    {
        $dir = $this->base().'/'.$sub;
        File::ensureDirectoryExists($dir, 0775);

        return $dir;
    }

    public function absoluto(string $relativo): string
    {
        return $this->base().'/'.ltrim($relativo, '/');
    }

    public function garantir(): void
    {
        foreach (self::SUBPASTAS as $sub) {
            $this->dir($sub);
        }
    }

    /** Próximo nome livre: renders/previa_v03.mp4. Nunca sobrescreve. */
    public function proximaVersao(string $prefixo, string $extensao, int $atual): array
    {
        $versao = max(1, $atual + 1);

        while (file_exists($caminho = $this->dir('renders').sprintf('/%s_v%02d.%s', $prefixo, $versao, $extensao))) {
            $versao++;
        }

        return [$versao, $caminho];
    }

    public function relativo(string $absoluto): string
    {
        $base = $this->base().'/';
        $absoluto = str_replace('\\', '/', $absoluto);

        return str_starts_with($absoluto, $base) ? substr($absoluto, strlen($base)) : $absoluto;
    }

    /** Bytes ocupados pela edição. */
    public function tamanho(): int
    {
        if (! is_dir($this->base())) {
            return 0;
        }

        $total = 0;
        foreach (File::allFiles($this->base()) as $arquivo) {
            $total += $arquivo->getSize();
        }

        return $total;
    }

    /** Retenção: apaga brutos e trabalho, mantém renders e o plano. */
    public function limparPesados(): void
    {
        foreach (['brutos', 'trabalho', 'claude'] as $sub) {
            File::deleteDirectory($this->base().'/'.$sub);
        }
    }

    public function apagarTudo(): void
    {
        File::deleteDirectory($this->base());
    }

    public static function espacoLivreGb(): ?float
    {
        $raiz = self::raiz();
        File::ensureDirectoryExists($raiz, 0775);
        $livre = @disk_free_space($raiz);

        return $livre === false ? null : round($livre / 1024 ** 3, 1);
    }
}
