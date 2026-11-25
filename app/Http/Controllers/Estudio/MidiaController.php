<?php

namespace App\Http\Controllers\Estudio;

use App\Http\Controllers\Controller;
use App\Models\VideoJob;
use App\Models\VideoJobFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Serve prévias, finais, capas e miniaturas do disco local do estúdio.
 *
 * BinaryFileResponse responde a Range (o player pula para qualquer ponto
 * sem baixar tudo). A conexão com o banco é solta antes de transmitir:
 * um vídeo de 80 MB em 4G segurava conexão MySQL por minutos (a lição do
 * "Too many connections" de 26/08/2026).
 */
class MidiaController extends Controller
{
    public function show(Request $request, VideoJob $edicao, string $tipo, ?int $versao = null)
    {
        $this->authorize('view', $edicao->client);

        $relativo = match (true) {
            $versao !== null => sprintf('renders/%s_v%02d.%s', $tipo, $versao, $tipo === 'capa' ? 'jpg' : 'mp4'),
            $tipo === 'previa' => $edicao->previa_path,
            $tipo === 'final' => $edicao->final_path,
            default => $edicao->capa_path,
        };

        abort_unless($relativo && ! str_contains($relativo, '..'), 404);
        $caminho = $edicao->pasta()->absoluto($relativo);
        abort_unless(is_file($caminho), 404);

        DB::disconnect();

        if ($request->boolean('baixar')) {
            $nome = Str::slug($edicao->client->name.' '.$edicao->titulo).'-'.basename($caminho);

            return response()->download($caminho, $nome);
        }

        return response()->file($caminho, [
            'Cache-Control' => 'private, max-age=86400',
            'Content-Type' => $tipo === 'capa' ? 'image/jpeg' : 'video/mp4',
        ]);
    }

    public function miniatura(VideoJobFile $arquivo)
    {
        $this->authorize('view', $arquivo->job->client);
        $caminho = $arquivo->job->pasta()->absoluto("trabalho/mini_{$arquivo->chave()}.jpg");
        abort_unless(is_file($caminho), 404);
        DB::disconnect();

        return response()->file($caminho, ['Cache-Control' => 'private, max-age=604800', 'Content-Type' => 'image/jpeg']);
    }
}
