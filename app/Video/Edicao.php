<?php

namespace App\Video;

use App\Models\VideoJob;
use App\Models\VideoLearning;
use App\Models\VideoProfile;
use App\Video\Claude\ClaudeCli;
use App\Video\Claude\Contexto;
use App\Video\Plano\Linha;
use App\Video\Plano\PilotoAutomatico;
use App\Video\Plano\PlanoInvalido;
use App\Video\Plano\Validador;
use App\Video\Render\Montador;
use Illuminate\Support\Facades\DB;

/**
 * O fluxo de uma edição, etapa por etapa (o /editar do pacote de skills):
 *
 *   preparar → transcrever → decidir (Claude ou piloto) → prévia
 *   ajustar (pedido em texto → Claude edita o plano) → prévia nova
 *   finalizar (originais em resolução cheia + capa)
 *
 * Cada etapa grava o resultado em disco; uma nova tentativa depois de uma
 * queda continua de onde parou (o que já existe é reaproveitado).
 */
class Edicao
{
    private Armazenamento $pasta;

    public function __construct(private VideoJob $job)
    {
        $this->pasta = $job->pasta();
    }

    /* Fluxo principal --------------------------------------------------- */

    public function processar(): void
    {
        $job = $this->job;
        $estilo = $job->estiloInfo();

        $this->progresso(2, 'Preparando o material', 'preparar');
        $inventario = (new Preparador($job))->executar(fn ($f, $msg) => $this->progresso(2 + (int) ($f * 26), $msg));
        $this->avisarMaterial($inventario);
        $inventario = $this->mapearRostos($inventario);

        $palavras = [];
        if ($estilo['fala']) {
            $this->progresso(30, 'Transcrevendo a fala', 'transcrever');
            $palavras = $this->transcrever($inventario);
        }

        $this->progresso(42, ClaudeCli::ativo() ? 'O Claude está assistindo ao material e decidindo a edição' : 'Montando a edição automática', 'decidir');
        $plano = $this->decidir($inventario, $palavras);

        $this->progresso(70, 'Renderizando a prévia', 'previa');
        $this->renderizarPrevia($plano, $palavras, 70, 100);
    }

    /** Pedido em texto da equipe ("em 0:12 tira essa frase") → Claude edita o plano. */
    public function ajustar(string $pedido): void
    {
        $job = $this->job;

        if (! ClaudeCli::ativo()) {
            throw new FalhaDeFerramenta('Ajuste por texto precisa do Claude configurado no servidor. Use a edição manual do plano.');
        }

        $inventario = $this->inventario();
        $palavras = $this->palavras();
        $linha = new Linha($job->plano['segmentos']);
        $frases = is_file($this->pasta->absoluto('trabalho/frases.txt')) ? file_get_contents($this->pasta->absoluto('trabalho/frases.txt')) : null;

        $this->progresso(10, 'O Claude está aplicando o ajuste', 'ajustar');
        $contexto = new Contexto($job);
        $prompt = $contexto->paraAjuste($pedido, $job->plano, $linha, $palavras, $inventario, $this->musica(), $frases);

        $plano = $this->rodarClaude($contexto, $prompt, $inventario, $palavras);

        foreach ($plano['aprendizados'] ?? [] as $texto) {
            VideoLearning::create([
                'company_id' => $job->company_id, 'client_id' => $job->client_id, 'video_job_id' => $job->id,
                'texto' => $texto, 'etapa' => 'ajuste', 'status' => 'pendente',
            ]);
        }

        $this->progresso(55, 'Renderizando a nova prévia', 'previa');
        $this->renderizarPrevia($plano, $palavras, 55, 100);
    }

    /** Depois de uma edição manual do plano (sem Claude). */
    public function rerenderizar(array $planoBruto): void
    {
        $plano = $this->validar($planoBruto, $this->inventario());
        $this->salvarPlano($plano, 'manual');
        $this->progresso(10, 'Renderizando a nova prévia', 'previa');
        $this->renderizarPrevia($plano, $this->palavras(), 10, 100);
    }

    /** Vídeo final: originais em resolução cheia, capa e legenda do post. */
    public function finalizar(): void
    {
        $job = $this->job;
        $plano = $job->plano;
        [$versao, $saida] = $this->pasta->proximaVersao('final', 'mp4', $job->final_versao);

        $this->progresso(3, 'Gerando o vídeo final em alta', 'final');
        $montador = $this->montador($plano, 'final');
        $temp = $saida.'.parcial.mp4';
        $resultado = $montador->renderizar($temp, fn ($f) => $this->progresso(3 + (int) ($f * 92), null));
        $this->conferir($temp, $resultado['duracao']);
        rename($temp, $saida);

        $capa = null;
        if (! empty($plano['capa'])) {
            $this->progresso(97, 'Gerando a capa');
            try {
                $capa = sprintf('%s/capa_v%02d.png', $this->pasta->dir('renders'), $versao);
                $montador->capa($plano['capa'], $capa);
            } catch (\Throwable) {
                $capa = null;
            }
        }

        $job->forceFill([
            // Versão nova: a do Drive (se houver) fica lá como histórico; esta sobe de novo.
            'nuvem_disco' => null, 'nuvem_final' => null, 'nuvem_capa' => null, 'nuvem_tamanho' => null,
            'nuvem_status' => null, 'nuvem_erro' => null, 'nuvem_em' => null,
            'final_versao' => $versao,
            'final_path' => $this->pasta->relativo($saida),
            'capa_path' => $capa && is_file($capa) ? $this->pasta->relativo($capa) : $job->capa_path,
        ])->save();

        $job->registrar('versao', "Vídeo final v{$versao} pronto (".Linha::formatar($resultado['duracao']).', '.round(filesize($saida) / 1048576, 1).' MB).', ['final' => $versao]);

        // As cenas que chegaram ao final viram repertório do cliente (família visual nos próximos vídeos).
        rescue(fn () => Repertorio::guardar($job, $plano), null, false);
    }

    /* Etapas ------------------------------------------------------------ */

    private function transcrever(array $inventario): array
    {
        $arquivo = $this->pasta->absoluto('trabalho/transcricao.json');

        if (is_file($arquivo)) {
            return $this->palavras();
        }

        if (! Transcricao::disponivel()) {
            $this->job->registrar('sistema', 'Transcrição não configurada no servidor: o vídeo sai sem legenda e os cortes são feitos pelos silêncios.');

            return [];
        }

        $contexto = $this->termosDoBriefing();
        $porArquivo = [];
        $comFala = array_filter($inventario, fn ($i) => $i['tem_audio'] && $i['tipo'] === 'video');
        $n = 0;

        foreach ($comFala as $chave => $info) {
            $this->progresso(30 + (int) (10 * $n++ / max(1, count($comFala))), "Transcrevendo {$info['nome']}");
            DB::disconnect();
            try {
                $porArquivo[$chave] = Transcricao::transcrever($this->pasta->absoluto("trabalho/audio_{$chave}.wav"), $contexto, (float) $info['duracao']);
            } catch (FalhaDeFerramenta $e) {
                // Sem legenda não é motivo para perder a edição: segue pelos silêncios.
                // Nada é gravado, então "Tentar de novo" tenta transcrever outra vez.
                $this->job->registrar('erro', 'A transcrição falhou e o vídeo vai sair sem legenda automática: '.$e->getMessage());

                return [];
            }
        }

        file_put_contents($arquivo, json_encode(['driver' => Transcricao::driver(), 'arquivos' => array_map(fn ($p) => ['palavras' => $p], $porArquivo)], JSON_UNESCAPED_UNICODE));
        file_put_contents($this->pasta->absoluto('trabalho/frases.txt'), Transcricao::frases($porArquivo, $inventario));

        $total = array_sum(array_map('count', $porArquivo));
        $this->job->registrar('sistema', "Transcrição pronta: {$total} palavras.");

        return $porArquivo;
    }

    private function decidir(array $inventario, array $palavras): array
    {
        $job = $this->job;
        $musica = $this->musica();

        // O motor é o Claude. O piloto automático (regras) só entra se a agência
        // ligar VIDEO_PILOTO_RESERVA — senão, sem Claude a edição para com erro claro.
        $reserva = (bool) config('video.claude.piloto_reserva', false);

        if (! ClaudeCli::ativo() && ! $reserva) {
            throw new FalhaDeFerramenta('O Claude não está conectado no servidor. Rode scripts/video/logar-claude.sh por SSH (veja leia/estudio-de-video.md).');
        }

        if (ClaudeCli::ativo()) {
            $frases = $palavras && is_file($this->pasta->absoluto('trabalho/frases.txt')) ? file_get_contents($this->pasta->absoluto('trabalho/frases.txt')) : null;
            $contexto = new Contexto($job);
            $prompt = $contexto->paraEdicao($inventario, $frases, $musica);

            try {
                return $this->rodarClaude($contexto, $prompt, $inventario, $palavras, true);
            } catch (Claude\LimiteDoClaude $e) {
                throw $e;
            } catch (FalhaDeFerramenta|PlanoInvalido $e) {
                if (! $reserva) {
                    throw $e;
                }
                $job->registrar('erro', 'O Claude não conseguiu entregar um plano válido ('.$e->getMessage().'). Usei a edição automática; peça ajustes depois.');
            }
        }

        $plano = $this->validar((new PilotoAutomatico($job))->gerar($inventario, $palavras, $musica), $inventario);
        $this->salvarPlano($plano, 'piloto');
        $job->forceFill(['feito_por_claude' => false])->save();
        $job->registrar('claude', $plano['resumo']);

        return $plano;
    }

    /** Roda o Claude, lê o plano, valida; se inválido, devolve os problemas para ele corrigir uma vez. */
    private function rodarClaude(Contexto $contexto, string $prompt, array $inventario, array $palavras, bool $primeira = false): array
    {
        $job = $this->job;
        DB::disconnect();
        $r = ClaudeCli::executar($contexto->pasta(), $prompt, null, $this->longo());
        $this->registrarUso($r);

        for ($tentativa = 0; ; $tentativa++) {
            $arquivo = $contexto->pasta().'/plano.json';
            $bruto = is_file($arquivo) ? json_decode((string) file_get_contents($arquivo), true) : null;

            try {
                if (! is_array($bruto)) {
                    throw new PlanoInvalido([is_file($arquivo) ? 'plano.json não é um JSON válido ('.json_last_error_msg().').' : 'plano.json não foi criado.']);
                }

                $plano = $this->validar($bruto, $inventario);

                // Narração sem legenda perde quem assiste sem som (a maioria): liga,
                // a não ser que a equipe tenha desligado a legenda. A aba Plano ainda pode tirar.
                if ($plano['narracao']['ativo'] && $job->opcao('legendas', true)) {
                    $plano['legendas']['ativo'] = true;
                }
                break;
            } catch (PlanoInvalido $e) {
                if ($tentativa >= 1 || ! $r['sessao']) {
                    throw $e;
                }
                $this->progresso($primeira ? 60 : 40, 'O Claude está corrigindo o plano');
                DB::disconnect();
                $r = ClaudeCli::executar($contexto->pasta(), "O sistema recusou o plano.json com estes problemas:\n- ".implode("\n- ", $e->problemas)."\nCorrija o plano.json e termine dizendo o que mudou.", $r['sessao'], $this->longo());
                $this->registrarUso($r);
            }
        }

        if ($primeira && config('video.claude.revisao_visual', true) && $r['sessao']) {
            [$plano, $r] = $this->revisaoVisual($contexto, $plano, $r, $inventario);
        }

        $this->salvarPlano($plano, 'claude');
        $job->forceFill(['feito_por_claude' => true, 'claude_sessao' => $r['sessao']])->save();
        $job->registrar('claude', $r['texto'] ?: ($plano['resumo'] ?: 'Plano pronto.'), array_filter([
            'avisos' => $plano['avisos'] ?: null,
            'correcoes' => $plano['correcoes_automaticas'] ?: null,
            'turnos' => $r['turnos'],
        ]));

        return $plano;
    }

    private function renderizarPrevia(array $plano, array $palavras, int $de, int $ate): void
    {
        $job = $this->job;
        [$versao, $saida] = $this->pasta->proximaVersao('previa', 'mp4', $job->previa_versao);
        DB::disconnect();

        $temp = $saida.'.parcial.mp4';
        $r = $this->montador($plano, 'previa', $palavras)->renderizar($temp, fn ($f) => $this->progresso($de + (int) ($f * ($ate - $de - 1)), null));
        $this->conferir($temp, $r['duracao']);
        rename($temp, $saida);

        $job->forceFill([
            'previa_versao' => $versao,
            'previa_path' => $this->pasta->relativo($saida),
            'legenda_post' => $plano['legenda_post'] ?: $job->legenda_post,
        ])->save();

        file_put_contents($this->pasta->dir('renders').sprintf('/previa_v%02d.json', $versao), json_encode(['linha' => $r['linha'], 'legendas' => $r['legendas']], JSON_UNESCAPED_UNICODE));

        $job->registrar('versao', "Prévia v{$versao} pronta (".Linha::formatar($r['duracao']).').', array_filter([
            'previa' => $versao,
            'avisos' => $r['avisos'] ?: null,
        ]));
    }

    /**
     * O Claude olha o que fez: folhas com entrada/auge/saída de cada animação
     * sobre o quadro real do vídeo (rosto em verde, zonas do app em vermelho)
     * e corrige o plano. Se a correção sair inválida, fica o plano anterior.
     */
    private function revisaoVisual(Contexto $contexto, array $plano, array $r, array $inventario): array
    {
        $job = $this->job;
        [$id, $fontes, $logo] = $this->identidadeEFontes();
        $folhas = Revisao::folhas($job, Logos::resolverPlano($plano, (int) $job->company_id, $job->client_id), $contexto->pasta(), $id, $fontes, $logo);
        if (! $folhas) {
            return [$plano, $r];
        }

        $this->progresso(62, 'O Claude está revisando as animações (olhando os quadros)');
        DB::disconnect();
        $prompt = "Revisão visual. Renderizei as suas animações sobre o quadro real do vídeo. Abra cada imagem com Read:\n- ".implode("\n- ", $folhas)
            ."\n\nCada folha mostra a entrada, o auge e a saída. Vermelho = zonas que o app cobre (topo, base e direita); verde = o rosto."
            ."\nCorrija no plano.json, sem medo de refazer uma cena inteira:\n"
            ."- texto ilegível, pequeno demais ou cortado; elemento saindo do quadro ou dentro do vermelho;\n"
            ."- animação cobrindo o rosto (mova, encolha, ou vire tela cheia de propósito);\n"
            ."- desalinhado, amontoado, duas protagonistas brigando, contraste fraco sobre a imagem;\n"
            ."- sem graça ou genérica: o conceito tem que nascer desta fala (refaça a cena).\n"
            ."Se estiver tudo bom, não mexa. Termine com 1 a 3 frases dizendo o que mudou.";

        try {
            $r2 = ClaudeCli::executar($contexto->pasta(), $prompt, $r['sessao'], $this->longo());
            $this->registrarUso($r2);
            $bruto = json_decode((string) @file_get_contents($contexto->pasta().'/plano.json'), true);
            $novo = is_array($bruto) ? $this->validar($bruto, $inventario) : null;
        } catch (Claude\LimiteDoClaude $e) {
            throw $e;
        } catch (\Throwable $e) {
            $novo = null;
        }

        if (! $novo) {
            $job->registrar('sistema', 'A revisão visual do Claude não gerou um plano válido; fica o plano anterior.');

            return [$plano, $r];
        }

        if (($plano['narracao']['ativo'] ?? false) && $job->opcao('legendas', true)) {
            $novo['legendas']['ativo'] = true;
        }
        $r2['texto'] = trim(($r['texto'] ?? '')."\n\nRevisão visual: ".($r2['texto'] ?? ''));
        $r2['turnos'] = ($r['turnos'] ?? 0) + ($r2['turnos'] ?? 0);

        return [$novo, $r2 + $r];
    }

    /** Onde está o rosto em cada arquivo de vídeo (YuNet), guardado no inventário. */
    private function mapearRostos(array $inventario): array
    {
        if (! Render\Motion::rostoDisponivel()) {
            return $inventario;
        }
        $mudou = false;
        foreach ($inventario as $chave => $info) {
            if (($info['tipo'] ?? '') !== 'video' || isset($info['rosto'])) {
                continue;
            }
            $this->progresso($this->job->progresso, "Encontrando o rosto em {$info['nome']}");
            DB::disconnect();
            $mapa = Revisao::mapearRosto($this->pasta, $chave);
            $inventario[$chave]['rosto'] = $mapa ? Revisao::resumoRosto($mapa) : 'não detectado';
            $mudou = true;
        }
        if ($mudou) {
            file_put_contents($this->pasta->absoluto('trabalho/inventario.json'), json_encode($inventario, JSON_UNESCAPED_UNICODE));
        }

        return $inventario;
    }

    /** Plano sem foco de câmera (o padrão do centro): centraliza no rosto do meio do trecho. */
    private function focoNoRosto(array $segmentos): array
    {
        $mapas = [];
        foreach ($segmentos as &$s) {
            if (($s['tipo'] ?? '') !== 'clip' || ($s['camera']['foco'] ?? [0.5, 0.5]) != [0.5, 0.5]) {
                continue;
            }
            $arq = $this->pasta->absoluto("trabalho/rosto_{$s['arquivo']}.json");
            $mapas[$s['arquivo']] ??= is_file($arq) ? json_decode((string) file_get_contents($arq), true) : null;
            $r = Revisao::rostoEm($mapas[$s['arquivo']], ((float) $s['inicio'] + (float) $s['fim']) / 2);
            if ($r) {
                // Centro do rosto, puxado para os olhos (~42% da caixa do rosto).
                $s['camera']['foco'] = [round($r[0] + $r[2] / 2, 3), round($r[1] + $r[3] * 0.42, 3)];
            }
        }
        unset($s);

        return $segmentos;
    }

    /** Identidade (com os ajustes desta edição), fontes e logo: o que o motor precisa. */
    private function identidadeEFontes(): array
    {
        $job = $this->job;
        $perfil = VideoProfile::doCliente($job->client);
        $pastaFontes = rtrim(str_replace('\\', '/', (string) config('video.ferramentas.fontes')), '/');
        $fontes = glob("$pastaFontes/*.ttf") ?: [];
        if ($custom = $perfil->caminhoFonte()) {
            $fontes[] = $custom;
        }
        $logo = $this->pasta->absoluto('trabalho/logo.png');

        return [Identidade::daEdicao($perfil, (array) $job->opcao('identidade', [])), $fontes, $job->opcao('logo', true) && is_file($logo) ? $logo : null];
    }

    /* Peças compartilhadas ---------------------------------------------- */

    public function validar(array $bruto, array $inventario): array
    {
        $job = $this->job;
        $estilo = $job->estiloInfo();
        $musica = $this->musica();

        $arquivos = array_map(fn ($i) => [
            'tipo' => $i['tipo'], 'duracao' => $i['duracao'], 'tem_audio' => $i['tem_audio'],
            'papel' => $i['papel'], 'largura' => $i['largura'], 'altura' => $i['altura'],
        ], $inventario);

        $plano = (new Validador)->normalizar($bruto, $arquivos, [
            'estilo' => $job->estilo,
            'fala' => $estilo['fala'],
            'tem_musica' => (bool) $musica,
            'narracao' => $this->comNarracao(),
            'duracao_max' => max(60, $estilo['duracao'][1] * 1.6),
        ]);

        // Narração desligada nesta edição (ou sem voz instalada): as falas ficam
        // guardadas no plano, mas não vão para o vídeo.
        if (! $this->comNarracao()) {
            $plano['narracao']['ativo'] = false;
        }

        if (! $job->opcao('cartao_final', true)) {
            $plano['segmentos'] = array_values(array_filter($plano['segmentos'], fn ($s) => $s['id'] !== 'fim'));
            $plano['cartao_final']['ativo'] = false;
        }

        if (! $job->opcao('legendas', true)) {
            $plano['legendas']['ativo'] = false;
        }

        $plano['segmentos'] = $this->focoNoRosto($plano['segmentos']);

        // Cortes na batida (montagem sem fala).
        if ($plano['musica']['usar'] && $plano['musica']['sincronizar_cortes'] && ! empty($musica['batidas'])) {
            $batidas = array_values(array_filter(array_map(fn ($b) => round($b - $plano['musica']['inicio'], 3), $musica['batidas']), fn ($b) => $b > 0));
            $plano['segmentos'] = Batidas::sincronizar($plano['segmentos'], $batidas, array_map(fn ($i) => $i['duracao'], $inventario));
        }

        return $plano;
    }

    private function salvarPlano(array $plano, string $origem): void
    {
        $versao = $this->job->plano_versao + 1;
        file_put_contents($this->pasta->dir('claude').sprintf('/plano_v%02d.json', $versao), json_encode($plano, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->job->forceFill(['plano' => $plano, 'plano_versao' => $versao])->save();
    }

    private function montador(array $plano, string $modo, ?array $palavras = null): Montador
    {
        $job = $this->job;
        $dim = $job->dimensoes();
        $perfil = VideoProfile::doCliente($job->client);
        $logo = $this->pasta->absoluto('trabalho/logo.png');
        $fontes = [];
        foreach ($job->arquivos()->where('status', 'pronto')->get() as $arq) {
            $chave = $arq->chave();
            $foto = $this->pasta->absoluto("trabalho/foto_{$chave}.png");
            $fontes[$chave] = [
                'original' => $arq->tipo === 'imagem' && is_file($foto) ? $foto : $arq->caminhoAbsoluto(),
                'proxy' => $arq->tipo === 'imagem' ? null : $this->pasta->absoluto("trabalho/proxy_{$chave}.mp4"),
                'tipo' => $arq->tipo, 'hdr' => $arq->hdr, 'largura' => $arq->largura, 'altura' => $arq->altura,
                'tem_audio' => $arq->tem_audio,
            ];
        }

        $pastaFontes = rtrim(str_replace('\\', '/', (string) config('video.ferramentas.fontes')), '/');
        $arquivosDeFonte = glob("$pastaFontes/*.ttf") ?: [];
        if ($custom = $perfil->caminhoFonte()) {
            $arquivosDeFonte[] = $custom;
        }

        $musica = $this->musica();

        // Logos dos componentes (apps, cards, notificação, marca): biblioteca → Simple Icons →
        // App Store → Wikipédia → site. Quem busca é o servidor; o Claude só escreve o nome.
        if (Render\Motion::disponivel()) {
            $mensagemAntes = $job->mensagem;
            $buscou = false;
            $plano = Logos::resolverPlano($plano, (int) $job->company_id, $job->client_id, function (string $nome) use (&$buscou) {
                $buscou = true;
                $this->progresso($this->job->progresso, "Buscando o logo de {$nome}");
            });
            if ($buscou) {
                $this->progresso($job->progresso, $mensagemAntes);
            }
        }

        // Voz gerada: só as falas novas ou mudadas são geradas (cache por texto).
        $narracao = ['falas' => [], 'avisos' => []];
        if (($plano['narracao']['ativo'] ?? false) && $this->comNarracao()) {
            DB::disconnect();
            $mensagemAntes = $job->mensagem;
            $gerou = false;
            $narracao = Narracao::preparar($plano, Narracao::vozDaEdicao($job), $this->pasta->dir('renders'), function (int $quantas) use (&$gerou) {
                $gerou = true;
                $this->progresso($this->job->progresso, $quantas > 1 ? "Gerando a narração ({$quantas} falas)" : 'Gerando a narração');
            });
            if ($gerou) {
                $this->progresso($job->progresso, $mensagemAntes); // volta para "Renderizando…"
            }
        }

        return new Montador(
            $plano, $fontes, Identidade::daEdicao($perfil, (array) $job->opcao('identidade', [])), $this->pasta->dir('renders'), $modo,
            $dim['largura'], $dim['altura'], $palavras ?? $this->palavras(),
            $musica ? ['caminho' => $musica['caminho'], 'duracao' => $musica['duracao']] : null,
            // "Sem logo" nesta edição: nem marca d'água nem logo no cartão final.
            $job->opcao('logo', true) && is_file($logo) ? $logo : null, $arquivosDeFonte, $job->estiloInfo()['fala'],
            $narracao['falas'], $narracao['avisos'],
        );
    }

    /** Estilo de vídeo longo (vlog): limites, prazos e montagem por blocos. */
    public function longo(): bool
    {
        return ! empty($this->job->estiloInfo()['longo']);
    }

    /** Narração ligada nesta edição e com alguma voz instalada no servidor. */
    public function comNarracao(): bool
    {
        return (bool) $this->job->opcao('narracao', false) && Narracao::disponivel();
    }

    /** Confere o arquivo gerado antes de entregar (render_conferir.py do pacote). */
    private function conferir(string $arquivo, float $esperado): void
    {
        if (! is_file($arquivo) || filesize($arquivo) < 10_000) {
            throw new FalhaDeFerramenta('O render terminou mas o arquivo saiu vazio.');
        }

        $info = Sondagem::arquivo($arquivo);

        if (abs($info['duracao'] - $esperado) > 0.6) {
            throw new FalhaDeFerramenta(sprintf('O vídeo saiu com %.1f s, mas o plano tem %.1f s. Algo deu errado no render.', $info['duracao'], $esperado));
        }
    }

    public function inventario(): array
    {
        $arquivo = $this->pasta->absoluto('trabalho/inventario.json');

        if (! is_file($arquivo)) {
            return (new Preparador($this->job))->executar(fn () => null);
        }

        return json_decode((string) file_get_contents($arquivo), true) ?: [];
    }

    public function palavras(): array
    {
        $arquivo = $this->pasta->absoluto('trabalho/transcricao.json');
        $dados = is_file($arquivo) ? json_decode((string) file_get_contents($arquivo), true) : null;

        return array_map(fn ($a) => $a['palavras'] ?? [], $dados['arquivos'] ?? []);
    }

    public function musica(): ?array
    {
        $m = $this->job->musica;

        if (! $m || ! is_file($m->caminhoAbsoluto())) {
            return null;
        }

        return [
            'nome' => $m->nome, 'caminho' => $m->caminhoAbsoluto(), 'duracao' => $m->duracao,
            'bpm' => $m->bpm, 'batidas' => $m->batidas ?? [], 'humor' => $m->humor,
        ];
    }

    /** Nomes e termos do briefing + nome do cliente: ajudam a transcrição a acertar a grafia. */
    private function termosDoBriefing(): string
    {
        $perfil = VideoProfile::doCliente($this->job->client);

        return trim(implode('. ', array_filter([
            $this->job->client->name,
            $perfil->instagram,
            mb_substr((string) $this->job->briefing, 0, 400),
        ])));
    }

    private function avisarMaterial(array $inventario): void
    {
        $avisos = [];

        foreach ($inventario as $chave => $i) {
            if ($i['hdr']) {
                $avisos[] = "{$i['nome']}: vídeo HDR (iPhone) — convertido para cor normal.";
            }
            if (isset($i['nivel_audio']) && $i['nivel_audio'] < -40 && $this->job->estiloInfo()['fala']) {
                $avisos[] = "{$i['nome']}: áudio muito baixo ({$i['nivel_audio']} dB).";
            }
        }

        if ($avisos) {
            $this->job->registrar('sistema', implode(' ', array_slice($avisos, 0, 6)));
        }
    }

    private function registrarUso(array $r): void
    {
        $uso = $this->job->claude_uso ?? [];
        $uso['chamadas'] = ($uso['chamadas'] ?? 0) + 1;
        $uso['turnos'] = ($uso['turnos'] ?? 0) + (int) ($r['turnos'] ?? 0);
        $uso['segundos'] = round(($uso['segundos'] ?? 0) + ($r['duracao_ms'] ?? 0) / 1000);
        $this->job->forceFill(['claude_uso' => $uso])->saveQuietly();
    }

    private function progresso(int $percentual, ?string $mensagem, ?string $etapa = null): void
    {
        $this->job->progresso($percentual, $mensagem, $etapa);
    }
}
