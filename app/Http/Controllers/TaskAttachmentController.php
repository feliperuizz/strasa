<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskAttachment;
use App\Services\AttachmentStreamer;
use App\Services\PreviaDeImagem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $enviados = [];

        // O navegador não manda os arquivos na ordem do nome — no Windows vem
        // primeiro o que estava selecionado por último. Ordenar pelo nome de
        // forma natural ("2" antes de "10") acerta o carrossel na maioria dos
        // casos (arte-1, arte-2...); o resto a equipe ajusta arrastando.
        $arquivos = collect($request->file('files'))
            ->sortBy(fn ($f) => $f->getClientOriginalName(), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        // Entram no fim da pasta (ou dos soltos) onde foram enviados.
        $proxima = (int) $task->attachments()
            ->when($folderId, fn ($q) => $q->where('folder_id', $folderId), fn ($q) => $q->whereNull('folder_id'))
            ->max('position') + 1;

        foreach ($arquivos as $file) {
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

            $anexo = $task->attachments()->create([
                'company_id' => $task->company_id,
                'folder_id' => $folderId,
                'uploaded_by' => $request->user()->id,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $mime ?: 'application/octet-stream',
                'size' => $file->getSize(),
                'is_image' => Str::startsWith($mime, 'image/'),
                'position' => $proxima++,
            ]);

            // Prévias já no envio, a partir do arquivo que ainda está no
            // temporário do PHP — sem baixar de volta do Drive. Falhar aqui
            // nunca impede o envio: o painel cai no original.
            if ($anexo->is_image) {
                app(PreviaDeImagem::class)->gerarTodas($anexo, $file->getRealPath());
            }

            $enviados[] = $file->getClientOriginalName();
        }

        // Um envio de vários arquivos vira uma linha só no histórico, com os
        // nomes no meta — é o "incluiu algo no card" que ninguém via.
        if ($enviados) {
            TaskActivity::registrar($task, TaskActivity::TYPE_ATTACHMENT_ADDED,
                count($enviados) === 1
                    ? 'anexou "'.Str::limit($enviados[0], 60).'"'
                    : 'anexou '.count($enviados).' arquivos',
                ['arquivos' => $enviados]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'Anexo(s) enviado(s) com sucesso.']);
        }

        return back()->with('status', 'Anexo(s) enviado(s) para o bucket.');
    }

    /**
     * Nova ordem de uma pasta (ou dos arquivos soltos), vinda do arraste no
     * card. É a ordem do carrossel no card e no painel do cliente.
     */
    public function reorder(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $dados = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        $anexos = $task->attachments()->whereIn('id', $dados['ids'])->get()->keyBy('id');

        // Todos precisam ser deste card e da mesma pasta: o arraste é dentro
        // de um bloco só, então qualquer outra coisa é pedido adulterado.
        abort_if($anexos->count() !== count($dados['ids']), 422, 'Algum arquivo não pertence a este card.');
        abort_if($anexos->pluck('folder_id')->unique()->count() > 1, 422, 'A ordem só pode ser alterada dentro de uma mesma pasta.');

        $mudou = false;

        DB::transaction(function () use ($dados, $anexos, &$mudou) {
            foreach ($dados['ids'] as $posicao => $id) {
                if ($anexos[$id]->position !== $posicao) {
                    $anexos[$id]->forceFill(['position' => $posicao])->save();
                    $mudou = true;
                }
            }
        });

        // Vários arrastes seguidos viram uma linha só no histórico.
        if ($mudou) {
            TaskActivity::registrarAgrupado($task, TaskActivity::TYPE_ATTACHMENTS_REORDERED,
                'reorganizou a ordem do carrossel');
        }

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, TaskAttachment $attachment)
    {
        abort_unless($attachment->company_id === $request->user()->company_id, 403);
        $this->authorize('update', $attachment->task);

        // Remove o arquivo no bucket e depois o registro no banco.
        $nome = $attachment->original_name;
        $task = $attachment->task;

        Storage::disk($attachment->disk)->delete($attachment->path);
        app(PreviaDeImagem::class)->apagar($attachment);
        $attachment->delete();

        if ($task) {
            TaskActivity::registrar($task, TaskActivity::TYPE_ATTACHMENT_REMOVED,
                'removeu o anexo "'.Str::limit((string) $nome, 60).'"');
        }

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
