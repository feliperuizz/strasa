<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Flags dos cards, manipuladas direto do quadro.
 *
 * Todas as ações respondem JSON: o painel de flags atualiza o card na hora,
 * sem recarregar o quadro.
 */
class TagController extends Controller
{
    /** Cria uma flag nova na empresa. */
    public function store(Request $request): JsonResponse
    {
        $empresa = $request->user()->company_id;

        $dados = $request->validate([
            'name' => [
                'required', 'string', 'max:40',
                // O nome é único por empresa; sem isto o insert estouraria a
                // constraint e o usuário veria um erro 500 sem explicação.
                Rule::unique('tags', 'name')->where('company_id', $empresa),
            ],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], [
            'name.unique' => 'Já existe uma flag com esse nome.',
        ]);

        $tag = Tag::create([
            'company_id' => $empresa,
            'name' => $dados['name'],
            'color' => $dados['color'],
        ]);

        return response()->json([
            'ok' => true,
            'tag' => ['id' => $tag->id, 'name' => $tag->name, 'color' => $tag->color],
        ]);
    }

    /** Muda o nome ou a cor de uma flag existente. */
    public function update(Request $request, Tag $tag): JsonResponse
    {
        abort_unless($tag->company_id === $request->user()->company_id, 403);

        $dados = $request->validate([
            'name' => [
                'sometimes', 'required', 'string', 'max:40',
                Rule::unique('tags', 'name')
                    ->where('company_id', $tag->company_id)
                    ->ignore($tag->id),
            ],
            'color' => ['sometimes', 'required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], [
            'name.unique' => 'Já existe uma flag com esse nome.',
        ]);

        $tag->update($dados);

        return response()->json([
            'ok' => true,
            'tag' => ['id' => $tag->id, 'name' => $tag->name, 'color' => $tag->color],
        ]);
    }

    /**
     * Exclui a flag da empresa inteira.
     *
     * Some de todos os cards que a usavam, então o painel confirma antes e
     * informamos em quantos ela está aplicada.
     */
    public function destroy(Request $request, Tag $tag): JsonResponse
    {
        abort_unless($tag->company_id === $request->user()->company_id, 403);

        $tag->tasks()->detach();
        $tag->delete();

        return response()->json(['ok' => true]);
    }

    /** Aplica ou tira a flag de um card. */
    public function toggle(Request $request, Task $task, Tag $tag): JsonResponse
    {
        $this->authorize('update', $task);

        abort_unless($tag->company_id === $task->company_id, 403);

        $resultado = $task->tags()->toggle($tag->id);

        return response()->json([
            'ok' => true,
            'aplicada' => ! empty($resultado['attached']),
        ]);
    }
}
