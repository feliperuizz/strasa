<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = Client::query()
            ->withCount(['projects' => fn ($q) => $q->whereNull('archived_at')])
            ->orderBy('archived_at')
            ->orderBy('name')
            ->get();

        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        $this->authorize('create', Client::class);

        return view('clients.create');
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        $this->authorize('create', Client::class);

        $client = new Client($request->validated());
        $client->slug = $this->uniqueSlug($request->input('name'), $request->user()->company_id);
        $client->default_columns = Client::DEFAULT_COLUMNS;
        $this->handleLogo($request, $client);
        $client->save();

        return redirect()->route('clients.show', $client)
            ->with('status', 'Cliente criado com sucesso.');
    }

    public function show(Client $client)
    {
        $this->authorize('view', $client);

        $client->load([
            'projects' => fn ($q) => $q->orderBy('name')->withCount('tasks'),
            'portal',
        ]);

        // Usados no bloco do painel de aprovação: quem pode receber o push.
        $equipe = \App\Models\User::where('company_id', $client->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('clients.show', compact('client', 'equipe'));
    }

    public function edit(Client $client)
    {
        $this->authorize('update', $client);

        return view('clients.edit', [
            'client' => $client,
        ]);
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->fill($request->validated());
        $this->handleLogo($request, $client);
        $client->save();

        return redirect()->route('clients.show', $client)
            ->with('status', 'Cliente atualizado.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        $client->delete();

        return redirect()->route('clients.index')->with('status', 'Cliente removido.');
    }

    /** Arquivar / desarquivar cliente. */
    public function archive(Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->archived_at = $client->archived_at ? null : now();
        $client->save();

        return back()->with('status', $client->archived_at ? 'Cliente arquivado.' : 'Cliente reativado.');
    }

    private function handleLogo(Request $request, Client $client): void
    {
        if (! $request->hasFile('logo')) {
            return;
        }

        $disk = 's3'; // Fixado no R2/S3 independente do disco de anexos

        // Remove o logo antigo do bucket, se houver.
        if ($client->logo_path) {
            Storage::disk($client->logo_disk ?: $disk)->delete($client->logo_path);
        }

        $path = $request->file('logo')->store("company-{$request->user()->company_id}/logos", $disk);
        $client->logo_path = $path;
        $client->logo_disk = $disk;
    }

    private function uniqueSlug(string $name, int $companyId): string
    {
        $base = Str::slug($name) ?: 'cliente';
        $slug = $base;
        $i = 1;
        while (Client::where('company_id', $companyId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
