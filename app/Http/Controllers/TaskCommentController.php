<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaskCommentController extends Controller
{
    public function store(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        $this->authorize('view', $task);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $task->comments()->create([
            'company_id' => $task->company_id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
            'mentions' => $this->parseMentions($data['body'], $task->company_id),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'Comentário adicionado']);
        }

        return back()->with('status', 'Comentário adicionado.');
    }

    public function update(Request $request, TaskComment $comment)
    {
        abort_unless($comment->company_id === $request->user()->company_id, 403);
        abort_unless($comment->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $comment->update([
            'body' => $data['body'],
            'mentions' => $this->parseMentions($data['body'], $comment->company_id),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'Comentário atualizado']);
        }

        return back()->with('status', 'Comentário atualizado.');
    }

    public function destroy(Request $request, TaskComment $comment): RedirectResponse
    {
        abort_unless($comment->company_id === $request->user()->company_id, 403);
        abort_unless(
            $comment->user_id === $request->user()->id || $request->user()->isManager(),
            403
        );

        $comment->delete();

        return back()->with('status', 'Comentário removido.');
    }

    /**
     * Extrai @menções e resolve para IDs de usuários da empresa
     * (casa pelo primeiro nome). Usado depois para notificações.
     */
    private function parseMentions(string $body, int $companyId): array
    {
        preg_match_all('/@([\p{L}0-9._-]+)/u', $body, $matches);

        if (empty($matches[1])) {
            return [];
        }

        $tokens = array_map(fn ($t) => Str::lower($t), $matches[1]);

        return User::where('company_id', $companyId)
            ->get(['id', 'name'])
            ->filter(fn ($u) => in_array(Str::lower(Str::before($u->name, ' ')), $tokens, true))
            ->pluck('id')
            ->values()
            ->all();
    }
}
