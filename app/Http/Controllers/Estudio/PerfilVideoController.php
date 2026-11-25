<?php

namespace App\Http\Controllers\Estudio;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\VideoAsset;
use App\Models\VideoLearning;
use App\Models\VideoProfile;
use App\Video\Armazenamento;
use App\Video\Ferramentas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Identidade de vídeo do cliente + o caderno de aprendizados dele.
 */
class PerfilVideoController extends Controller
{
    public function edit(Client $client)
    {
        $this->authorize('view', $client);

        return view('estudio.perfil', [
            'client' => $client,
            'perfil' => VideoProfile::doCliente($client),
            'musicas' => VideoAsset::query()->where('tipo', 'musica')->paraCliente($client->id)->orderBy('nome')->get(),
            'aprendizados' => VideoLearning::query()->where('client_id', $client->id)->whereIn('status', ['pendente', 'aplicado'])->latest()->get(),
        ]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $cor = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];
        $dados = $request->validate([
            'cor_primaria' => $cor, 'cor_destaque' => $cor, 'cor_texto' => $cor, 'cor_fundo' => $cor,
            'fonte_titulo' => ['required', Rule::in(VideoProfile::FONTES)],
            'fonte_texto' => ['required', Rule::in(VideoProfile::FONTES)],
            'logo_posicao' => ['required', Rule::in(array_keys(VideoProfile::POSICOES_LOGO))],
            'logo_tamanho' => ['required', 'integer', 'min:6', 'max:40'],
            'logo_opacidade' => ['required', 'integer', 'min:20', 'max:100'],
            'legenda_estilo' => ['required', Rule::in(array_keys(VideoProfile::ESTILOS_LEGENDA))],
            'legenda_posicao' => ['required', 'in:baixo,centro,alto'],
            'legenda_caixa_alta' => ['nullable', 'boolean'],
            'cor_grade' => ['required', Rule::in(array_keys(VideoProfile::GRADES))],
            'cta_texto' => ['nullable', 'string', 'max:160'],
            'instagram' => ['nullable', 'string', 'max:80'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'site' => ['nullable', 'string', 'max:120'],
            'endereco' => ['nullable', 'string', 'max:160'],
            'vibe' => ['nullable', 'string', 'max:160'],
            'instrucoes' => ['nullable', 'string', 'max:4000'],
            'musica_padrao_id' => ['nullable', 'integer'],
            'logo' => ['nullable', 'file', 'mimes:png,webp,jpg,jpeg', 'max:6144'],
            'remover_logo' => ['nullable', 'boolean'],
            'fonte' => ['nullable', 'file', 'max:10240'],
            'remover_fonte' => ['nullable', 'boolean'],
        ]);

        $perfil = VideoProfile::doCliente($client);
        $pasta = "company-{$client->company_id}/identidade";
        File::ensureDirectoryExists(Armazenamento::biblioteca($pasta), 0775);

        if (! empty($dados['musica_padrao_id']) && ! VideoAsset::query()->where('tipo', 'musica')->paraCliente($client->id)->whereKey($dados['musica_padrao_id'])->exists()) {
            $dados['musica_padrao_id'] = null;
        }

        if ($request->boolean('remover_logo') && $perfil->logo_path) {
            @unlink(Armazenamento::biblioteca($perfil->logo_path));
            $perfil->logo_path = null;
        }

        if ($request->hasFile('logo')) {
            $relativo = "$pasta/logo-{$client->id}-".Str::random(6).'.png';
            // Normaliza para PNG com transparência preservada.
            Ferramentas::ffmpeg(['-i', $request->file('logo')->getRealPath(), '-frames:v', '1', '-vf', "scale='min(1200,iw)':-1,format=rgba", Armazenamento::biblioteca($relativo)], null, null, null, 60);
            if ($perfil->logo_path) {
                @unlink(Armazenamento::biblioteca($perfil->logo_path));
            }
            $perfil->logo_path = $relativo;
        }

        if ($request->boolean('remover_fonte') && $perfil->fonte_path) {
            @unlink(Armazenamento::biblioteca($perfil->fonte_path));
            $perfil->fonte_path = null;
            $perfil->fonte_nome = null;
        }

        if ($request->hasFile('fonte')) {
            $arquivo = $request->file('fonte');
            $ext = strtolower($arquivo->getClientOriginalExtension());
            abort_unless(in_array($ext, ['ttf', 'otf'], true), 422, 'Envie a fonte em TTF ou OTF.');
            $familia = self::familiaDaFonte($arquivo->getRealPath());
            abort_unless($familia, 422, 'Não consegui ler o nome da fonte. Confira se o arquivo é uma fonte TTF/OTF válida.');

            $relativo = "$pasta/fonte-{$client->id}-".Str::slug($familia).'.'.$ext;
            $arquivo->move(dirname(Armazenamento::biblioteca($relativo)), basename($relativo));
            if ($perfil->fonte_path && $perfil->fonte_path !== $relativo) {
                @unlink(Armazenamento::biblioteca($perfil->fonte_path));
            }
            $perfil->fonte_path = $relativo;
            $perfil->fonte_nome = $familia;
        }

        unset($dados['logo'], $dados['fonte'], $dados['remover_logo'], $dados['remover_fonte']);
        $dados['legenda_caixa_alta'] = $request->boolean('legenda_caixa_alta');
        $dados['instagram'] = isset($dados['instagram']) ? ltrim(trim($dados['instagram']), '@') : null;

        $perfil->fill($dados);
        $perfil->company_id = $client->company_id;
        $perfil->client_id = $client->id;
        $perfil->save();

        return back()->with('status', 'Identidade de vídeo salva. Vale a partir da próxima prévia.');
    }

    public function logo(Client $client)
    {
        $this->authorize('view', $client);
        $caminho = VideoProfile::doCliente($client)->caminhoLogo();
        abort_unless($caminho && is_file($caminho), 404);

        return response()->file($caminho, ['Cache-Control' => 'private, max-age=86400', 'Content-Type' => 'image/png']);
    }

    public function novoAprendizado(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);
        $dados = $request->validate(['texto' => ['required', 'string', 'max:500']]);

        VideoLearning::create([
            'company_id' => $client->company_id, 'client_id' => $client->id, 'texto' => $dados['texto'],
            'etapa' => 'manual', 'status' => 'aplicado', 'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Anotado. O Claude passa a seguir isso nas próximas edições deste cliente.');
    }

    public function aprendizado(Request $request, VideoLearning $aprendizado): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $aprendizado->client);
        $dados = $request->validate([
            'status' => ['sometimes', 'in:pendente,aplicado,descartado'],
            'texto' => ['sometimes', 'string', 'max:500'],
        ]);
        $aprendizado->fill($dados)->save();

        return $request->wantsJson() ? response()->json(['ok' => true]) : back();
    }

    /** Nome da família (nameID 16, senão 1) lido da tabela "name" de um TTF/OTF. */
    public static function familiaDaFonte(string $arquivo): ?string
    {
        $f = @fopen($arquivo, 'rb');
        if (! $f) {
            return null;
        }

        try {
            $cabecalho = fread($f, 12);
            if (strlen($cabecalho) < 12) {
                return null;
            }
            $tabelas = unpack('n', substr($cabecalho, 4, 2))[1];
            $offsetName = null;
            for ($i = 0; $i < min($tabelas, 64); $i++) {
                $t = fread($f, 16);
                if (substr($t, 0, 4) === 'name') {
                    $offsetName = unpack('N', substr($t, 8, 4))[1];
                    break;
                }
            }
            if ($offsetName === null) {
                return null;
            }

            fseek($f, $offsetName);
            $h = unpack('nformato/nquantos/ninicio', fread($f, 6));
            $registros = [];
            for ($i = 0; $i < min($h['quantos'], 512); $i++) {
                $registros[] = unpack('nplataforma/ncodificacao/nidioma/nid/ntamanho/noffset', fread($f, 12));
            }

            foreach ([16, 1] as $procurado) {
                foreach ($registros as $r) {
                    if ($r['id'] !== $procurado) {
                        continue;
                    }
                    fseek($f, $offsetName + $h['inicio'] + $r['offset']);
                    $bruto = fread($f, $r['tamanho']);
                    $nome = in_array($r['plataforma'], [0, 3], true) ? mb_convert_encoding($bruto, 'UTF-8', 'UTF-16BE') : $bruto;
                    $nome = trim(preg_replace('/[^\p{L}\p{N} \-]/u', '', $nome) ?? '');
                    if ($nome !== '') {
                        return mb_substr($nome, 0, 80);
                    }
                }
            }

            return null;
        } finally {
            fclose($f);
        }
    }
}
