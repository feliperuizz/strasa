<?php

namespace App\Services;

use App\Models\TaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Entrega um anexo ao navegador, com suporte a Range.
 *
 * Extraído do TaskAttachmentController para que o painel do cliente sirva as
 * mesmas mídias sem duplicar a parte delicada (Range, ETag, decisão de abrir
 * inline ou forçar download). Quem chama é responsável pela autorização — o
 * serviço assume que a permissão já foi verificada.
 */
class AttachmentStreamer
{
    public function stream(Request $request, TaskAttachment $attachment): Response
    {
        $etag = '"att-'.$attachment->id.'-'.optional($attachment->updated_at)->timestamp.'"';

        $headers = [
            'Cache-Control' => 'private, max-age=2592000', // 30 dias no navegador
            'ETag' => $etag,
        ];

        // Revalidação barata: nem toca no Google Drive/S3.
        if (trim((string) $request->header('If-None-Match')) === $etag) {
            return response('', 304, $headers);
        }

        $headers += $this->headersDeExibicao($attachment);

        // Accept-Ranges avisa o navegador que ele pode pedir pedaços do arquivo.
        // É isso que permite dar play e arrastar a linha do tempo de um vídeo
        // sem baixar tudo antes (e sem isso o Safari simplesmente não toca).
        $tamanho = (int) $attachment->size;
        $headers['Accept-Ranges'] = 'bytes';

        $faixa = $tamanho > 0 ? $this->faixaPedida($request, $tamanho) : null;

        if ($faixa === false) {
            return response('', 416, $headers + ['Content-Range' => "bytes */{$tamanho}"]);
        }

        $inicio = $faixa['inicio'] ?? 0;
        $fim = $faixa['fim'] ?? ($tamanho > 0 ? $tamanho - 1 : null);

        $stream = $this->abrirStream($attachment, $inicio);

        if ($faixa !== null) {
            $headers['Content-Range'] = "bytes {$inicio}-{$fim}/{$tamanho}";
            $headers['Content-Length'] = (string) ($fim - $inicio + 1);
            $status = 206;
        } else {
            if ($tamanho > 0) {
                $headers['Content-Length'] = (string) $tamanho;
            }
            $status = 200;
        }

        $bytesAEnviar = $fim === null ? null : $fim - $inicio + 1;

        // Devolve a conexão do banco ANTES de começar a transmitir.
        //
        // Um vídeo de 200 MB mantém este processo PHP ocupado por minutos. Sem
        // isto, a conexão MySQL fica presa junto — e em hospedagem
        // compartilhada, onde o limite de conexões simultâneas por usuário é
        // baixo, alguns downloads em paralelo esgotam o limite e derrubam o
        // login de todo mundo (a sessão também vive no banco). Nada mais será
        // consultado daqui pra frente, então a conexão não faz falta.
        DB::disconnect();

        return response()->stream(function () use ($stream, $bytesAEnviar) {
            if ($bytesAEnviar === null) {
                fpassthru($stream);
            } else {
                $restante = $bytesAEnviar;
                while ($restante > 0 && ! feof($stream)) {
                    $bloco = fread($stream, (int) min(262144, $restante));
                    if ($bloco === false || $bloco === '') {
                        break;
                    }
                    echo $bloco;
                    $restante -= strlen($bloco);
                }
            }

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $status, $headers);
    }

    /**
     * Interpreta o header Range.
     *
     * @return array{inicio:int, fim:int}|null|false  null = sem Range (arquivo
     *         inteiro); false = faixa impossível (responder 416).
     */
    public function faixaPedida(Request $request, int $tamanho): array|null|false
    {
        $header = trim((string) $request->header('Range'));

        if ($header === '' || ! preg_match('/^bytes=(\d*)-(\d*)$/', $header, $m)) {
            return null;
        }

        $temInicio = $m[1] !== '';
        $temFim = $m[2] !== '';

        if (! $temInicio && ! $temFim) {
            return null;
        }

        if (! $temInicio) {
            // Forma "bytes=-500": os últimos 500 bytes.
            $inicio = max(0, $tamanho - (int) $m[2]);
            $fim = $tamanho - 1;
        } else {
            $inicio = (int) $m[1];
            $fim = $temFim ? (int) $m[2] : $tamanho - 1;
        }

        $fim = min($fim, $tamanho - 1);

        if ($inicio > $fim || $inicio >= $tamanho) {
            return false;
        }

        return ['inicio' => $inicio, 'fim' => $fim];
    }

    /**
     * Abre o arquivo já posicionado no byte pedido.
     *
     * Discos remotos (Google Drive) devolvem um stream que não aceita fseek;
     * nesse caso não há alternativa senão ler e descartar o começo. Por isso
     * dar play (offset 0) é barato e arrastar para o meio de um arquivo grande
     * não é — em disco S3/R2 com URL pública o vídeo nem passa por aqui.
     *
     * @return resource
     */
    public function abrirStream(TaskAttachment $attachment, int $offset)
    {
        $stream = Storage::disk($attachment->disk)->readStream($attachment->path);
        abort_if($stream === false || $stream === null, 404);

        if ($offset <= 0) {
            return $stream;
        }

        $meta = stream_get_meta_data($stream);

        if (! empty($meta['seekable']) && fseek($stream, $offset) === 0) {
            return $stream;
        }

        $restante = $offset;
        while ($restante > 0 && ! feof($stream)) {
            $bloco = fread($stream, (int) min(1048576, $restante));
            if ($bloco === false || $bloco === '') {
                break;
            }
            $restante -= strlen($bloco);
        }

        return $stream;
    }

    /**
     * Como o arquivo deve ser entregue ao navegador.
     *
     * Aceitamos qualquer tipo de arquivo no upload, e esta rota serve pelo
     * domínio do próprio app. Abrir um .html ou .svg enviado por alguém como
     * "inline" faria o navegador executar o script dele dentro da sessão do
     * usuário. Por isso só abre inline o que é seguro exibir (imagem, vídeo,
     * áudio, PDF); o resto vira download com tipo genérico.
     *
     * @return array<string, string>
     */
    public function headersDeExibicao(TaskAttachment $attachment): array
    {
        $mime = (string) ($attachment->mime_type ?: 'application/octet-stream');

        $inline = Str::startsWith($mime, ['video/', 'audio/'])
            || $mime === 'application/pdf'
            || (Str::startsWith($mime, 'image/') && $mime !== 'image/svg+xml');

        if ($inline) {
            return [
                'Content-Type' => $mime,
                'X-Content-Type-Options' => 'nosniff',
            ];
        }

        // O SVG mantém o tipo real, senão a miniatura <img> do card quebra —
        // o Content-Disposition não atrapalha imagem carregada em <img>, só
        // impede que o arquivo abra como página se alguém acessar a URL direto.
        return [
            'Content-Type' => $mime === 'image/svg+xml' ? $mime : 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'attachment; filename="'
                .str_replace('"', '', $attachment->original_name).'"',
        ];
    }
}
