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

            // getMimeType() adivinha pelo conteúdo; getClientMimeType() vem do
            // navegador e é forjável. Como esse valor vira o Content-Type na
            // hora de servir o arquivo, guardamos o detectado.
            $mime = (string) $file->getMimeType();

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

        // Stream manual em vez de Storage::response(): aquele helper chama
        // mimeType() e size() no disco, o que custa duas chamadas extras à API
        // do Drive por imagem. Esses dados já estão no banco.
        $stream = Storage::disk($attachment->disk)->readStream($attachment->path);
        abort_if($stream === false || $stream === null, 404);

        $headers += $this->headersDeExibicao($attachment);

        if ($attachment->size > 0) {
            $headers['Content-Length'] = (string) $attachment->size;
        }

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, $headers);
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
