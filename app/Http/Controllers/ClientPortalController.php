<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientPortal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Administração do portal de aprovação de um cliente (lado da agência).
 */
class ClientPortalController extends Controller
{
    /**
     * Página do painel de aprovação de um cliente, acessível direto pelo menu
     * lateral: chave de acesso, mensagem pronta e o histórico de respostas.
     */
    public function show(Request $request, Client $client)
    {
        $this->autorizar($request, $client);

        $client->load('portal');

        return view('clients.portal', [
            'client' => $client,
            'equipe' => \App\Models\User::where('company_id', $client->company_id)
                ->orderBy('name')
                ->get(['id', 'name']),
            'aprovacoes' => \App\Models\TaskApproval::where('client_id', $client->id)
                ->with(['task:id,title,content_type,project_id', 'submitter:id,name'])
                ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
                ->orderByDesc('responded_at')
                ->orderByDesc('submitted_at')
                ->limit(50)
                ->get(),
        ]);
    }

    /** Cria o portal na primeira vez que a equipe abre a aba. */
    public function store(Request $request, Client $client): RedirectResponse
    {
        $this->autorizar($request, $client);

        if ($client->portal) {
            return back()->with('status', 'Este cliente já tem um painel.');
        }

        $portal = ClientPortal::createForClient($client);

        // Por padrão avisa quem criou o portal — melhor do que ninguém receber.
        $portal->forceFill(['notify_user_ids' => [$request->user()->id]])->save();

        return back()->with('status', 'Painel de aprovação criado.');
    }

    /** Gera um código novo; o anterior deixa de funcionar na hora. */
    public function rotate(Request $request, Client $client): RedirectResponse
    {
        $this->autorizar($request, $client);

        $portal = $this->portalDo($client);
        $portal->rotateCode();

        return back()->with('status', 'Novo código gerado. O código anterior não funciona mais.');
    }

    /** Revoga o acesso sem apagar o histórico de aprovações. */
    public function revoke(Request $request, Client $client): RedirectResponse
    {
        $this->autorizar($request, $client);

        $this->portalDo($client)->revoke();

        return back()->with('status', 'Acesso revogado. O link deixou de abrir.');
    }

    public function reactivate(Request $request, Client $client): RedirectResponse
    {
        $this->autorizar($request, $client);

        $this->portalDo($client)->reactivate();

        return back()->with('status', 'Acesso reativado.');
    }

    /** Quem recebe push quando o cliente responde, e o texto de boas-vindas. */
    public function update(Request $request, Client $client): RedirectResponse
    {
        $this->autorizar($request, $client);

        $portal = $this->portalDo($client);

        $dados = $request->validate([
            'notify_enabled' => ['nullable', 'boolean'],
            'notify_user_ids' => ['nullable', 'array'],
            'notify_user_ids.*' => ['integer'],
            'welcome_message' => ['nullable', 'string', 'max:500'],
        ]);

        // Só aceita usuários da própria empresa — o campo vem do navegador.
        $ids = collect($dados['notify_user_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique();

        $validos = $ids->isEmpty() ? collect() : \App\Models\User::whereIn('id', $ids)
            ->where('company_id', $client->company_id)
            ->pluck('id');

        $portal->forceFill([
            'notify_enabled' => (bool) ($dados['notify_enabled'] ?? false),
            'notify_user_ids' => $validos->values()->all(),
            'welcome_message' => $dados['welcome_message'] ?? null,
        ])->save();

        return back()->with('status', 'Preferências do painel salvas.');
    }

    /* --------------------------------------------------------------------- */

    private function portalDo(Client $client): ClientPortal
    {
        $portal = $client->portal;

        abort_if(! $portal, 404, 'Este cliente ainda não tem painel de aprovação.');

        return $portal;
    }

    private function autorizar(Request $request, Client $client): void
    {
        abort_unless($client->company_id === $request->user()->company_id, 403);
    }
}
