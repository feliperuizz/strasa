<?php

namespace App\Http\Controllers\Estudio;

use App\Http\Controllers\Controller;
use App\Models\VideoJob;
use App\Models\VideoJobFile;
use App\Video\Armazenamento;
use App\Video\FalhaDeFerramenta;
use App\Video\Ferramentas;
use App\Video\Sondagem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Upload dos brutos em pedaços (retomável).
 *
 * Vídeo 4K de 1 minuto passa de 400 MB. Mandar de uma vez esbarra em:
 * post_max_size/upload_max_filesize, limite de corpo do LiteSpeed, os
 * 100 MB por requisição do Cloudflare (se o domínio estiver atrás dele) e
 * conexão de celular que cai no meio. Em pedaços de 8 MB nenhum desses
 * limites é atingido, e um pedaço que falha é reenviado sem perder o resto.
 *
 * O arquivo é montado direto no destino final (append), sem cópia extra.
 */
class UploadController extends Controller
{
    public const EXTENSOES = [
        'video' => ['mp4', 'mov', 'm4v', 'avi', 'mkv', 'webm', '3gp', 'mts', 'hevc'],
        'imagem' => ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'],
    ];

    public function iniciar(Request $request, VideoJob $edicao): JsonResponse
    {
        $this->authorize('update', $edicao->client);
        abort_unless($edicao->aceitaArquivos(), 422, 'Esta edição já foi enviada; não aceita mais arquivos.');

        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'tamanho' => ['required', 'integer', 'min:1'],
            'papel' => ['nullable', 'in:'.implode(',', VideoJobFile::PAPEIS)],
        ]);

        $ext = strtolower(pathinfo($dados['nome'], PATHINFO_EXTENSION));
        $todas = array_merge(...array_values(self::EXTENSOES));
        abort_unless(in_array($ext, $todas, true), 422, "Formato .$ext não aceito. Envie vídeo (".implode(', ', self::EXTENSOES['video']).') ou foto ('.implode(', ', self::EXTENSOES['imagem']).').');

        $limiteArquivo = (int) config('video.limites.arquivo_mb') * 1048576;
        abort_if($dados['tamanho'] > $limiteArquivo, 422, 'Arquivo maior que '.config('video.limites.arquivo_mb').' MB.');

        $atuais = $edicao->arquivos()->get();
        $maxArquivos = (int) config(! empty($edicao->estiloInfo()['longo']) ? 'video.limites.arquivos_por_edicao_longo' : 'video.limites.arquivos_por_edicao');
        abort_if($atuais->count() >= $maxArquivos, 422, "Limite de {$maxArquivos} arquivos por edição.");
        abort_if($atuais->sum('tamanho') + $dados['tamanho'] > (int) config('video.limites.edicao_mb') * 1048576, 422, 'Esta edição passaria de '.config('video.limites.edicao_mb').' MB somando todos os arquivos.');

        $livre = Armazenamento::espacoLivreGb();
        $precisa = (float) config('video.limites.disco_minimo_gb') + $dados['tamanho'] * 1.6 / 1024 ** 3;
        abort_if($livre !== null && $livre < $precisa, 507, "O disco do servidor está quase cheio ({$livre} GB livres). Avise o administrador: php artisan video:limpar libera espaço.");

        $edicao->pasta()->garantir();

        $arquivo = $edicao->arquivos()->create([
            'company_id' => $edicao->company_id,
            'papel' => $dados['papel'] ?? ($edicao->estilo === 'antes_depois' ? $this->papelPeloNome($dados['nome']) : 'bruto'),
            'ordem' => (int) $atuais->max('ordem') + 1,
            'nome_original' => Str::limit($dados['nome'], 250, ''),
            'path' => 'brutos/'.Str::uuid().'.'.$ext,
            'tamanho' => $dados['tamanho'],
            'recebido' => 0,
            'status' => 'enviando',
        ]);

        touch($arquivo->caminhoAbsoluto());

        return response()->json([
            'id' => $arquivo->id,
            'recebido' => 0,
            'pedaco' => (int) config('video.limites.pedaco_mb') * 1048576,
            'url_pedaco' => route('estudio.arquivos.pedaco', $arquivo),
            'url_concluir' => route('estudio.arquivos.concluir', $arquivo),
        ]);
    }

    public function pedaco(Request $request, VideoJobFile $arquivo): JsonResponse
    {
        $edicao = $arquivo->job;
        $this->authorize('update', $edicao->client);
        abort_unless($arquivo->status === 'enviando' && $edicao->aceitaArquivos(), 409, 'Este arquivo não está mais recebendo dados.');

        $request->validate([
            'offset' => ['required', 'integer', 'min:0'],
            'pedaco' => ['required', 'file', 'max:'.((int) config('video.limites.pedaco_mb') * 1024 + 1024)],
        ]);

        $destino = $arquivo->caminhoAbsoluto();
        $fp = fopen($destino, 'c+b');
        abort_unless($fp, 500, 'Não consegui gravar no disco do servidor.');

        try {
            flock($fp, LOCK_EX);
            clearstatcache(true, $destino);
            $atual = filesize($destino);
            $offset = (int) $request->input('offset');

            // Fora de ordem ou repetido (reenvio depois de uma queda): diz onde parou.
            if ($offset !== $atual) {
                $arquivo->forceFill(['recebido' => $atual])->saveQuietly();

                return response()->json(['recebido' => $atual, 'mensagem' => 'Continuar de onde parou.'], 409);
            }

            $pedaco = $request->file('pedaco');
            abort_if($atual + $pedaco->getSize() > $arquivo->tamanho, 422, 'O arquivo ficou maior do que o tamanho informado.');

            fseek($fp, 0, SEEK_END);
            $origem = fopen($pedaco->getRealPath(), 'rb');
            stream_copy_to_stream($origem, $fp);
            fclose($origem);
            fflush($fp);
            clearstatcache(true, $destino);
            $recebido = filesize($destino);
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        $arquivo->forceFill(['recebido' => $recebido])->saveQuietly();

        return response()->json(['recebido' => $recebido]);
    }

    public function concluir(VideoJobFile $arquivo): JsonResponse
    {
        $edicao = $arquivo->job;
        $this->authorize('update', $edicao->client);

        $caminho = $arquivo->caminhoAbsoluto();
        clearstatcache(true, $caminho);
        $tamanho = is_file($caminho) ? filesize($caminho) : 0;
        abort_unless($tamanho === (int) $arquivo->tamanho, 422, "O arquivo chegou incompleto ($tamanho de {$arquivo->tamanho} bytes). Envie de novo.");

        try {
            $info = Sondagem::arquivo($caminho);
        } catch (FalhaDeFerramenta $e) {
            $arquivo->forceFill(['status' => 'erro'])->save();
            @unlink($caminho);

            return response()->json(['message' => 'O servidor não conseguiu ler "'.$arquivo->nome_original.'": '.$e->getMessage()], 422);
        }

        if ($info['tipo'] === 'audio') {
            $arquivo->forceFill(['status' => 'erro'])->save();
            @unlink($caminho);

            return response()->json(['message' => 'Arquivo só de áudio. Música vai na Biblioteca do estúdio.'], 422);
        }

        $minutos = ($edicao->arquivos()->where('status', 'pronto')->sum('duracao') + $info['duracao']) / 60;
        $maxMinutos = (int) config(! empty($edicao->estiloInfo()['longo']) ? 'video.limites.brutos_minutos_longo' : 'video.limites.brutos_minutos');
        if ($minutos > $maxMinutos) {
            $arquivo->forceFill(['status' => 'erro'])->save();
            @unlink($caminho);

            return response()->json(['message' => "Passou de {$maxMinutos} minutos de material nesta edição. Envie só os trechos que interessam."], 422);
        }

        $arquivo->forceFill([
            'status' => 'pronto',
            'recebido' => $tamanho,
            'tipo' => $info['tipo'],
            'duracao' => $info['duracao'],
            'largura' => $info['largura'],
            'altura' => $info['altura'],
            'fps' => $info['fps'],
            'rotacao' => $info['rotacao'],
            'hdr' => $info['hdr'],
            'tem_audio' => $info['tem_audio'],
            'sondagem' => $info,
            'mime' => $info['codec'] ?? null,
        ])->save();

        $this->miniatura($arquivo);

        return response()->json(['arquivo' => self::json($arquivo)]);
    }

    public function atualizar(Request $request, VideoJobFile $arquivo): JsonResponse
    {
        $this->authorize('update', $arquivo->job->client);
        $dados = $request->validate(['papel' => ['required', 'in:'.implode(',', VideoJobFile::PAPEIS)]]);
        $arquivo->forceFill($dados)->save();

        return response()->json(['arquivo' => self::json($arquivo)]);
    }

    public function destroy(VideoJobFile $arquivo): JsonResponse
    {
        $edicao = $arquivo->job;
        $this->authorize('update', $edicao->client);
        abort_unless($edicao->aceitaArquivos(), 422, 'Esta edição já foi enviada.');

        @unlink($arquivo->caminhoAbsoluto());
        @unlink($edicao->pasta()->absoluto("trabalho/mini_{$arquivo->chave()}.png"));
        $arquivo->delete();

        return response()->json(['ok' => true]);
    }

    public static function json(VideoJobFile $a): array
    {
        return [
            'id' => $a->id,
            'nome' => $a->nome_original,
            'papel' => $a->papel,
            'status' => $a->status,
            'tipo' => $a->tipo,
            'tamanho' => $a->tamanho,
            'duracao' => $a->duracao,
            'resolucao' => $a->largura ? "{$a->largura}x{$a->altura}" : null,
            'hdr' => $a->hdr,
            'tem_audio' => $a->tem_audio,
            'miniatura' => $a->status === 'pronto' ? route('estudio.arquivos.miniatura', $a).'?v='.$a->updated_at?->timestamp : null,
            // Hora da gravação (metadados do celular): "ordenar pela hora" e a cronologia do vlog.
            'gravado_em' => $a->sondagem['gravado_em'] ?? null,
            'url_papel' => route('estudio.arquivos.update', $a),
            'url_remover' => route('estudio.arquivos.destroy', $a),
        ];
    }

    /** Miniatura leve para a lista (um quadro a 1 s, ou a própria foto reduzida). */
    private function miniatura(VideoJobFile $arquivo): void
    {
        $destino = $arquivo->job->pasta()->dir('trabalho').'/mini_'.$arquivo->chave().'.png';

        try {
            Ferramentas::ffmpeg(array_merge(
                $arquivo->tipo === 'video' ? ['-ss', Ferramentas::s(min(1.0, (float) $arquivo->duracao / 2))] : [],
                ['-i', $arquivo->caminhoAbsoluto(), '-frames:v', '1', '-vf', 'scale=360:-2', $destino]
            ), null, null, null, 60);
        } catch (\Throwable) {
            // Sem miniatura não é erro: a lista mostra um ícone.
        }
    }

    /** "antes.mp4", "IMG_depois_2.mov" → já chega com o papel certo. */
    private function papelPeloNome(string $nome): string
    {
        $n = Str::lower(Str::ascii($nome));

        return match (true) {
            str_contains($n, 'antes') || str_contains($n, 'before') => 'antes',
            str_contains($n, 'depois') || str_contains($n, 'after') => 'depois',
            default => 'bruto',
        };
    }
}
