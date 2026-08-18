<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskAttachment;
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
    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $request->validate([
            'files' => ['required', 'array'],
            'files.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,mp4'],
            'folder_id' => ['nullable', 'integer', 'exists:task_folders,id'],
        ]);

        $disk = config('filesystems.attachments_disk');
        $folder = "company-{$task->company_id}/tasks/{$task->id}";
        $folderId = $request->input('folder_id');

        foreach ($request->file('files') as $file) {
            // Nome único no bucket, preservando a extensão original.
            $name = Str::uuid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs($folder, $name, $disk);

            $task->attachments()->create([
                'company_id' => $task->company_id,
                'folder_id' => $folderId,
                'uploaded_by' => $request->user()->id,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'is_image' => Str::startsWith((string) $file->getMimeType(), 'image/'),
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
}
