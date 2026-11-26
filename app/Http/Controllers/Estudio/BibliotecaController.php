<?php

namespace App\Http\Controllers\Estudio;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\VideoAsset;
use App\Video\Armazenamento;
use App\Video\Batidas;
use App\Video\Logos;
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
            'logos' => VideoAsset::query()->where('tipo', 'logo')->with('client:id,name')->orderBy('nome')->get(),
            'logosWeb' => self::logosWeb(),
            'clientes' => Client::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Logos que o estúdio achou na internet (cache compartilhado do App\Video\Logos). */
    private static function logosWeb(): array
    {
        $saida = [];
        foreach (glob(Logos::pastaWeb().'/*.json') ?: [] as $meta) {
            $m = json_decode((string) file_get_contents($meta), true) ?: [];
            if (! empty($m['arquivo']) && is_file(Logos::pastaWeb().'/'.$m['arquivo'])) {
                $saida[] = ['chave' => basename($meta, '.json'), 'nome' => $m['nome'] ?? '', 'fonte' => $m['fonte'] ?? '', 'titulo' => $m['titulo'] ?? '', 'forma' => $m['forma'] ?? 'app', 'quando' => $m['quando'] ?? 0];
            }
        }
        usort($saida, fn ($a, $b) => $b['quando'] <=> $a['quando']);

        return $saida;
    }

    /** Logo enviado pela equipe: vale antes de qualquer busca na internet. */
    public function logo(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'arquivo' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'nome' => ['required', 'string', 'max:40'],
            'forma' => ['required', 'in:app,logo'],
            'client_id' => ['nullable', 'integer'],
        ]);

        $clienteId = ! empty($dados['client_id']) ? Client::query()->whereKey($dados['client_id'])->value('id') : null;
        $arquivo = $request->file('arquivo');
        $ext = strtolower($arquivo->getClientOriginalExtension()) === 'jpeg' ? 'jpg' : strtolower($arquivo->getClientOriginalExtension());
        $relativo = 'company-'.$request->user()->company_id.'/logos/'.Str::uuid().'.'.$ext;
        $destino = Armazenamento::biblioteca($relativo);
        File::ensureDirectoryExists(dirname($destino), 0775);
        $arquivo->move(dirname($destino), basename($destino));

        // Mesmo nome (e mesmo alcance) = troca o anterior.
        VideoAsset::query()->where('tipo', 'logo')->where('client_id', $clienteId)->get()
            ->filter(fn ($a) => Logos::chave($a->nome) === Logos::chave($dados['nome']))
            ->each(function ($a) {
                @unlink($a->caminhoAbsoluto());
                $a->delete();
            });

        VideoAsset::create([
            'company_id' => $request->user()->company_id, 'client_id' => $clienteId, 'tipo' => 'logo',
            'nome' => trim($dados['nome']), 'path' => $relativo, 'mime' => $ext, 'tamanho' => filesize($destino),
            'humor' => $dados['forma'], // formato do logo: "app" (ícone quadrado) ou "logo" (sobre placa)
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Logo salvo. Os próximos vídeos que citarem "'.trim($dados['nome']).'" usam ele.');
    }

    public function logoWeb(string $chave)
    {
        abort_unless(preg_match('/^[a-z0-9]{1,60}$/', $chave), 404);
        $meta = json_decode((string) @file_get_contents(Logos::pastaWeb()."/$chave.json"), true) ?: [];
        $arquivo = Logos::pastaWeb().'/'.basename((string) ($meta['arquivo'] ?? ''));
        abort_unless(! empty($meta['arquivo']) && is_file($arquivo), 404);

        return response()->file($arquivo, ['Cache-Control' => 'private, max-age=3600']);
    }

    /** Esquece um logo achado na internet: a próxima edição busca de novo. */
    public function esquecerLogo(string $chave): RedirectResponse
    {
        abort_unless(preg_match('/^[a-z0-9]{1,60}$/', $chave), 404);
        $meta = json_decode((string) @file_get_contents(Logos::pastaWeb()."/$chave.json"), true) ?: [];
        if (! empty($meta['arquivo'])) {
            @unlink(Logos::pastaWeb().'/'.basename($meta['arquivo']));
        }
        @unlink(Logos::pastaWeb()."/$chave.json");

        return back()->with('status', 'Logo esquecido: o estúdio busca de novo na próxima edição.');
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
        $tipo = $asset->tipo;
        $asset->delete();

        return back()->with('status', $tipo === 'logo' ? 'Logo removido.' : 'Música removida.');
    }

    public function ouvir(VideoAsset $asset)
    {
        abort_unless(is_file($asset->caminhoAbsoluto()), 404);
        DB::disconnect();

        return response()->file($asset->caminhoAbsoluto(), ['Cache-Control' => 'private, max-age=86400']);
    }

    /** Amostra de uma voz da narração (gerada pelo php artisan video:vozes). */
    public function amostraDeVoz(string $voz)
    {
        $arquivo = \App\Video\Narracao::amostra($voz);
        abort_unless($arquivo, 404);
        DB::disconnect();

        return response()->file($arquivo, ['Content-Type' => 'audio/mpeg', 'Cache-Control' => 'private, max-age=86400']);
    }
}
