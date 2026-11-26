<?php

namespace App\Http\Controllers\Estudio;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessarEdicao;
use App\Models\Client;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\VideoAsset;
use App\Models\VideoJob;
use App\Models\VideoLearning;
use App\Models\VideoProfile;
use App\Video\Armazenamento;
use App\Video\Claude\ClaudeCli;
use App\Video\Estilos;
use App\Video\Narracao;
use App\Video\Nuvem;
use App\Video\Plano\Linha;
use App\Video\Transcricao;
use Illuminate\Http\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EdicaoController extends Controller
{
    public function store(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $dados = $request->validate([
            'estilo' => ['required', 'in:'.implode(',', array_keys(Estilos::todos()))],
            'titulo' => ['nullable', 'string', 'max:160'],
        ]);

        $estilo = Estilos::get($dados['estilo']);
        $perfil = VideoProfile::doCliente($client);

        $edicao = VideoJob::create([
            'company_id' => $client->company_id,
            'client_id' => $client->id,
            'created_by' => $request->user()->id,
            'titulo' => $dados['titulo'] ?: $estilo['nome'].' — '.now()->timezone('America/Sao_Paulo')->format('d/m'),
            'estilo' => $dados['estilo'],
            'formato' => '9:16',
            'musica_id' => $estilo['musica'] !== 'opcional' ? $perfil->musica_padrao_id : null,
            'opcoes' => ['legendas' => true, 'cartao_final' => true],
            'status' => VideoJob::RASCUNHO,
        ]);

        return redirect()->route('estudio.edicoes.show', $edicao);
    }

    public function show(VideoJob $edicao)
    {
        $this->authorize('view', $edicao->client);
        $edicao->load(['client', 'musica', 'creator:id,name', 'arquivos', 'eventos.user:id,name']);

        $previaInfo = null;
        if ($edicao->previa_versao) {
            $arquivo = $edicao->pasta()->absoluto(sprintf('renders/previa_v%02d.json', $edicao->previa_versao));
            $previaInfo = is_file($arquivo) ? json_decode((string) file_get_contents($arquivo), true) : null;
        }

        return view('estudio.edicao', [
            'edicao' => $edicao,
            'client' => $edicao->client,
            'estilo' => $edicao->estiloInfo(),
            'perfil' => VideoProfile::doCliente($edicao->client),
            'musicas' => VideoAsset::query()->where('tipo', 'musica')->paraCliente($edicao->client_id)->orderBy('nome')->get(),
            'aprendizadosPendentes' => VideoLearning::where('video_job_id', $edicao->id)->where('status', 'pendente')->get(),
            'previaInfo' => $previaInfo,
            'projetos' => $edicao->client->projects()->with(['columns' => fn ($q) => $q->orderBy('position')])->orderBy('name')->get(['id', 'name', 'client_id']),
            'claudeAtivo' => ClaudeCli::ativo(),
            'transcricaoAtiva' => Transcricao::disponivel(),
            'dadosArquivos' => $edicao->arquivos->map(fn ($a) => UploadController::json($a))->values(),
        ]);
    }

    public function update(Request $request, VideoJob $edicao): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $edicao->client);

        $dados = $request->validate([
            'titulo' => ['sometimes', 'required', 'string', 'max:160'],
            'briefing' => ['sometimes', 'nullable', 'string', 'max:6000'],
            'formato' => ['sometimes', 'in:'.implode(',', array_keys(VideoJob::FORMATOS))],
            'duracao_alvo' => ['sometimes', 'nullable', 'integer', 'min:5', 'max:180'],
            'musica_id' => ['sometimes', 'nullable', 'integer'],
            'opcoes' => ['sometimes', 'array'],
            'opcoes.legendas' => ['sometimes', 'boolean'],
            'opcoes.cartao_final' => ['sometimes', 'boolean'],
            'opcoes.logo' => ['sometimes', 'boolean'],
            'opcoes.narracao' => ['sometimes', 'boolean'],
            'voz' => ['sometimes', 'nullable', 'string', 'in:'.implode(',', array_keys(Narracao::vozes()))],
            'identidade' => ['sometimes', 'array'],
            'ordem' => ['sometimes', 'array'],
            'ordem.*' => ['integer'],
        ]);

        // Depois de enviada, só o título muda (o resto já entrou na edição).
        if (! $edicao->aceitaArquivos()) {
            $dados = array_intersect_key($dados, ['titulo' => true]);
        }

        if (array_key_exists('musica_id', $dados) && $dados['musica_id']) {
            abort_unless(VideoAsset::query()->where('tipo', 'musica')->paraCliente($edicao->client_id)->whereKey($dados['musica_id'])->exists(), 422, 'Música inválida.');
        }

        if (isset($dados['opcoes'])) {
            $dados['opcoes'] = array_merge($edicao->opcoes ?? [], array_map('boolval', $dados['opcoes']));
        }

        if (array_key_exists('identidade', $dados)) {
            $dados['opcoes'] = array_merge($dados['opcoes'] ?? $edicao->opcoes ?? [], ['identidade' => self::limparIdentidade($dados['identidade'])]);
            unset($dados['identidade']);
        }

        if (array_key_exists('voz', $dados)) {
            $dados['opcoes'] = array_merge($dados['opcoes'] ?? $edicao->opcoes ?? [], ['voz' => $dados['voz'] ?: null]);
            unset($dados['voz']);
        }

        if (isset($dados['ordem'])) {
            foreach (array_values($dados['ordem']) as $posicao => $id) {
                $edicao->arquivos()->whereKey($id)->update(['ordem' => $posicao]);
            }
            unset($dados['ordem']);
        }

        $edicao->fill($dados)->save();

        return $request->wantsJson() ? response()->json(['ok' => true]) : back()->with('status', 'Salvo.');
    }

    public function iniciar(Request $request, VideoJob $edicao): RedirectResponse
    {
        $this->authorize('update', $edicao->client);
        abort_unless($edicao->aceitaArquivos(), 422, 'Esta edição já foi enviada.');

        $arquivos = $edicao->arquivos()->get();
        $prontos = $arquivos->where('status', 'pronto');

        if ($arquivos->where('status', 'enviando')->isNotEmpty()) {
            return back()->with('erro', 'Ainda tem arquivo subindo. Espere terminar (ou remova o que travou).');
        }
        if ($prontos->isEmpty()) {
            return back()->with('erro', 'Envie pelo menos um vídeo ou foto.');
        }
        if ($edicao->estilo === 'antes_depois' && ($prontos->where('papel', 'antes')->isEmpty() || $prontos->where('papel', 'depois')->isEmpty())) {
            return back()->with('erro', 'Marque pelo menos um arquivo como "antes" e um como "depois".');
        }

        $livre = Armazenamento::espacoLivreGb();
        if ($livre !== null && $livre < (float) config('video.limites.disco_minimo_gb')) {
            return back()->with('erro', "Disco do servidor quase cheio ({$livre} GB). Avise o administrador.");
        }

        $edicao->forceFill(['status' => VideoJob::NA_FILA, 'progresso' => 0, 'mensagem' => 'Na fila', 'erro' => null, 'sinal_em' => now()])->save();
        $edicao->registrar('sistema', 'Edição enviada por '.$request->user()->name.' ('.$prontos->count().' arquivo(s), '.Linha::formatar($prontos->sum('duracao')).' de material).', [], $request->user()->id);

        ProcessarEdicao::dispatch($edicao->id, 'editar');

        return redirect()->route('estudio.edicoes.show', $edicao);
    }

    /** Polling da tela: estado, progresso e mensagens novas da linha do tempo. */
    public function status(Request $request, VideoJob $edicao): JsonResponse
    {
        $this->authorize('view', $edicao->client);
        $desde = (int) $request->query('desde', 0);

        $eventos = $edicao->eventos()->with('user:id,name')->where('id', '>', $desde)->limit(30)->get()
            ->map(fn ($e) => view('estudio.partials.evento', ['evento' => $e])->render().'<!--id:'.$e->id.'-->');

        return response()->json([
            'status' => $edicao->status,
            'rotulo' => $edicao->statusLabel(),
            'em_andamento' => $edicao->emAndamento() || $edicao->status === VideoJob::AGUARDANDO_LIMITE,
            'progresso' => $edicao->progresso,
            'mensagem' => $edicao->mensagem,
            'erro' => $edicao->erro,
            'previa_versao' => $edicao->previa_versao,
            'final_versao' => $edicao->final_versao,
            'ultimo_evento' => (int) $edicao->eventos()->max('id'),
            'eventos' => $eventos,
        ]);
    }

    /** "Em 0:12 tira essa frase", "música mais baixa", "mo04 mais lento". */
    public function ajustar(Request $request, VideoJob $edicao): RedirectResponse
    {
        $this->authorize('update', $edicao->client);
        abort_unless($edicao->aceitaAjustes(), 422, 'Esta edição não aceita ajustes agora.');
        abort_if($edicao->limpo_em, 422, 'Os brutos desta edição já foram apagados pela retenção. Duplique a edição com os arquivos de novo.');

        if (! ClaudeCli::ativo()) {
            return back()->with('erro', 'Ajuste por texto precisa do Claude no servidor. Use a edição rápida do plano.');
        }

        $dados = $request->validate(['pedido' => ['required', 'string', 'min:3', 'max:3000']]);

        // "Coloca uma narração" depois da prévia: liga a opção para o Claude receber as instruções de narração.
        if (! $edicao->opcao('narracao', false) && Narracao::disponivel()
            && preg_match('/narra/iu', $dados['pedido']) && ! preg_match('/\b(tir|remov|sem|apag)\w*\b.{0,25}narra/iu', $dados['pedido'])) {
            $edicao->forceFill(['opcoes' => array_merge($edicao->opcoes ?? [], ['narracao' => true])])->save();
        }

        $edicao->registrar('pedido', $dados['pedido'], [], $request->user()->id);
        $edicao->forceFill(['status' => VideoJob::AJUSTANDO, 'progresso' => 0, 'mensagem' => 'Na fila para ajuste', 'erro' => null, 'sinal_em' => now()])->save();
        ProcessarEdicao::dispatch($edicao->id, 'ajustar', ['pedido' => $dados['pedido']]);

        return back();
    }

    /**
     * Edição rápida sem Claude: liga/desliga trechos e animações, corrige
     * grafia de legenda, volume da música, textos do cartão final.
     */
    public function plano(Request $request, VideoJob $edicao): RedirectResponse
    {
        $this->authorize('update', $edicao->client);
        abort_unless($edicao->aceitaAjustes(), 422, 'Esta edição não aceita ajustes agora.');
        abort_if($edicao->limpo_em, 422, 'Os brutos desta edição já foram apagados pela retenção.');

        $dados = $request->validate([
            'segmentos_fora' => ['array'], 'segmentos_fora.*' => ['string', 'max:10'],
            'elementos_fora' => ['array'], 'elementos_fora.*' => ['string', 'max:10'],
            'textos' => ['array'], 'textos.*' => ['nullable', 'string', 'max:140'],
            'correcoes' => ['nullable', 'string', 'max:2000'],
            'legendas' => ['nullable', 'boolean'],
            'volume_musica' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'cartao_titulo' => ['nullable', 'string', 'max:60'],
            'cartao_linhas' => ['nullable', 'string', 'max:300'],
            'cartao_ativo' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'boolean'],
            'identidade' => ['nullable', 'array'],
            'narracao_ativo' => ['nullable', 'boolean'],
            'narracao' => ['array'], 'narracao.*' => ['nullable', 'string', 'max:500'],
            'voz' => ['nullable', 'string', 'in:'.implode(',', array_keys(Narracao::vozes()))],
        ]);

        $plano = $edicao->plano;
        $fora = $dados['segmentos_fora'] ?? [];
        $plano['segmentos'] = array_values(array_filter($plano['segmentos'], fn ($s) => $s['id'] !== 'fim' && ! in_array($s['id'], $fora, true)));

        if (! $plano['segmentos']) {
            return back()->with('erro', 'Deixe pelo menos um trecho no vídeo.');
        }

        $elFora = $dados['elementos_fora'] ?? [];
        $plano['elementos'] = array_values(array_filter(array_map(function ($el) use ($dados) {
            if (isset($dados['textos'][$el['id']]) && filled($dados['textos'][$el['id']])) {
                $el['texto'] = $dados['textos'][$el['id']];
            }

            return $el;
        }, $plano['elementos']), fn ($el) => ! in_array($el['id'], $elFora, true)));

        foreach (preg_split('/\r?\n/', (string) ($dados['correcoes'] ?? '')) as $linha) {
            if (preg_match('/^\s*(.+?)\s*(?:=>|->|=|→)\s*(.+?)\s*$/u', $linha, $m)) {
                $plano['legendas']['correcoes'][$m[1]] = $m[2];
            }
        }

        $plano['legendas']['ativo'] = (bool) ($dados['legendas'] ?? false);

        if (isset($dados['volume_musica'])) {
            $plano['musica']['volume'] = round($dados['volume_musica'] / 100, 2);
        }

        $plano['cartao_final']['ativo'] = (bool) ($dados['cartao_ativo'] ?? false);
        $plano['cartao_final']['titulo'] = (string) ($dados['cartao_titulo'] ?? $plano['cartao_final']['titulo']);
        if (array_key_exists('cartao_linhas', $dados)) {
            $plano['cartao_final']['linhas'] = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $dados['cartao_linhas']))));
        }

        // Narração: texto editado à mão (fala apagada sai do vídeo). Só a fala que mudou é gerada de novo.
        if (! empty($plano['narracao']['falas'])) {
            $plano['narracao']['falas'] = array_values(array_filter(array_map(function ($f) use ($dados) {
                if (array_key_exists($f['id'], $dados['narracao'] ?? [])) {
                    $f['texto'] = trim((string) $dados['narracao'][$f['id']]);
                }

                return $f;
            }, $plano['narracao']['falas']), fn ($f) => $f['texto'] !== ''));
        }

        unset($plano['correcoes_automaticas']);

        // Logo, identidade e narração deste vídeo ficam nas opções da edição (o render lê de lá).
        $opcoes = [
            'logo' => (bool) ($dados['logo'] ?? false),
            'identidade' => self::limparIdentidade($dados['identidade'] ?? []),
        ];
        if (! empty($plano['narracao']['falas'])) {
            $opcoes['narracao'] = (bool) ($dados['narracao_ativo'] ?? false);
            $opcoes['voz'] = ($dados['voz'] ?? null) ?: null;
            $plano['narracao']['ativo'] = $opcoes['narracao'];
        }
        $edicao->forceFill(['opcoes' => array_merge($edicao->opcoes ?? [], $opcoes)])->save();

        $edicao->registrar('pedido', 'Edição rápida do plano'.($fora ? ' (tirou '.implode(', ', $fora).')' : '').($elFora ? ' (tirou '.implode(', ', $elFora).')' : '').'.', [], $request->user()->id);
        $edicao->forceFill(['status' => VideoJob::AJUSTANDO, 'progresso' => 0, 'mensagem' => 'Na fila', 'erro' => null, 'sinal_em' => now()])->save();
        // Texto com codificação quebrada (colado de outro programa) não pode derrubar a fila.
        array_walk_recursive($plano, function (&$v) {
            if (is_string($v)) {
                $v = mb_scrub($v, 'UTF-8');
            }
        });
        ProcessarEdicao::dispatch($edicao->id, 'rerenderizar', ['plano' => $plano]);

        return back();
    }

    public function finalizar(Request $request, VideoJob $edicao): RedirectResponse
    {
        $this->authorize('update', $edicao->client);
        abort_unless(in_array($edicao->status, [VideoJob::REVISAO, VideoJob::PRONTO], true) && $edicao->previa_path, 422);
        abort_if($edicao->limpo_em, 422, 'Os brutos desta edição já foram apagados pela retenção.');

        $edicao->registrar('sistema', $request->user()->name.' aprovou a prévia v'.$edicao->previa_versao.'. Gerando o final em alta.', [], $request->user()->id);
        $edicao->forceFill(['status' => VideoJob::FINALIZANDO, 'progresso' => 0, 'mensagem' => 'Na fila', 'erro' => null, 'sinal_em' => now()])->save();
        ProcessarEdicao::dispatch($edicao->id, 'finalizar');

        return back();
    }

    /** O vídeo final entra num card (novo ou existente) e segue o fluxo de aprovação de sempre. */
    public function enviarParaCard(Request $request, VideoJob $edicao): RedirectResponse
    {
        $this->authorize('update', $edicao->client);
        abort_unless($edicao->final_path, 422, 'Gere o vídeo final antes.');

        $dados = $request->validate([
            'task_id' => ['nullable', 'integer'],
            'project_id' => ['nullable', 'integer'],
            'column_id' => ['nullable', 'integer'],
            'titulo' => ['nullable', 'string', 'max:255'],
            'incluir_capa' => ['nullable', 'boolean'],
        ]);

        if (! empty($dados['task_id'])) {
            $task = Task::where('client_id', $edicao->client_id)->findOrFail($dados['task_id']);
        } else {
            $projeto = $edicao->client->projects()->findOrFail($dados['project_id'] ?? 0);
            $coluna = ! empty($dados['column_id']) ? $projeto->columns()->findOrFail($dados['column_id']) : $projeto->columns()->orderBy('position')->first();
            abort_unless($coluna, 422, 'O projeto não tem colunas.');

            $task = $projeto->tasks()->create([
                'company_id' => $edicao->company_id,
                'client_id' => $edicao->client_id,
                'column_id' => $coluna->id,
                'created_by' => $request->user()->id,
                'title' => $dados['titulo'] ?: $edicao->titulo,
                'caption' => $edicao->legenda_post,
                'content_type' => 'reel',
                'position' => (int) $projeto->tasks()->where('column_id', $coluna->id)->max('position') + 1,
            ]);
            TaskActivity::registrar($task, TaskActivity::TYPE_CREATED, 'criou a tarefa pelo Estúdio de vídeo');
        }

        if (blank($task->caption) && filled($edicao->legenda_post)) {
            $task->forceFill(['caption' => $edicao->legenda_post])->save();
        }

        $disco = config('filesystems.attachments_disk');
        $pasta = "company-{$task->company_id}/tasks/{$task->id}";
        $nomeBase = Str::slug($edicao->client->name.' '.$edicao->titulo);
        // Final que já foi para o Drive: baixa um temporário só para anexar.
        $finalLocal = $edicao->pasta()->absoluto($edicao->final_path);
        $temporario = null;
        if (! is_file($finalLocal)) {
            abort_unless($edicao->noDrive(), 422, 'O vídeo final não está mais disponível. Gere de novo.');
            DB::disconnect();
            $finalLocal = $temporario = Nuvem::baixarTemporario($edicao);
        }
        $arquivos = [[$finalLocal, "$nomeBase.mp4", 'video/mp4', false]];

        if (($dados['incluir_capa'] ?? false) && $edicao->capa_path && is_file($edicao->pasta()->absoluto($edicao->capa_path))) {
            $arquivos[] = [$edicao->pasta()->absoluto($edicao->capa_path), "$nomeBase-capa.png", 'image/png', true];
        }

        // Upload para o bucket/Drive pode demorar: sem prender conexão do banco.
        DB::disconnect();
        $posicao = (int) $task->attachments()->whereNull('folder_id')->max('position') + 1;

        foreach ($arquivos as [$origem, $nome, $mime, $imagem]) {
            $path = Storage::disk($disco)->putFileAs($pasta, new File($origem), Str::uuid().'.'.pathinfo($nome, PATHINFO_EXTENSION));
            abort_if($path === false, 500, 'O armazenamento recusou o arquivo. Tente de novo.');

            $task->attachments()->create([
                'company_id' => $task->company_id, 'uploaded_by' => $request->user()->id, 'disk' => $disco,
                'path' => $path, 'original_name' => $nome, 'mime_type' => $mime, 'size' => filesize($origem),
                'is_image' => $imagem, 'position' => $posicao++,
            ]);
        }
        if ($temporario) {
            @unlink($temporario);
        }

        TaskActivity::registrar($task, TaskActivity::TYPE_ATTACHMENT_ADDED, 'anexou o vídeo editado no Estúdio ("'.Str::limit($edicao->titulo, 50).'")', ['edicao' => $edicao->id]);
        $edicao->forceFill(['task_id' => $task->id])->save();
        $edicao->registrar('sistema', "Vídeo final v{$edicao->final_versao} anexado ao card \"{$task->title}\".", ['task' => $task->id], $request->user()->id);

        return back()->with('status', 'Vídeo anexado ao card "'.$task->title.'". De lá ele segue para a aprovação do cliente.');
    }

    public function tentarDeNovo(Request $request, VideoJob $edicao): RedirectResponse
    {
        $this->authorize('update', $edicao->client);
        abort_unless(in_array($edicao->status, [VideoJob::ERRO, VideoJob::AGUARDANDO_LIMITE], true), 422);

        // Já tem plano: só falta renderizar. Sem plano: retoma a edição (o que já foi preparado é reaproveitado).
        $acao = $edicao->plano ? 'rerenderizar' : 'editar';
        $edicao->forceFill(['status' => VideoJob::NA_FILA, 'erro' => null, 'mensagem' => 'Na fila', 'sinal_em' => now()])->save();
        $edicao->registrar('sistema', $request->user()->name.' pediu para tentar de novo.', [], $request->user()->id);
        ProcessarEdicao::dispatch($edicao->id, $acao, $acao === 'rerenderizar' ? ['plano' => $edicao->plano] : []);

        return back();
    }

    public function cancelar(VideoJob $edicao): RedirectResponse
    {
        $this->authorize('update', $edicao->client);
        abort_unless(in_array($edicao->status, [VideoJob::NA_FILA, VideoJob::AGUARDANDO_LIMITE], true), 422, 'Só dá para cancelar enquanto está na fila.');

        $edicao->forceFill(['status' => $edicao->previa_path ? VideoJob::REVISAO : VideoJob::RASCUNHO, 'mensagem' => null])->save();
        $edicao->registrar('sistema', 'Cancelado pela equipe.');

        return back();
    }

    /** Mesmo material, outro estilo (ou a mesma ideia de novo). Os brutos são reaproveitados. */
    public function duplicar(Request $request, VideoJob $edicao): RedirectResponse
    {
        $this->authorize('update', $edicao->client);
        abort_if($edicao->limpo_em, 422, 'Os brutos desta edição já foram apagados.');

        $dados = $request->validate(['estilo' => ['nullable', 'in:'.implode(',', array_keys(Estilos::todos()))]]);
        $estilo = $dados['estilo'] ?? $edicao->estilo;

        $nova = $edicao->replicate(['status', 'etapa', 'progresso', 'mensagem', 'erro', 'plano', 'plano_versao', 'previa_versao', 'final_versao',
            'previa_path', 'final_path', 'capa_path', 'legenda_post', 'feito_por_claude', 'task_id', 'claude_sessao', 'claude_uso',
            'tentativas', 'sinal_em', 'iniciado_em', 'concluido_em', 'limpo_em']);
        $nova->forceFill([
            'estilo' => $estilo,
            'titulo' => $estilo === $edicao->estilo ? $edicao->titulo.' (cópia)' : Estilos::get($estilo)['nome'].' — '.$edicao->titulo,
            'status' => VideoJob::RASCUNHO,
            'created_by' => $request->user()->id,
        ])->save();
        $nova->pasta()->garantir();

        foreach ($edicao->arquivos()->where('status', 'pronto')->get() as $arq) {
            $copia = $arq->replicate();
            $copia->video_job_id = $nova->id;
            $copia->path = 'brutos/'.Str::uuid().'.'.pathinfo($arq->path, PATHINFO_EXTENSION);
            $origem = $arq->caminhoAbsoluto();
            $destino = $nova->pasta()->absoluto($copia->path);
            // Link físico quando dá (mesmo disco): duplicar não ocupa o dobro.
            if (! @link($origem, $destino)) {
                copy($origem, $destino);
            }
            $copia->save();
            $mini = $edicao->pasta()->absoluto("trabalho/mini_{$arq->chave()}.png");
            if (is_file($mini)) {
                @copy($mini, $nova->pasta()->dir('trabalho')."/mini_{$copia->chave()}.png");
            }
        }

        return redirect()->route('estudio.edicoes.show', $nova)->with('status', 'Edição duplicada com o mesmo material. Ajuste o briefing e envie.');
    }

    /** Só os campos ajustáveis, sem vazios: campo vazio = vale o do cliente. */
    private static function limparIdentidade(array $dados): array
    {
        $limpo = [];
        foreach (\App\Video\Identidade::AJUSTAVEIS as $campo) {
            $valor = $dados[$campo] ?? null;
            if ($valor === null || $valor === '') {
                continue;
            }
            $limpo[$campo] = is_scalar($valor) ? mb_substr((string) $valor, 0, 40) : null;
        }

        return array_filter($limpo, fn ($v) => $v !== null);
    }

    /**
     * Exclui a edição em qualquer estado — inclusive travada ou processando
     * (o worker, ao ver que ela sumiu, para; a pasta que ele recriar a limpeza
     * de órfãos apaga). A cópia no Drive só sai se a equipe marcar.
     */
    public function destroy(Request $request, VideoJob $edicao): RedirectResponse
    {
        $this->authorize('update', $edicao->client);

        $client = $edicao->client;
        if ($request->boolean('apagar_drive')) {
            Nuvem::apagar($edicao);
        }
        $edicao->forceFill(['status' => VideoJob::CANCELADO])->saveQuietly();
        $edicao->pasta()->apagarTudo();
        $edicao->eventos()->delete();
        $edicao->arquivos()->delete();
        $edicao->delete();

        return redirect()->route('clients.estudio', $client)->with('status', 'Edição excluída.');
    }

    /** Envia (de novo) o vídeo final ao Drive — quando o envio automático falhou. */
    public function drive(VideoJob $edicao): RedirectResponse
    {
        $this->authorize('update', $edicao->client);
        abort_unless(Nuvem::ativo(), 422, 'O Google Drive não está configurado no servidor.');
        abort_unless($edicao->final_path && is_file($edicao->pasta()->absoluto($edicao->final_path)), 422, 'Não há vídeo final no servidor para enviar.');

        $edicao->forceFill(['nuvem_status' => 'enviando', 'nuvem_erro' => null])->save();
        \App\Jobs\ArquivarNoDrive::dispatch($edicao->id);

        return back()->with('status', 'Enviando ao Drive…');
    }
}
