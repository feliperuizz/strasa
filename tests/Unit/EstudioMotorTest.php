<?php

namespace Tests\Unit;

use App\Http\Controllers\Estudio\PerfilVideoController;
use App\Video\Batidas;
use App\Video\Narracao;
use App\Video\Plano\Linha;
use App\Video\Plano\PlanoInvalido;
use App\Video\Plano\Validador;
use App\Video\Render\Ass;
use App\Video\Render\Legendas;
use App\Video\Render\Motion;
use App\Video\Transcricao;
use PHPUnit\Framework\TestCase;

/**
 * Lógica pura do Estúdio de vídeo (sem FFmpeg, sem banco): o que decide
 * tempo, corte e texto precisa estar certo antes de qualquer render.
 */
class EstudioMotorTest extends TestCase
{
    private const ARQUIVOS = [
        'f1' => ['tipo' => 'video', 'duracao' => 30.0, 'tem_audio' => true, 'papel' => 'bruto', 'largura' => 1080, 'altura' => 1920],
        'f2' => ['tipo' => 'video', 'duracao' => 8.0, 'tem_audio' => true, 'papel' => 'bruto', 'largura' => 1920, 'altura' => 1080],
        'f3' => ['tipo' => 'imagem', 'duracao' => 0.0, 'tem_audio' => false, 'papel' => 'bruto', 'largura' => 1200, 'altura' => 1200],
    ];

    public function test_linha_converte_tempo_de_origem_e_volta_com_transicao_e_velocidade(): void
    {
        $linha = new Linha([
            ['id' => 's1', 'tipo' => 'clip', 'arquivo' => 'f1', 'inicio' => 2.0, 'fim' => 5.0, 'velocidade' => 1.0, 'transicao' => 'corte'],
            ['id' => 's2', 'tipo' => 'clip', 'arquivo' => 'f2', 'inicio' => 0.0, 'fim' => 2.0, 'velocidade' => 0.5, 'transicao' => 'fade', 'transicao_duracao' => 0.5],
        ]);

        // s2 dura 4 s (câmera lenta) e começa 0,5 s antes do fim de s1 (sobreposição do fade).
        $this->assertEqualsWithDelta(2.5, $linha->itens[1]['seq_inicio'], 0.001);
        $this->assertEqualsWithDelta(6.5, $linha->total, 0.001);
        $this->assertEqualsWithDelta(1.0, $linha->paraSequencia('f1', 3.0), 0.001);
        $this->assertEqualsWithDelta(4.5, $linha->paraSequencia('f2', 1.0), 0.001);
        $this->assertNull($linha->paraSequencia('f1', 10.0), 'trecho cortado não existe na sequência');
        $this->assertSame(['arquivo' => 'f2', 't' => 1.0, 'segmento' => 's2'], $linha->paraOrigem(4.5));
        $this->assertEqualsWithDelta(3.0, $linha->resolver(['segmento' => 's2', 't' => 0.5]), 0.001);
    }

    public function test_validador_corrige_o_que_da_e_anota(): void
    {
        $plano = (new Validador)->normalizar([
            'segmentos' => [
                ['arquivo' => 'f1', 'inicio' => -1, 'fim' => 40, 'camera' => ['zoom' => [0.5, 9], 'foco' => [2, -1]], 'transicao' => 'explosao'],
                ['arquivo' => 'f2', 'inicio' => 1, 'fim' => 1.1],
                ['arquivo' => 'f3', 'duracao' => 99],
                ['arquivo' => 'f2', 'inicio' => 0, 'fim' => 3, 'velocidade' => 2, 'audio' => true],
            ],
            'elementos' => [
                ['tipo' => 'titulo', 'texto' => 'Olá {\\pos(0,0)}', 'em' => ['arquivo' => 'f1', 't' => 1]],
                ['tipo' => 'titulo', 'texto' => 'Cortado', 'em' => ['arquivo' => 'f2', 't' => 7]],
                ['tipo' => 'inventado', 'em' => 1],
            ],
            'cartao_final' => ['ativo' => true],
        ], self::ARQUIVOS, ['fala' => true]);

        $s = $plano['segmentos'];
        $this->assertSame([0.0, 30.0], [$s[0]['inicio'], $s[0]['fim']]);
        $this->assertSame([1.0, 1.8], $s[0]['camera']['zoom']);
        $this->assertSame([1.0, 0.0], $s[0]['camera']['foco']);
        $this->assertSame('corte', $s[0]['transicao']);
        $this->assertSame('foto', $s[1]['tipo'], 'o segmento curto demais (0,1 s) foi removido');
        $this->assertSame(10.0, $s[1]['duracao']);
        $this->assertFalse($s[2]['audio'], 'som original com velocidade 2x é desligado');
        $this->assertSame(2.0, $s[2]['velocidade'], 'array_merge, não +: a velocidade pedida vale');
        $this->assertSame('fim', end($s)['id']);
        $this->assertCount(1, $plano['elementos']);
        $this->assertNotEmpty($plano['correcoes_automaticas']);
    }

    public function test_validador_recusa_plano_sem_segmento_valido_com_mensagem_para_o_claude(): void
    {
        try {
            (new Validador)->normalizar(['segmentos' => [['arquivo' => 'f9', 'inicio' => 0, 'fim' => 2]]], self::ARQUIVOS);
            $this->fail('deveria recusar');
        } catch (PlanoInvalido $e) {
            $this->assertStringContainsString('f9', implode(' ', $e->problemas));
            $this->assertStringContainsString('f1', implode(' ', $e->problemas), 'lista as chaves válidas');
        }
    }

    public function test_montagem_sem_fala_desliga_som_original_por_padrao(): void
    {
        $plano = (new Validador)->normalizar(['segmentos' => [['arquivo' => 'f2', 'inicio' => 0, 'fim' => 3]]], self::ARQUIVOS, ['fala' => false]);
        $this->assertFalse($plano['segmentos'][0]['audio']);
    }

    public function test_legendas_agrupam_sem_pendurar_artigo_e_tiram_hesitacao(): void
    {
        $palavras = [];
        $t = 0.0;
        foreach (['Hoje', 'hum', 'eu', 'vou', 'mostrar', 'o', 'corte', 'de', 'três', 'passos.'] as $w) {
            $palavras[] = ['w' => $w, 'i' => $t, 'f' => $t + 0.3];
            $t += 0.35;
        }

        $limpas = Legendas::preparar($palavras, [], true, []);
        $grupos = Legendas::agrupar($limpas, Legendas::LIMITES['karaoke']);
        $textos = array_map(fn ($g) => implode(' ', array_column($g['palavras'], 'texto')), $grupos);

        $this->assertNotContains('HUM', array_merge(...array_map(fn ($g) => array_column($g['palavras'], 'texto'), $grupos)));
        foreach ($textos as $texto) {
            $this->assertDoesNotMatchRegularExpression('/ (O|DE)$/', $texto, "grupo terminando em artigo: $texto");
        }
        $this->assertStringContainsString('3', implode(' ', $textos), 'número falado vira algarismo');
    }

    public function test_correcao_de_varias_palavras_vira_uma(): void
    {
        $palavras = [
            ['w' => 'com', 'i' => 0.0, 'f' => 0.2], ['w' => 'na', 'i' => 0.3, 'f' => 0.4], ['w' => 'vália.', 'i' => 0.45, 'f' => 0.9],
            ['w' => 'A', 'i' => 1.5, 'f' => 1.6], ['w' => 'gente', 'i' => 1.6, 'f' => 1.9],
        ];
        $limpas = Legendas::preparar($palavras, ['na vália' => 'navalha', 'A gente' => 'Agende'], false, []);

        $this->assertSame(['com', 'navalha', 'Agende'], array_column($limpas, 'texto'));
        $this->assertSame(0.3, $limpas[1]['i']);
        $this->assertSame(0.9, $limpas[1]['f']);
        $this->assertTrue($limpas[1]['fim_frase'], 'a pontuação da última parte é preservada');
    }

    public function test_ass_escapa_texto_de_fora_e_converte_cor(): void
    {
        // Fala transcrita ou texto do Claude nunca abre tag de override do ASS.
        $this->assertSame('(∖pos(0,0))x', Ass::escapar('{\\pos(0,0)}x'));
        $this->assertSame('linha 1\\Nlinha 2', Ass::escapar("linha 1\nlinha 2"));
        $this->assertSame('&H00332211', Ass::cor('#112233'));
        $this->assertSame('0:01:02.50', Ass::tempo(62.5));
    }

    public function test_desenho_normalizado_comeca_em_zero(): void
    {
        // O libass alinha desenho pela origem: coordenada negativa deslocava as formas.
        foreach (['seta', 'estrela', 'circulo', 'balao', 'fogo'] as $forma) {
            preg_match_all('/-?\d+(?:\.\d+)?/', Ass::forma($forma), $m);
            $numeros = array_map('floatval', $m[0]);
            $this->assertGreaterThanOrEqual(0, min($numeros), "forma $forma tem coordenada negativa");
        }
    }

    public function test_batidas_acha_120_bpm_num_clique_sintetico(): void
    {
        $taxa = 11025;
        $amostras = '';
        for ($i = 0; $i < $taxa * 20; $i++) {
            $t = $i / $taxa;
            $fase = fmod($t, 0.5);
            $v = $fase < 0.03 ? sin(2 * M_PI * 80 * $t) * exp(-$fase * 60) * 20000 : 0;
            $amostras .= pack('v', (int) $v & 0xFFFF);
        }

        $r = Batidas::deAmostras($amostras);
        $this->assertEqualsWithDelta(120, $r['bpm'], 1.0);
        $this->assertEqualsWithDelta(0.0, fmod($r['batidas'][10] + 0.25, 0.5) - 0.25, 0.04, 'batida alinhada à grade de 0,5 s');
    }

    public function test_sincronizar_leva_corte_para_a_batida_sem_mexer_em_fala(): void
    {
        $segmentos = [
            ['id' => 's1', 'tipo' => 'clip', 'arquivo' => 'f1', 'inicio' => 0.0, 'fim' => 1.8, 'velocidade' => 1.0, 'audio' => false, 'transicao' => 'corte'],
            ['id' => 's2', 'tipo' => 'clip', 'arquivo' => 'f1', 'inicio' => 5.0, 'fim' => 7.1, 'velocidade' => 1.0, 'audio' => true, 'transicao' => 'corte'],
            ['id' => 's3', 'tipo' => 'clip', 'arquivo' => 'f1', 'inicio' => 9.0, 'fim' => 11.0, 'velocidade' => 1.0, 'audio' => false, 'transicao' => 'corte'],
        ];
        $batidas = [0.5, 1.0, 1.5, 2.0, 2.5, 3.0, 3.5, 4.0, 4.5, 5.0];
        $saida = Batidas::sincronizar($segmentos, $batidas, ['f1' => 30.0]);

        $this->assertSame(2.0, $saida[0]['fim'], 'fim de s1 vai para a batida de 2,0 s');
        $this->assertSame(7.1, $saida[1]['fim'], 'segmento com fala não é mexido');
    }

    public function test_frases_marcam_pausa_e_nota_de_edicao(): void
    {
        $p = fn ($w, $i) => ['w' => $w, 'i' => $i, 'f' => $i + 0.3];
        $txt = Transcricao::frases(['f1' => [
            $p('Olá', 0.0), $p('pessoal.', 0.4),
            $p('nota', 2.0), $p('de', 2.35), $p('edição', 2.7), $p('corta', 3.05), $p('isso.', 3.4),
            $p('fim', 4.0), $p('da', 4.35), $p('nota.', 4.7),
        ]], ['f1' => ['nome' => 'a.mov', 'duracao' => 6]]);

        $this->assertStringContainsString('pausa 1.3 s', $txt);
        $this->assertStringContainsString('⚑NOTA DE EDIÇÃO', $txt);
        $this->assertStringContainsString('[f1 0.00–0.70] Olá pessoal.', $txt);
    }

    public function test_distribuir_espalha_palavras_no_trecho(): void
    {
        $palavras = Transcricao::distribuir('um dois três', 1.0, 4.0);
        $this->assertCount(3, $palavras);
        $this->assertSame(1.0, $palavras[0]['i']);
        $this->assertLessThanOrEqual(4.0, end($palavras)['f']);
    }

    public function test_le_o_nome_da_familia_de_uma_fonte(): void
    {
        $fonte = dirname(__DIR__, 2).'/storage/app/video-fontes/sora-700.ttf';
        if (! is_file($fonte)) {
            $this->markTestSkipped('Fontes não instaladas (php artisan video:instalar).');
        }

        $this->assertSame('Sora', PerfilVideoController::familiaDaFonte($fonte));
    }

    public function test_narracao_le_simbolos_como_se_fala(): void
    {
        $this->assertSame('Por 39 reais e 90 centavos, apartamento de 80 metros quadrados com 20 por cento de entrada.',
            Narracao::textoFalado('Por R$ 39,90, apartamento de 80m² com 20% de entrada. 😍'));
        $this->assertSame('1200 reais à vista, das 9 às 18 horas, arroba barbeariadoze', Narracao::textoFalado('R$ 1.200 à vista, das 9 às 18h, @barbeariadoze'));
    }

    public function test_narracao_distribui_palavras_e_pausa_na_pontuacao(): void
    {
        $p = Narracao::palavrasNoTempo('Bem-vindo. Três quartos', 10.0, 2.0);

        $this->assertCount(3, $p);
        $this->assertEqualsWithDelta(10.0, $p[0]['i'], 0.001);
        $this->assertLessThan($p[1]['i'], $p[0]['f'], 'a pausa do ponto fica entre as palavras');
        $this->assertEqualsWithDelta(12.0, $p[2]['f'], 0.01);
    }

    public function test_validador_limpa_componentes_de_motion_e_resolve_cada_item_na_palavra(): void
    {
        $plano = (new Validador)->normalizar([
            'segmentos' => [['id' => 's1', 'arquivo' => 'f1', 'inicio' => 10, 'fim' => 20]],
            'elementos' => [
                ['tipo' => 'passos', 'em' => ['arquivo' => 'f1', 't' => 11], 'itens' => [
                    ['texto' => 'máquina', 'em' => ['arquivo' => 'f1', 't' => 12.5]], 'tesoura', ['texto' => ''],
                ], 'codigo' => ['/edit-cortes']],
                ['tipo' => 'apps', 'em' => ['arquivo' => 'f1', 't' => 14], 'acao' => 'explodir', 'itens' => [['logo' => 'Premiere', 'nome' => 'Premiere'], ['logo' => '../../etc/passwd']]],
                ['tipo' => 'janela', 'em' => ['arquivo' => 'f1', 't' => 10], 'linhas' => [['tipo' => 'comando', 'texto' => 'edita'], ['tipo' => 'xss', 'texto' => 'ok']]],
                ['tipo' => 'marca', 'em' => ['arquivo' => 'f1', 't' => 16], 'cor' => 'destaque'],
                ['tipo' => 'contador', 'em' => ['arquivo' => 'f1', 't' => 17], 'ate' => 20, 'sufixo' => '%'],
            ],
        ], self::ARQUIVOS);

        $porTipo = collect($plano['elementos'])->keyBy('tipo');
        $this->assertFalse($porTipo->has('marca'), 'marca sem logo nem sigla sai');
        $this->assertEqualsWithDelta(2.5, $porTipo['passos']['itens'][0]['t'], 0.001, 'item na palavra, no tempo do vídeo');
        $this->assertCount(2, $porTipo['passos']['itens']);
        $this->assertSame('topo', $porTipo['passos']['posicao']);
        $this->assertSame('nenhum', $porTipo['apps']['acao']);
        $this->assertSame('Premiere', $porTipo['apps']['itens'][0]['logo'], 'nome da marca como escrito: o servidor acha o logo');
        $this->assertArrayNotHasKey('logo', $porTipo['apps']['itens'][1], 'slug inválido não passa');
        $this->assertSame('texto', $porTipo['janela']['linhas'][1]['tipo']);
        $this->assertEqualsWithDelta(0.35, $porTipo['janela']['linhas'][0]['t'], 0.001, 'linha sem gatilho entra em sequência');
        $this->assertSame('centro', $porTipo['contador']['posicao']);
        $this->assertSame(2.8, $porTipo['contador']['duracao']);
        $this->assertNotEmpty(Motion::sons($porTipo['contador']));
    }

    public function test_cena_livre_limpa_camadas_e_converte_palavra_em_tempo_da_cena(): void
    {
        $plano = (new Validador)->normalizar([
            'segmentos' => [['id' => 's1', 'arquivo' => 'f1', 'inicio' => 10, 'fim' => 20]],
            'elementos' => [[
                'tipo' => 'cena', 'em' => ['arquivo' => 'f1', 't' => 12], 'duracao' => 4, 'formato' => 'tela_cheia',
                'conceito' => 'estrada que se desenha', 'fundo' => ['cores' => ['destaque', '#123456', 'javascript:x']],
                'camera' => ['zoom' => [9, 1], 'punches' => [['em' => ['arquivo' => 'f1', 't' => 13.5], 'forca' => 0.2]]],
                'camadas' => [
                    ['tipo' => 'texto', 'texto' => 'Do clique à venda', 'palavras' => [['arquivo' => 'f1', 't' => 12.5], 1.0], 'cor' => 'red', 'onclick' => 'x', 'fonte' => 'Comic'],
                    ['tipo' => 'caminho', 'd' => 'M0 0 L10 10 <script>'],
                    ['tipo' => 'grupo', 'filhos' => [['tipo' => 'icone', 'nome' => 'lucide:rocket'], ['tipo' => 'exec', 'cmd' => 'rm']]],
                    ['tipo' => 'linha', 'pontos' => [[0, 0], [100, 'x'], [200, 200]], 'entrada' => ['tipo' => 'desenhar', 'em' => ['arquivo' => 'f1', 't' => 14]]],
                ],
            ]],
        ], self::ARQUIVOS);

        $c = $plano['elementos'][0];
        $this->assertSame('cena', $c['tipo']);
        $this->assertTrue($c['oculta_legenda'], 'tela cheia esconde a legenda');
        $this->assertSame(['destaque', '#123456'], $c['fundo']['cores']);
        $this->assertSame(3.0, $c['camera']['zoom'][0]);
        $this->assertEqualsWithDelta(1.5, $c['camera']['punches'][0]['em'], 0.001, 'palavra em 13,5 s = 1,5 s depois do início da cena');
        $texto = $c['camadas'][0];
        $this->assertSame([0.5, 1.0], $texto['palavras']);
        $this->assertArrayNotHasKey('cor', $texto, 'cor inválida some');
        $this->assertArrayNotHasKey('onclick', $texto);
        $this->assertSame('forte', $texto['fonte']);
        $this->assertArrayNotHasKey('d', $c['camadas'][1], 'path SVG com lixo é recusado');
        $this->assertCount(1, $c['camadas'][2]['filhos'], 'tipo desconhecido não entra');
        $this->assertCount(2, $c['camadas'][3]['pontos']);
        $this->assertEqualsWithDelta(2.0, $c['camadas'][3]['entrada']['em'], 0.001);
        $this->assertNotEmpty(Motion::sons($c));
    }

    public function test_validador_guarda_falas_da_narracao_e_descarta_vazias(): void
    {
        $plano = (new Validador)->normalizar([
            'segmentos' => [['id' => 's1', 'arquivo' => 'f1', 'inicio' => 0, 'fim' => 6]],
            'narracao' => ['velocidade' => 3, 'falas' => [
                ['em' => ['segmento' => 's1', 't' => 0.2], 'texto' => 'Olá!'],
                ['em' => ['segmento' => 's9'], 'texto' => 'Segmento que não existe'],
                ['texto' => '   '],
            ]],
        ], self::ARQUIVOS, ['narracao' => true]);

        $n = $plano['narracao'];
        $this->assertTrue($n['ativo']);
        $this->assertSame(1.3, $n['velocidade']);
        $this->assertCount(2, $n['falas']);
        $this->assertSame('n02', $n['falas'][1]['id']);
        $this->assertSame($n['falas'][0]['em'], $n['falas'][1]['em'], 'âncora inválida entra junto da fala anterior');
        $this->assertTrue($plano['legendas']['ativo'], 'com narração, a legenda liga por padrão');
        $this->assertSame(0.18, $plano['musica']['volume'], 'música baixa sob a voz');
    }
}
