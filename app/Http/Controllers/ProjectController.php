<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function create(Client $client)
    {
        $this->authorize('create', Project::class);

        return view('projects.create', compact('client'));
    }

    public function store(ProjectRequest $request, Client $client): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $project = DB::transaction(function () use ($request, $client) {
            $project = $client->projects()->create([
                'company_id' => $client->company_id,
                'name' => $request->validated('name'),
                'description' => $request->validated('description'),
            ]);

            // Cria as colunas padrão a partir do template do cliente.
            $project->createDefaultColumns();

            return $project;
        });

        return redirect()->route('projects.board', $project)
            ->with('status', 'Projeto criado com as colunas padrão.');
    }

    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        return view('projects.edit', compact('project'));
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        return redirect()->route('projects.board', $project)->with('status', 'Projeto atualizado.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $client = $project->client;
        $project->delete();

        return redirect()->route('clients.show', $client)->with('status', 'Projeto removido.');
    }

    public function toggleFavorite(\Illuminate\Http\Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $user = $request->user();
        if ($project->favoritedBy()->where('user_id', $user->id)->exists()) {
            $project->favoritedBy()->detach($user->id);
            $isFavorite = false;
        } else {
            $project->favoritedBy()->attach($user->id);
            $isFavorite = true;
        }

        return response()->json(['is_favorite' => $isFavorite]);
    }

    public function updateStatus(\Illuminate\Http\Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'status' => ['nullable', 'string', \Illuminate\Validation\Rule::in(['on_track', 'at_risk', 'off_track'])]
        ]);

        $project->update(['status' => $validated['status']]);

        return response()->json(['status' => $project->status]);
    }
}
