<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Upload e exclusão de anexos das tarefas. Os arquivos vão para o disco
 * S3-compatible (R2/B2) definido em filesystems.attachments_disk; no banco
 * só guardamos os metadados.
 */
class TaskAttachmentController extends Controller
{
    public function store(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $task);

        // Sem restrição de tipo nem de tamanho: qualquer arquivo é aceito.
        // O teto real passa a ser o do PHP/servidor (veja public/.user.ini).
        $request->validate([
            'files' => ['required', 'array'],
            'files.*' => ['file'],
            'folder_id' => ['nullable', 'integer', 'exists:task_folders,id'],
        ]);

        $disk = config('filesystems.attachments_disk');
        $folder = "company-{$task->company_id}/tasks/{$task->id}";
        $folderId = $request->input('folder_id');

        foreach ($request->file('files') as $file) {
            // Nome único no bucket, preservando a extensão original.
            $extension = $file->getClientOriginalExtension();
            $name = Str::uuid().($extension !== '' ? '.'.$extension : '');

            // putFileAs em vez de storeAs: é o mesmo caminho, mas deixa
            // explícito que o envio é em streaming — um vídeo de 1GB não é
            // carregado inteiro na memória do PHP.
            $path = Storage::disk($disk)->putFileAs($folder, $file, $name);

            // O bucket/Drive pode recusar o arquivo (cota, timeout). Sem isto o
            // anexo era gravado no banco com caminho vazio e "sumia" depois.
            abort_if($path === false, 500, "Não foi possível salvar \"{$file->getClientOriginalName()}\" no armazenamento. Tente novamente.");

            $mime = $this->detectarMime($file, $extension);

            $task->attachments()->create([
                'company_id' => $task->company_id,
                'folder_id' => $folderId,
                'uploaded_by' => $request->user()->id,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $mime ?: 'application/octet-stream',
                'size' => $file->getSize(),
                'is_image' => Str::startsWith($mime, 'image/'),
            ]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'Anexo(s) enviado(s) com sucesso.']);
        }

        return back()->with('status', 'Anexo(s) enviado(s) para o bucket.');
    }

    public function destroy(Request $request, TaskAttachment $attachment)
    {
        abort_unless($attachment->company_id === $request->user()->company_id, 403);
        $this->authorize('update', $attachment->task);

        // Remove o arquivo no bucket e depois o registro no banco.
        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'Anexo removido com sucesso.']);
        }

        return back()->with('status', 'Anexo removido do bucket.');
    }

    public function download(Request $request, TaskAttachment $attachment)
    {
        abort_unless($attachment->company_id === $request->user()->company_id, 403);
        $this->authorize('view', $attachment->task);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    /**
     * Serve o arquivo através do app (usado quando o disco não tem URL pública,
     * ex.: Google Drive). Sem cache, cada <img> da tela obrigava o PHP a baixar
     * o arquivo do provedor de novo — daí a lentidão em telas com muitos anexos.
     *
     * O conteúdo de um anexo nunca muda (o caminho no bucket é um UUID novo a
     * cada upload), então podemos mandar o navegador guardar por bastante tempo
     * e responder 304 quando ele revalidar.
     */
    public function show(Request $request, TaskAttachment $attachment)
    {
        abort_unless($attachment->company_id === $request->user()->company_id, 403);
        $this->authorize('view', $attachment->task);

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
     * Tipo do arquivo, usado depois como Content-Type ao servir.
     *
     * getMimeType() olha o conteúdo (confiável); getClientMimeType() vem do
     * navegador e é forjável, por isso não é usado. Só que o fileinfo devolve
     * "application/octet-stream" para vários containers de vídeo — e aí o
     * player não abriria. Nesses casos vale o palpite pela extensão.
     *
     * Isso não reabre a porta do XSS: se o conteúdo fosse HTML/SVG o fileinfo
     * teria detectado, e quem decide o que abre inline é a lista em
     * headersDeExibicao(), onde esses tipos não entram.
     */
    private function detectarMime(\Illuminate\Http\UploadedFile $file, string $extension): string
    {
        $mime = (string) $file->getMimeType();

        // Se o conteúdo é de um formato que o navegador executaria, essa
        // detecção manda — nada de "promover" para vídeo por causa do nome.
        $executaveis = [
            'text/html', 'application/xhtml+xml', 'image/svg+xml',
            'application/xml', 'text/xml', 'application/javascript', 'text/javascript',
        ];

        if (in_array($mime, $executaveis, true)) {
            return $mime;
        }

        // Já é um tipo que sabemos exibir: mantém.
        if ($mime === 'application/pdf' || Str::startsWith($mime, ['video/', 'audio/', 'image/'])) {
            return $mime;
        }

        // Sobrou o caso comum com vídeo: o fileinfo devolve algo genérico ou
        // ambíguo ("application/octet-stream", "application/mp4") e o player
        // não abriria. Aí a extensão decide.
        if ($extension !== '') {
            $candidatos = (new \Symfony\Component\Mime\MimeTypes())->getMimeTypes(strtolower($extension));

            foreach ($candidatos as $candidato) {
                if (Str::startsWith($candidato, ['video/', 'audio/', 'image/'])) {
                    return $candidato;
                }
            }
        }

        return $mime ?: 'application/octet-stream';
    }

    /**
     * Interpreta o header Range.
     *
     * @return array{inicio:int, fim:int}|null|false  null = sem Range (arquivo
     *         inteiro); false = faixa impossível (responder 416).
     */
    private function faixaPedida(Request $request, int $tamanho): array|null|false
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
    private function abrirStream(TaskAttachment $attachment, int $offset)
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
    private function headersDeExibicao(TaskAttachment $attachment): array
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
