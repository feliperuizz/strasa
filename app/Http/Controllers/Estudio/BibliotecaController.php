<?php

namespace App\Http\Controllers\Estudio;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\VideoAsset;
use App\Video\Armazenamento;
use App\Video\Batidas;
use App\Video\Sondagem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Biblioteca de músicas da agência. Música só entra com a fonte/licença
 * anotada: o vídeo vai para o Instagram do cliente, e música sem licença
 * derruba o áudio (ou o post) — e pode virar problema jurídico.
 */
class BibliotecaController extends Controller
{
    public function index()
    {
        return view('estudio.biblioteca', [
            'musicas' => VideoAsset::query()->where('tipo', 'musica')->with('client:id,name')->orderBy('nome')->get(),
            'clientes' => Client::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'arquivo' => ['required', 'file', 'max:61440'],
            'nome' => ['nullable', 'string', 'max:120'],
            'humor' => ['nullable', 'string', 'max:120'],
            'licenca' => ['required', 'string', 'min:4', 'max:255'],
            'client_id' => ['nullable', 'integer'],
        ], ['licenca.required' => 'Diga de onde veio a música e qual a licença (ex.: "YouTube Audio Library — livre", "Artlist — assinatura da agência").']);

        $arquivo = $request->file('arquivo');
        $ext = strtolower($arquivo->getClientOriginalExtension());
        abort_unless(in_array($ext, ['mp3', 'wav', 'm4a', 'aac', 'ogg', 'flac'], true), 422, 'Envie MP3, WAV, M4A, AAC, OGG ou FLAC.');

        $clienteId = null;
        if (! empty($dados['client_id'])) {
            $clienteId = Client::query()->whereKey($dados['client_id'])->value('id');
        }

        $relativo = 'company-'.$request->user()->company_id.'/musica/'.Str::uuid().'.'.$ext;
        $destino = Armazenamento::biblioteca($relativo);
        File::ensureDirectoryExists(dirname($destino), 0775);
        $arquivo->move(dirname($destino), basename($destino));

        try {
            $info = Sondagem::arquivo($destino);
            abort_unless($info['tem_audio'], 422, 'O arquivo não tem áudio.');
        } catch (\Throwable $e) {
            @unlink($destino);
            abort(422, 'Não consegui ler o áudio: '.$e->getMessage());
        }

        DB::disconnect();
        $batidas = rescue(fn () => Batidas::analisar($destino), ['bpm' => null, 'batidas' => []], false);

        VideoAsset::create([
            'company_id' => $request->user()->company_id,
            'client_id' => $clienteId,
            'tipo' => 'musica',
            'nome' => $dados['nome'] ?: Str::limit(pathinfo($arquivo->getClientOriginalName(), PATHINFO_FILENAME), 120, ''),
            'path' => $relativo,
            'mime' => $ext,
            'tamanho' => filesize($destino),
            'duracao' => $info['duracao'],
            'bpm' => $batidas['bpm'],
            'batidas' => $batidas['batidas'],
            'humor' => $dados['humor'] ?? null,
            'licenca' => $dados['licenca'],
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Música adicionada'.($batidas['bpm'] ? " ({$batidas['bpm']} BPM)." : '.'));
    }

    public function destroy(VideoAsset $asset): RedirectResponse
    {
        @unlink($asset->caminhoAbsoluto());
        $asset->delete();

        return back()->with('status', 'Música removida.');
    }

    public function ouvir(VideoAsset $asset)
    {
        abort_unless(is_file($asset->caminhoAbsoluto()), 404);
        DB::disconnect();

        return response()->file($asset->caminhoAbsoluto(), ['Cache-Control' => 'private, max-age=86400']);
    }
}
