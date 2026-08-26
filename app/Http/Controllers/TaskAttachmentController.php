<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Services\AttachmentStreamer;
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

        return app(AttachmentStreamer::class)->stream($request, $attachment);
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
}
