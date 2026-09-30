<?php

namespace App\Http\Controllers;

use App\Http\Resources\Api\PostResource;
use App\Models\ApiToken;
use App\Models\Client;
use App\Models\Task;
use App\Models\TaskPublication;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Painel de Integrações: chaves da API de postagem automática (só admin).
 */
class IntegrationController extends Controller
{
    public function index(Request $request): View
    {
        $this->somenteAdmin($request);
        $companyId = $request->user()->company_id;

        $chaves = ApiToken::with(['clients:id,name,color', 'creator:id,name'])
            ->orderByRaw('revoked_at IS NOT NULL')
            ->latest()
            ->get();

        $retornos = TaskPublication::with([
            'task:id,title,client_id,project_id',
            'task.client:id,name,color',
            'apiToken:id,name',
        ])
            ->latest('updated_at')
            ->limit(30)
            ->get();

        // Fila de postagem agora: o que vai e o que está travado (sem data/hora).
        $naFila = Task::where('company_id', $companyId)
            ->where('is_published', false)
            ->whereHas('column', fn ($q) => $q->where('is_publish_column', true))
            ->with(['client:id,name,color', 'column'])
            ->orderBy('publish_date')
            ->orderBy('publish_time')
            ->get();

        return view('integrations.index', [
            'chaves' => $chaves,
            'clientes' => Client::where('company_id', $companyId)->whereNull('archived_at')->orderBy('name')->get(['id', 'name', 'color']),
            'retornos' => $retornos,
            'prontos' => $naFila->filter(fn ($t) => PostResource::motivoNaoPronto($t) === null)->count(),
            'travados' => $naFila->reject(fn ($t) => PostResource::motivoNaoPronto($t) === null)->values(),
            'raiz' => rtrim((string) config('app.url'), '/'),
            'chaveCriada' => session('chave_criada'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->somenteAdmin($request);
        $dados = $this->validar($request);

        [$chave, $segredo] = DB::transaction(function () use ($request, $dados) {
            $chave = new ApiToken([
                'company_id' => $request->user()->company_id,
                'created_by' => $request->user()->id,
            ]);
            $this->preencher($chave, $dados);
            $segredo = $chave->gerarSegredo();
            $chave->save();
            $chave->clients()->sync($dados['escopo'] === 'selecionados' ? $dados['clientes'] : []);

            return [$chave, $segredo];
        });

        return redirect()->route('integrations.index')
            ->with('chave_criada', ['id' => $chave->id, 'nome' => $chave->name, 'segredo' => $segredo]);
    }

    public function update(Request $request, ApiToken $chave): RedirectResponse
    {
        $this->somenteAdmin($request);
        $this->mesmaEmpresa($request, $chave);
        $dados = $this->validar($request);

        DB::transaction(function () use ($chave, $dados) {
            $this->preencher($chave, $dados);
            $chave->save();
            $chave->clients()->sync($dados['escopo'] === 'selecionados' ? $dados['clientes'] : []);
        });

        return back()->with('status', 'Chave "'.$chave->name.'" atualizada.');
    }

    /** Troca o segredo: o antigo para de funcionar na hora. */
    public function regenerate(Request $request, ApiToken $chave): RedirectResponse
    {
        $this->somenteAdmin($request);
        $this->mesmaEmpresa($request, $chave);
        abort_if($chave->estaRevogada(), 422, 'Chave revogada não pode ser renovada. Crie uma nova.');

        $segredo = $chave->gerarSegredo();
        $chave->save();

        return redirect()->route('integrations.index')
            ->with('chave_criada', ['id' => $chave->id, 'nome' => $chave->name, 'segredo' => $segredo, 'renovada' => true]);
    }

    /** Revogar é definitivo: para voltar, cria-se outra chave. */
    public function revoke(Request $request, ApiToken $chave): RedirectResponse
    {
        $this->somenteAdmin($request);
        $this->mesmaEmpresa($request, $chave);

        $chave->forceFill(['revoked_at' => now()])->save();

        return back()->with('status', 'Chave "'.$chave->name.'" revogada. O sistema que a usava perdeu o acesso agora.');
    }

    public function destroy(Request $request, ApiToken $chave): RedirectResponse
    {
        $this->somenteAdmin($request);
        $this->mesmaEmpresa($request, $chave);
        abort_unless($chave->estaRevogada(), 422, 'Revogue a chave antes de excluir.');

        $chave->delete();

        return back()->with('status', 'Chave excluída. O histórico de postagens informado por ela continua nos cards.');
    }

    /* --------------------------------------------------------------------- */

    private function validar(Request $request): array
    {
        $companyId = $request->user()->company_id;

        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'escopo' => ['required', Rule::in(['todos', 'selecionados'])],
            'clientes' => ['array', 'required_if:escopo,selecionados'],
            'clientes.*' => ['integer', Rule::exists('clients', 'id')->where('company_id', $companyId)],
            'ips' => ['nullable', 'string', 'max:1000', function (string $campo, mixed $valor, Closure $falhar) {
                foreach (preg_split('/[\s,;]+/', (string) $valor, -1, PREG_SPLIT_NO_EMPTY) as $ip) {
                    [$endereco, $mascara] = array_pad(explode('/', $ip, 2), 2, null);
                    $ehIp = filter_var($endereco, FILTER_VALIDATE_IP) !== false;
                    $limite = str_contains((string) $endereco, ':') ? 128 : 32;
                    $mascaraOk = $mascara === null || (ctype_digit($mascara) && (int) $mascara <= $limite);
                    if (! $ehIp || ! $mascaraOk) {
                        $falhar('"'.$ip.'" não é um IP ou faixa válida (ex.: 203.0.113.10 ou 203.0.113.0/24).');
                    }
                }
            }],
            'expira_em' => ['nullable', 'date', 'after:today'],
            'concluir_ao_publicar' => ['nullable', 'boolean'],
        ], [
            'clientes.required_if' => 'Escolha pelo menos um cliente, ou marque "Todos os clientes".',
            'expira_em.after' => 'A validade precisa ser uma data futura.',
        ]);

        $dados['clientes'] = array_map('intval', $dados['clientes'] ?? []);

        return $dados;
    }

    private function preencher(ApiToken $chave, array $dados): void
    {
        $chave->fill([
            'name' => $dados['nome'],
            'all_clients' => $dados['escopo'] === 'todos',
            'auto_complete' => (bool) ($dados['concluir_ao_publicar'] ?? false),
            'allowed_ips' => trim(implode(', ', preg_split('/[\s,;]+/', (string) ($dados['ips'] ?? ''), -1, PREG_SPLIT_NO_EMPTY))) ?: null,
            'expires_at' => ! empty($dados['expira_em']) ? \Illuminate\Support\Carbon::parse($dados['expira_em'])->endOfDay() : null,
        ]);
    }

    private function somenteAdmin(Request $request): void
    {
        abort_unless($request->user()->isAdmin(), 403);
    }

    private function mesmaEmpresa(Request $request, ApiToken $chave): void
    {
        abort_unless($chave->company_id === $request->user()->company_id, 403);
    }
}
