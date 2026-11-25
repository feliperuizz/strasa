<?php

namespace App\Http\Controllers\Estudio;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\VideoJob;
use App\Models\VideoLearning;
use App\Models\VideoProfile;
use App\Video\Armazenamento;
use App\Video\Claude\ClaudeCli;
use App\Video\Diagnostico;
use App\Video\Estilos;
use App\Video\Transcricao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EstudioController extends Controller
{
    /** Painel geral: edições de todos os clientes e a saúde do motor. */
    public function index(Request $request)
    {
        $edicoes = VideoJob::query()
            ->with(['client:id,name,color,logo_path,logo_disk', 'creator:id,name'])
            ->whereNot('status', VideoJob::RASCUNHO)
            ->latest('updated_at')
            ->limit(60)
            ->get();

        return view('estudio.index', [
            'edicoes' => $edicoes,
            'rascunhos' => VideoJob::query()->with('client:id,name')->where('status', VideoJob::RASCUNHO)->latest('updated_at')->limit(10)->get(),
            'clientes' => Client::query()->active()->orderBy('name')->get(['id', 'name', 'color', 'logo_path', 'logo_disk']),
            'emFila' => VideoJob::query()->whereIn('status', VideoJob::EM_ANDAMENTO)->count(),
            'diagnostico' => Diagnostico::ultimo(),
            'motor' => $this->motor(),
        ]);
    }

    public function cliente(Client $client)
    {
        $this->authorize('view', $client);

        return view('estudio.cliente', [
            'client' => $client,
            'perfil' => VideoProfile::doCliente($client),
            'estilos' => Estilos::todos(),
            'edicoes' => VideoJob::query()->where('client_id', $client->id)->with('creator:id,name')->latest('updated_at')->limit(50)->get(),
            'aprendizados' => VideoLearning::query()->where('client_id', $client->id)->whereIn('status', ['pendente', 'aplicado'])->latest()->get(),
            'motor' => $this->motor(),
        ]);
    }

    public function diagnostico(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isManager(), 403);
        $r = (new Diagnostico)->rodar(false);

        return back()->with($r['erros'] ? 'erro' : 'status', $r['erros']
            ? "Diagnóstico: {$r['erros']} problema(s). Veja a lista no painel."
            : 'Diagnóstico ok'.($r['avisos'] ? " ({$r['avisos']} aviso(s))." : '.'));
    }

    private function motor(): array
    {
        return [
            'claude' => ClaudeCli::ativo(),
            'transcricao' => Transcricao::disponivel(),
            'disco' => Armazenamento::espacoLivreGb(),
        ];
    }
}
