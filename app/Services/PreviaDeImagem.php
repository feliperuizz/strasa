<?php

namespace App\Services;

use App\Models\TaskAttachment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Prévias leves das imagens anexadas, guardadas no disco do próprio servidor.
 *
 * O painel do cliente mostrava o arquivo original, em resolução cheia, buscado
 * no Google Drive pelo servidor a cada acesso — inclusive nas miniaturas.
 * Cada imagem levava segundos. A prévia é gerada uma vez (no envio, ou na
 * primeira vez que alguém pede) e depois sai direto do disco local.
 *
 * Tudo aqui é "melhor esforço": se a prévia não puder ser gerada (formato que
 * o GD não lê, imagem grande demais para a memória, GIF animado), quem chama
 * cai no arquivo original e nada quebra.
 */
class PreviaDeImagem
{
    /**
     * Maior lado, em pixels. "tela" sobra para o feed (1080px) em tela
     * retina; "mini" cobre a capa da lista do cliente (~400px de largura,
     * 800px físicos em retina, com o recorte do object-fit).
     */
    public const TAMANHOS = [
        'tela' => 1600,
        'mini' => 640,
    ];

    private const DISCO = 'local';

    /** O que o GD reduz. GIF perderia a animação; HEIC/SVG ele não lê. */
    private const TIPOS = ['image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/webp'];

    /** Depois de uma falha, não tenta de novo por este tempo (evita rebaixar do Drive toda hora). */
    private const ESPERA_APOS_FALHA = 7 * 24 * 3600;

    public function extensao(): string
    {
        return function_exists('imagewebp') ? 'webp' : 'jpg';
    }

    public function caminho(TaskAttachment $anexo, string $tamanho): string
    {
        return "previas/company-{$anexo->company_id}/{$anexo->id}-{$tamanho}.".$this->extensao();
    }

    /** Caminho absoluto da prévia pronta, ou null se não existe. */
    public function existente(TaskAttachment $anexo, string $tamanho): ?string
    {
        $disco = Storage::disk(self::DISCO);
        $caminho = $this->caminho($anexo, $tamanho);

        return $disco->exists($caminho) ? $disco->path($caminho) : null;
    }

    /**
     * Devolve o caminho absoluto da prévia, gerando se preciso. Null quando
     * não dá para gerar — aí quem chama serve o original.
     *
     * $arquivoLocal evita baixar de novo do Drive/bucket quando o arquivo
     * acabou de ser enviado e ainda está no temporário do PHP.
     */
    public function garantir(TaskAttachment $anexo, string $tamanho, ?string $arquivoLocal = null): ?string
    {
        if (! isset(self::TAMANHOS[$tamanho]) || ! $anexo->is_image) {
            return null;
        }

        if ($pronta = $this->existente($anexo, $tamanho)) {
            return $pronta;
        }

        $gerados = $this->gerarTodas($anexo, $arquivoLocal, [$tamanho]);

        return $gerados[$tamanho] ?? null;
    }

    /**
     * Gera as prévias pedidas (todas, por padrão) a partir de uma única
     * leitura do original.
     *
     * @return array<string, string>  tamanho => caminho absoluto
     */
    public function gerarTodas(TaskAttachment $anexo, ?string $arquivoLocal = null, ?array $tamanhos = null): array
    {
        if (! $this->suportada($anexo) || $this->falhouRecentemente($anexo)) {
            return [];
        }

        $tamanhos ??= array_keys(self::TAMANHOS);
        $temporario = null;
        $prontas = [];

        try {
            $origem = $arquivoLocal && is_file($arquivoLocal)
                ? $arquivoLocal
                : ($temporario = $this->baixarOriginal($anexo));

            $imagem = $origem ? $this->abrir($origem) : null;
            if (! $imagem) {
                return [];
            }

            foreach ($tamanhos as $tamanho) {
                $destino = Storage::disk(self::DISCO)->path($this->caminho($anexo, $tamanho));

                if ($this->salvarReduzida($imagem, self::TAMANHOS[$tamanho], $destino)) {
                    $prontas[$tamanho] = $destino;
                }
            }

            imagedestroy($imagem);

            return $prontas;
        } catch (\Throwable $e) {
            Log::warning('Prévia de imagem não gerada', ['attachment_id' => $anexo->id, 'erro' => $e->getMessage()]);
            $prontas = [];

            return [];
        } finally {
            if ($temporario && is_file($temporario)) {
                @unlink($temporario);
            }

            $disco = Storage::disk(self::DISCO);
            $prontas
                ? $disco->delete($this->marcadorDeFalha($anexo))
                : $disco->put($this->marcadorDeFalha($anexo), (string) time());
        }
    }

    /** Tipo que dá para reduzir. Os outros vão direto no original, sem baixar à toa. */
    public function suportada(TaskAttachment $anexo): bool
    {
        return $anexo->is_image && in_array(strtolower((string) $anexo->mime_type), self::TIPOS, true);
    }

    public function falhouRecentemente(TaskAttachment $anexo): bool
    {
        $disco = Storage::disk(self::DISCO);
        $marcador = $this->marcadorDeFalha($anexo);

        return $disco->exists($marcador)
            && (time() - (int) $disco->get($marcador)) < self::ESPERA_APOS_FALHA;
    }

    private function marcadorDeFalha(TaskAttachment $anexo): string
    {
        return "previas/company-{$anexo->company_id}/{$anexo->id}.falhou";
    }

    public function apagar(TaskAttachment $anexo): void
    {
        Storage::disk(self::DISCO)->delete($this->marcadorDeFalha($anexo));

        foreach (array_keys(self::TAMANHOS) as $tamanho) {
            // As duas extensões: o servidor pode ter ganhado/perdido WebP.
            foreach (['webp', 'jpg'] as $ext) {
                Storage::disk(self::DISCO)->delete("previas/company-{$anexo->company_id}/{$anexo->id}-{$tamanho}.{$ext}");
            }
        }
    }

    /* --------------------------------------------------------------------- */

    /** Copia o original do Drive/bucket para um temporário local. */
    private function baixarOriginal(TaskAttachment $anexo): ?string
    {
        $entrada = Storage::disk($anexo->disk)->readStream($anexo->path);
        if (! $entrada) {
            return null;
        }

        $caminho = tempnam(sys_get_temp_dir(), 'previa');
        $saida = fopen($caminho, 'wb');
        stream_copy_to_stream($entrada, $saida);
        fclose($saida);

        if (is_resource($entrada)) {
            fclose($entrada);
        }

        return $caminho;
    }

    /** Abre com o GD já na orientação certa. Null para o que não vale reduzir. */
    private function abrir(string $arquivo)
    {
        $info = @getimagesize($arquivo);
        if (! $info) {
            return null; // HEIC, PSD e afins: o GD não lê
        }

        [$largura, $altura, $tipo] = $info;

        // GIF perderia a animação; SVG não é bitmap.
        if (! in_array($tipo, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return null;
        }

        if (! $this->cabeNaMemoria($largura, $altura)) {
            return null;
        }

        $imagem = match ($tipo) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($arquivo),
            IMAGETYPE_PNG => @imagecreatefrompng($arquivo),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($arquivo) : false,
        };

        if (! $imagem) {
            return null;
        }

        // Foto de celular vem "deitada" com a rotação só no EXIF; o GD ignora.
        if ($tipo === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($arquivo);
            $angulo = match ((int) ($exif['Orientation'] ?? 1)) {
                3 => 180,
                6 => -90,
                8 => 90,
                default => 0,
            };
            if ($angulo) {
                $girada = imagerotate($imagem, $angulo, 0);
                if ($girada) {
                    imagedestroy($imagem);
                    $imagem = $girada;
                }
            }
        }

        return $imagem;
    }

    /** Reduz mantendo a proporção (nunca amplia) e grava de forma atômica. */
    private function salvarReduzida($imagem, int $maiorLado, string $destino): bool
    {
        $largura = imagesx($imagem);
        $altura = imagesy($imagem);
        $escala = min(1, $maiorLado / max($largura, $altura));
        $novaLargura = max(1, (int) round($largura * $escala));
        $novaAltura = max(1, (int) round($altura * $escala));

        $reduzida = imagecreatetruecolor($novaLargura, $novaAltura);
        $webp = function_exists('imagewebp');

        if ($webp) {
            // WebP guarda transparência (logo em PNG, por exemplo).
            imagealphablending($reduzida, false);
            imagesavealpha($reduzida, true);
            imagefill($reduzida, 0, 0, imagecolorallocatealpha($reduzida, 0, 0, 0, 127));
        } else {
            // JPEG não tem transparência: fundo branco em vez de preto.
            imagefill($reduzida, 0, 0, imagecolorallocate($reduzida, 255, 255, 255));
        }

        imagecopyresampled($reduzida, $imagem, 0, 0, 0, 0, $novaLargura, $novaAltura, $largura, $altura);

        if (! is_dir(dirname($destino))) {
            @mkdir(dirname($destino), 0775, true);
        }

        // Grava num temporário e renomeia: dois pedidos simultâneos nunca
        // entregam um arquivo pela metade.
        $temporario = $destino.'.'.uniqid('', true).'.tmp';
        $ok = $webp ? imagewebp($reduzida, $temporario, 80) : imagejpeg($reduzida, $temporario, 82);
        imagedestroy($reduzida);

        if (! $ok || ! is_file($temporario)) {
            @unlink($temporario);

            return false;
        }

        return @rename($temporario, $destino) || is_file($destino);
    }

    /**
     * O GD descompacta a imagem inteira na memória (~5 bytes por pixel com a
     * cópia reduzida). Um PNG 8000x8000 passaria de 300 MB e derrubaria o
     * processo; melhor servir o original.
     */
    private function cabeNaMemoria(int $largura, int $altura): bool
    {
        $limite = $this->bytes((string) ini_get('memory_limit'));
        if ($limite <= 0) {
            return true;
        }

        return ($largura * $altura * 5) + memory_get_usage(true) < $limite * 0.85;
    }

    private function bytes(string $valor): int
    {
        $valor = trim($valor);
        if ($valor === '' || $valor === '-1') {
            return -1;
        }

        $numero = (int) $valor;

        return match (strtolower(substr($valor, -1))) {
            'g' => $numero * 1024 ** 3,
            'm' => $numero * 1024 ** 2,
            'k' => $numero * 1024,
            default => $numero,
        };
    }
}
