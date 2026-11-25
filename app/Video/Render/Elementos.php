<?php

namespace App\Video\Render;

/**
 * Animações por cima do vídeo (os "moNN" do plano), desenhadas em ASS.
 *
 * Ofício aplicado (edit-motion-render + estilo-motion do pacote):
 * - entradas rápidas (~0,25–0,4 s), saídas mais rápidas que as entradas;
 * - uma cor de destaque só: a palavra-chave acende, o resto fica na cor de texto;
 * - listas entram item a item, na palavra; nada importante fora da zona
 *   segura do Reels (8% de cima, 20% de baixo, 12% da direita);
 * - elemento parado parece morto: stickers têm "vida" (balanço leve) e a
 *   tela cheia tem câmera virtual (aproximação lenta).
 *
 * Cada elemento devolve as deixas de som (whoosh na entrada, pop no item…)
 * para a mixagem — o som segue o que se vê.
 */
class Elementos
{
    private const SOM_PADRAO = [
        'titulo' => 'whoosh', 'texto' => 'swoosh', 'palavra' => 'impacto', 'lista' => 'swoosh',
        'etiqueta' => 'pop', 'numero' => 'tique', 'preco' => 'ding', 'nome' => 'swoosh', 'local' => 'swoosh',
        'sticker' => 'pop', 'tela_cheia' => 'whoosh', 'flash' => 'impacto', 'citacao' => 'swoosh', 'estrelas' => 'pop',
    ];

    private array $sons = [];

    private float $k;

    public function __construct(private Ass $ass, private array $id)
    {
        $this->k = $ass->k();
        $f = $id['fonte_titulo'] ?? 'Montserrat';
        $t = $id['fonte_texto'] ?? $f;

        $ass->estilo('Titulo', $f, $ass->px(96), $id['texto'], '#000000', 5 * $this->k, 3 * $this->k, true);
        $ass->estilo('Texto', $t, $ass->px(60), $id['texto'], '#000000', 0, 0, true);
        $ass->estilo('Forma', $f, 20, $id['destaque'], '#000000', 0, 0, false, 7);
    }

    /** @return array deixas de som [['t' => s, 'som' => 'pop', 'forca' => 0..1], ...] */
    public function gerar(array $elementos): array
    {
        foreach ($elementos as $el) {
            $metodo = 'el'.str_replace('_', '', ucwords($el['tipo'], '_'));

            if (method_exists($this, $metodo)) {
                $this->{$metodo}($el);
            }

            if ($el['tipo'] !== 'lista' && $el['tipo'] !== 'estrelas') {
                $this->som($el['inicio'], $el['som'] ?? self::SOM_PADRAO[$el['tipo']] ?? null, $el['tipo'] === 'palavra' || $el['tipo'] === 'flash' ? 0.9 : 0.6);
            }
        }

        return $this->sons;
    }

    /** Intervalos em que a legenda deve sumir (o elemento já mostra texto na tela toda). */
    public static function ocultamLegenda(array $elementos): array
    {
        return array_values(array_map(
            fn ($e) => [$e['inicio'], $e['inicio'] + $e['duracao']],
            array_filter($elementos, fn ($e) => in_array($e['tipo'], ['tela_cheia', 'citacao', 'palavra'], true))
        ));
    }

    /* Elementos -------------------------------------------------------- */

    private function elTitulo(array $el): void
    {
        $tam = $this->ass->px(['p' => 76, 'm' => 96, 'g' => 124][$el['tamanho']]);
        [$x, $y, $an] = $this->ancora($el['posicao']);
        $linhas = Ass::quebrar($el['texto'], $tam, $this->ass->largura * 0.84);
        $texto = $this->comDestaque(implode('\\N', array_map([Ass::class, 'escapar'], $linhas)), $el['destaque']);

        if ($el['cor'] === 'primaria' || $el['cor'] === 'fundo') {
            $w = max(array_map(fn ($l) => Ass::larguraTexto($l, $tam), $linhas)) + 70 * $this->k;
            $h = count($linhas) * $tam * 1.18 + 40 * $this->k;
            $this->caixa($el, $x, $y, $an, $w, $h, $el['cor'] === 'primaria' ? $this->id['primaria'] : $this->id['fundo'], 0x10);
        }

        $this->texto($el, $x, $y, $an, $texto, 'Titulo', "\\fs$tam", 40);
    }

    private function elTexto(array $el): void
    {
        $tam = $this->ass->px(['p' => 46, 'm' => 60, 'g' => 76][$el['tamanho']]);
        [$x, $y, $an] = $this->ancora($el['posicao']);
        $linhas = Ass::quebrar($el['texto'], $tam, $this->ass->largura * 0.76);
        $w = max(array_map(fn ($l) => Ass::larguraTexto($l, $tam), $linhas)) + 64 * $this->k;
        $h = count($linhas) * $tam * 1.25 + 44 * $this->k;

        $this->caixa($el, $x, $y, $an, $w, $h, $this->id['fundo'], 0x30);
        $texto = $this->comDestaque(implode('\\N', array_map([Ass::class, 'escapar'], $linhas)), $el['destaque']);
        $this->texto($el, $x, $y, $an, $texto, 'Texto', "\\fs$tam", 41);
    }

    /** Ênfase: escurece a imagem e a palavra-chave bate grande, na cor de destaque. */
    private function elPalavra(array $el): void
    {
        [$ini, $fim] = [$el['inicio'], $el['inicio'] + $el['duracao']];
        $this->ass->evento($ini, $fim, '{\\an7\\pos(0,0)\\1c'.Ass::corTag('#000000').'\\1a&H70&\\bord0\\shad0\\fad(90,120)\\p1}'.$this->retangulo($this->ass->largura, $this->ass->altura).'{\\p0}', 'Forma', 30);

        $tam = $this->ass->px(['p' => 120, 'm' => 160, 'g' => 200][$el['tamanho']]);
        $linhas = Ass::quebrar(mb_strtoupper($el['texto']), $tam, $this->ass->largura * 0.86);
        $cx = (int) ($this->ass->largura / 2);
        $cy = (int) ($this->ass->altura * 0.46);
        $cor = Ass::corTag($this->id['destaque']);

        $this->ass->evento($ini, $fim, "{\\an5\\pos($cx,$cy)\\fs$tam\\c$cor\\fscx135\\fscy135\\t(0,120,\\fscx96\\fscy96)\\t(120,200,\\fscx100\\fscy100)\\t(200,".$this->ms($el['duracao']).',\\fscx106\\fscy106)\\fad(0,120)}'.implode('\\N', array_map([Ass::class, 'escapar'], $linhas)), 'Titulo', 42);
    }

    /** Cartão com itens que entram um a um, cada um com o check na cor de destaque. */
    private function elLista(array $el): void
    {
        $itens = $el['itens'];
        $tam = $this->ass->px(54);
        $titTam = $this->ass->px(46);
        $lh = $tam * 1.55;
        $pad = 44 * $this->k;
        $w = $this->ass->largura * 0.82;
        $temTitulo = $el['texto'] !== '';
        $h = $pad * 2 + count($itens) * $lh + ($temTitulo ? $titTam * 1.6 : 0);

        $topo = match (true) {
            str_starts_with($el['posicao'], 'baixo') => $this->ass->altura * 0.78 - $h,
            $el['posicao'] === 'centro' => ($this->ass->altura - $h) / 2,
            default => $this->ass->altura * 0.12,
        };
        $cx = $this->ass->largura / 2;
        $cy = $topo + $h / 2;
        $ini = $el['inicio'];
        $fim = $el['inicio'] + $el['duracao'];

        $this->ass->evento($ini, $fim, sprintf(
            '{\\an5\\pos(%d,%d)\\1c%s\\1a&H14&\\3c%s\\bord%s\\shad0\\fscx80\\fscy80\\t(0,160,\\fscx100\\fscy100)\\fad(120,140)\\p1}%s{\\p0}',
            $cx, $cy, Ass::corTag($this->id['fundo']), Ass::corTag($this->id['destaque']), Ass::num(2 * $this->k),
            Ass::retanguloArredondado($w, $h, 34 * $this->k)
        ), 'Forma', 35);

        $y = $topo + $pad;
        $esq = (int) ($cx - $w / 2 + $pad);

        if ($temTitulo) {
            $this->ass->evento($ini, $fim, sprintf('{\\an7\\pos(%d,%d)\\fs%d\\c%s\\bord0\\shad0\\fad(160,140)}%s', $esq, $y, $titTam, Ass::corTag($this->id['destaque']), Ass::escapar(mb_strtoupper($el['texto']))), 'Texto', 36);
            $y += $titTam * 1.6;
        }

        foreach ($itens as $n => $item) {
            $t0 = max($ini, (float) $item['inicio']);
            $iy = (int) ($y + $n * $lh + $lh / 2);
            $escala = Ass::num(0.42 * $tam / 50 * 100);
            $this->ass->evento($t0, $fim, sprintf(
                '{\\an5\\pos(%d,%d)\\1c%s\\bord0\\shad0\\fscx0\\fscy0\\t(0,140,\\fscx%s\\fscy%s)\\fad(0,140)\\p1}%s{\\p0}',
                $esq + 24 * $this->k, $iy, Ass::corTag($this->id['destaque']), $escala, $escala, Ass::forma('check')
            ), 'Forma', 37);
            $this->ass->evento($t0, $fim, sprintf(
                '{\\an4\\pos(%d,%d)\\fs%d\\bord0\\shad0\\move(%d,%d,%d,%d,0,180)\\fad(120,140)}%s',
                $esq + 70 * $this->k, $iy, $tam, $esq + 100 * $this->k, $iy, $esq + 70 * $this->k, $iy, Ass::escapar($item['texto'])
            ), 'Texto', 37);
            $this->som($t0, $el['som'] ?? 'pop', 0.5);
        }
    }

    /** Pílula: "PASSO 1", "GRÁTIS", "ANTES". */
    private function elEtiqueta(array $el): void
    {
        $tam = $this->ass->px(['p' => 38, 'm' => 48, 'g' => 62][$el['tamanho']]);
        $texto = mb_strtoupper($el['texto']);
        [$x, $y, $an] = $this->ancora($el['posicao']);
        $w = Ass::larguraTexto($texto, $tam) + 60 * $this->k;
        $h = $tam * 1.7;
        $cor = $el['cor'] === 'primaria' ? $this->id['primaria'] : $this->id['destaque'];
        $corTexto = $el['cor'] === 'primaria' ? $this->id['texto'] : $this->contraste($cor);

        $this->caixa($el, $x, $y, $an, $w, $h, $cor, 0x00, $h / 2, '\\frz-3');
        $this->texto($el, $x, $y, $an, Ass::escapar($texto), 'Titulo', "\\fs$tam\\c".Ass::corTag($corTexto).'\\bord0\\shad0\\frz-3', 41, $w);
    }

    private function elNumero(array $el): void
    {
        $tam = $this->ass->px(['p' => 120, 'm' => 170, 'g' => 220][$el['tamanho']]);
        [$x, $y] = $this->ancora($el['posicao']);
        $x = (int) ($this->ass->largura / 2);
        $ini = $el['inicio'];
        $fim = $ini + $el['duracao'];
        $contagem = min(1.2, $el['duracao'] * 0.6);
        $passos = max(1, min(24, (int) round($contagem * 20)));
        $cor = Ass::corTag($this->id['destaque']);

        for ($i = 0; $i <= $passos; $i++) {
            $fracao = $i / $passos;
            $valor = $el['de'] + ($el['ate'] - $el['de']) * (1 - (1 - $fracao) ** 3); // desacelera no fim
            $t0 = $ini + $contagem * $i / ($passos + 1);
            $t1 = $i === $passos ? $fim : $ini + $contagem * ($i + 1) / ($passos + 1);
            $txt = $el['prefixo'].number_format($valor, $el['decimais'], ',', '.').$el['sufixo'];
            $anim = $i === 0 ? '\\fscx60\\fscy60\\t(0,120,\\fscx100\\fscy100)' : ($i === $passos ? '\\fscx112\\fscy112\\t(0,160,\\fscx100\\fscy100)\\fad(0,140)' : '');
            $this->ass->evento($t0, $t1, "{\\an5\\pos($x,$y)\\fs$tam\\c$cor$anim}".Ass::escapar($txt), 'Titulo', 42);
        }

        if ($el['texto'] !== '') {
            $sub = $this->ass->px(54);
            $this->ass->evento($ini + 0.1, $fim, sprintf('{\\an8\\pos(%d,%d)\\fs%d\\fad(160,140)}%s', $x, $y + $tam * 0.62, $sub, Ass::escapar($el['texto'])), 'Titulo', 42);
        }
    }

    /** Preço "de/por": o antigo riscado em cima, o novo grande numa pílula. */
    private function elPreco(array $el): void
    {
        [$x, $y] = $this->ancora($el['posicao']);
        $x = (int) ($this->ass->largura / 2);
        $tam = $this->ass->px(['p' => 90, 'm' => 120, 'g' => 150][$el['tamanho']]);
        $w = Ass::larguraTexto($el['texto'], $tam) + 90 * $this->k;
        $h = $tam * 1.45;
        $ini = $el['inicio'];
        $fim = $ini + $el['duracao'];

        $this->ass->evento($ini, $fim, sprintf(
            '{\\an5\\pos(%d,%d)\\1c%s\\bord0\\shad0\\fscx0\\fscy0\\t(0,150,\\fscx108\\fscy108)\\t(150,230,\\fscx100\\fscy100)\\fad(0,140)\\p1}%s{\\p0}',
            $x, $y, Ass::corTag($this->id['destaque']), Ass::retanguloArredondado($w, $h, 30 * $this->k)
        ), 'Forma', 40);
        $this->ass->evento($ini, $fim, sprintf('{\\an5\\pos(%d,%d)\\fs%d\\c%s\\bord0\\shad0\\fscx0\\fscy0\\t(0,150,\\fscx108\\fscy108)\\t(150,230,\\fscx100\\fscy100)\\fad(0,140)}%s', $x, $y, $tam, Ass::corTag($this->contraste($this->id['destaque'])), Ass::escapar($el['texto'])), 'Titulo', 41);

        $acima = $y - $h / 2 - 20 * $this->k;
        if ($el['antes'] !== '') {
            $this->ass->evento($ini + 0.08, $fim, sprintf('{\\an2\\pos(%d,%d)\\fs%d\\s1\\alpha&H30&\\fad(160,140)}%s', $x, $acima, $this->ass->px(54), Ass::escapar('de '.$el['antes'])), 'Titulo', 41);
            $acima -= $this->ass->px(70);
        }
        if ($el['subtexto'] !== '') {
            $this->ass->evento($ini + 0.12, $fim, sprintf('{\\an2\\pos(%d,%d)\\fs%d\\fad(160,140)}%s', $x, $acima, $this->ass->px(48), Ass::escapar(mb_strtoupper($el['subtexto']))), 'Titulo', 41);
        }
    }

    /** Lower third: barra de destaque + nome + linha de apoio, entrando da esquerda. */
    private function elNome(array $el): void
    {
        $this->terco($el, false);
    }

    private function elLocal(array $el): void
    {
        $this->terco($el, true);
    }

    private function terco(array $el, bool $pino): void
    {
        $tam = $this->ass->px(58);
        $sub = $this->ass->px(40);
        $x = (int) ($this->ass->largura * 0.07);
        $y = (int) ($this->ass->altura * (str_starts_with($el['posicao'], 'topo') ? 0.16 : 0.62));
        $temSub = $el['subtexto'] !== '';
        $h = $tam * 1.3 + ($temSub ? $sub * 1.35 : 0) + 30 * $this->k;
        $w = max(Ass::larguraTexto($el['texto'], $tam), $temSub ? Ass::larguraTexto($el['subtexto'], $sub) : 0) + 110 * $this->k;
        $ini = $el['inicio'];
        $fim = $ini + $el['duracao'];
        $mov = fn ($xx, $yy) => sprintf('\\move(%d,%d,%d,%d,0,240)', $xx - 90 * $this->k, $yy, $xx, $yy);

        $this->ass->evento($ini, $fim, sprintf('{\\an4%s\\1c%s\\1a&H28&\\bord0\\shad0\\fad(140,140)\\p1}%s{\\p0}', $mov($x, $y), Ass::corTag($this->id['fundo']), Ass::retanguloArredondado($w, $h, 18 * $this->k)), 'Forma', 38);

        if ($pino) {
            $this->ass->evento($ini, $fim, sprintf('{\\an5%s\\1c%s\\bord0\\shad0\\fscx%s\\fscy%s\\fad(140,140)\\p1}%s{\\p0}', $mov($x + 34 * $this->k, $y), Ass::corTag($this->id['destaque']), Ass::num(42 * $this->k), Ass::num(42 * $this->k), Ass::normalizar('m 0 50 l -30 4 b -48 -24 -30 -50 0 -50 b 30 -50 48 -24 30 4')), 'Forma', 39);
        } else {
            $this->ass->evento($ini, $fim, sprintf('{\\an5%s\\1c%s\\bord0\\shad0\\fad(140,140)\\p1}%s{\\p0}', $mov($x + 26 * $this->k, $y), Ass::corTag($this->id['destaque']), Ass::retanguloArredondado(12 * $this->k, $h - 26 * $this->k, 6 * $this->k)), 'Forma', 39);
        }

        $tx = (int) ($x + 62 * $this->k);
        $ty = $temSub ? $y - $sub * 0.6 : $y;
        $this->ass->evento($ini + 0.05, $fim, sprintf('{\\an4%s\\fs%d\\bord0\\shad0\\fad(140,140)}%s', $mov($tx, $ty), $tam, Ass::escapar($el['texto'])), 'Titulo', 40);

        if ($temSub) {
            $this->ass->evento($ini + 0.1, $fim, sprintf('{\\an4%s\\fs%d\\c%s\\bord0\\shad0\\fad(160,140)}%s', $mov($tx, (int) ($y + $tam * 0.55)), $sub, Ass::corTag($this->id['destaque']), Ass::escapar($el['subtexto'])), 'Texto', 40);
        }
    }

    /** Seta, círculo, check, estrela, brilho… com entrada em "pulo" e balanço vivo. */
    private function elSticker(array $el): void
    {
        $tamanho = ['p' => 90, 'm' => 140, 'g' => 210][$el['tamanho']] * $this->k;
        $escala = Ass::num($tamanho);
        $x = (int) ($el['xy'][0] * $this->ass->largura);
        $y = (int) ($el['xy'][1] * $this->ass->altura);
        $cor = Ass::corTag(match ($el['cor']) {
            'texto' => $this->id['texto'], 'primaria' => $this->id['primaria'], default => $this->id['destaque'],
        });
        $dur = $this->ms($el['duracao']);
        $rot = (int) round($el['angulo']);

        // Círculo é um contorno (marca algo na imagem), não uma bola cheia.
        $preenchimento = $el['forma'] === 'circulo'
            ? '\\1a&HFF&\\3c'.$cor.'\\bord'.Ass::num(9 * 100 / max(1, $tamanho))
            : '\\1c'.$cor.'\\3c'.Ass::corTag('#000000').'\\3a&H90&\\bord'.Ass::num(3 * 100 / max(1, $tamanho));

        $vida = '';
        for ($t = 260, $lado = 1; $t + 350 < $dur - 160; $t += 350, $lado *= -1) {
            $vida .= sprintf('\\t(%d,%d,\\frz%d)', $t, $t + 350, $rot + 7 * $lado);
        }

        $this->ass->evento($el['inicio'], $el['inicio'] + $el['duracao'], sprintf(
            '{\\an5\\pos(%d,%d)\\frz%d%s\\shad0\\fscx0\\fscy0\\t(0,140,\\fscx%s\\fscy%s)\\t(140,240,\\fscx%s\\fscy%s)%s\\t(%d,%d,\\fscx0\\fscy0)\\p1}%s{\\p0}',
            $x, $y, $rot, $preenchimento,
            Ass::num($tamanho * 1.2), Ass::num($tamanho * 1.2), $escala, $escala, $vida,
            max(0, $dur - 150), $dur, Ass::forma($el['forma'])
        ), 'Forma', 45);
    }

    /** Cobre a imagem (a voz continua): conceito, processo, número grande. */
    private function elTelaCheia(array $el): void
    {
        $ini = $el['inicio'];
        $fim = $ini + $el['duracao'];
        $W = $this->ass->largura;
        $H = $this->ass->altura;

        $this->ass->evento($ini, $fim, '{\\an7\\pos(0,0)\\1c'.Ass::corTag($this->id['fundo']).'\\bord0\\shad0\\fad(110,110)\\p1}'.$this->retangulo($W, $H).'{\\p0}', 'Forma', 80);
        // Luz de destaque desfocada no canto: dá profundidade sem segunda cor.
        $this->ass->evento($ini, $fim, sprintf('{\\an5\\move(%d,%d,%d,%d)\\1c%s\\1a&HB4&\\bord0\\shad0\\blur%d\\fad(200,110)\\p1}%s{\\p0}', $W * 0.82, $H * 0.16, $W * 0.74, $H * 0.2, Ass::corTag($this->id['destaque']), (int) (60 * $this->k), Ass::circulo($W * 0.42)), 'Forma', 81);

        $tam = $this->ass->px(['p' => 80, 'm' => 100, 'g' => 128][$el['tamanho']]);
        $linhas = Ass::quebrar($el['texto'], $tam, $W * 0.8);
        $temItens = ! empty($el['itens']);
        $cy = (int) ($H * ($temItens ? 0.32 : 0.45));
        $texto = $this->comDestaque(implode('\\N', array_map([Ass::class, 'escapar'], $linhas)), $el['destaque']);

        $this->ass->evento($ini + 0.06, $fim, sprintf('{\\an5\\pos(%d,%d)\\fs%d\\bord0\\shad0\\fscx70\\fscy70\\t(0,180,\\fscx100\\fscy100)\\t(180,%d,\\fscx106\\fscy106)\\fad(80,100)}%s', $W / 2, $cy, $tam, $this->ms($el['duracao']), $texto), 'Titulo', 82);

        if ($el['subtexto'] !== '') {
            $this->ass->evento($ini + 0.2, $fim, sprintf('{\\an8\\pos(%d,%d)\\fs%d\\c%s\\bord0\\shad0\\fad(160,100)}%s', $W / 2, $cy + count($linhas) * $tam * 0.62 + 20 * $this->k, $this->ass->px(50), Ass::corTag($this->id['destaque']), Ass::escapar($el['subtexto'])), 'Texto', 82);
        }

        foreach ($el['itens'] ?? [] as $n => $item) {
            $iy = (int) ($H * 0.48 + $n * $this->ass->px(96));
            $t0 = $ini + 0.3 + $n * 0.28;
            $this->ass->evento($t0, $fim, sprintf('{\\an5\\pos(%d,%d)\\1c%s\\bord0\\shad0\\fscx0\\fscy0\\t(0,120,\\fscx%s\\fscy%s)\\fad(0,100)\\p1}%s{\\p0}', $W * 0.16, $iy, Ass::corTag($this->id['destaque']), Ass::num(22 * $this->k), Ass::num(22 * $this->k), Ass::circulo(50)), 'Forma', 83);
            $this->ass->evento($t0, $fim, sprintf('{\\an4\\pos(%d,%d)\\fs%d\\bord0\\shad0\\move(%d,%d,%d,%d,0,160)\\fad(100,100)}%s', $W * 0.21, $iy, $this->ass->px(58), $W * 0.24, $iy, $W * 0.21, $iy, Ass::escapar($item['texto'])), 'Texto', 83);
            $this->som($t0, 'pop', 0.45);
        }
    }

    private function elFlash(array $el): void
    {
        $this->ass->evento($el['inicio'], $el['inicio'] + $el['duracao'], '{\\an7\\pos(0,0)\\1c&HFFFFFF&\\bord0\\shad0\\fad(0,'.$this->ms($el['duracao']).')\\p1}'.$this->retangulo($this->ass->largura, $this->ass->altura).'{\\p0}', 'Forma', 90);
    }

    private function elCitacao(array $el): void
    {
        $W = $this->ass->largura;
        $tam = $this->ass->px(64);
        $linhas = Ass::quebrar('“'.$el['texto'].'”', $tam, $W * 0.76);
        $h = count($linhas) * $tam * 1.3 + 160 * $this->k;
        [, $cy] = $this->ancora($el['posicao']);
        $cy = (int) max($this->ass->altura * 0.12 + $h / 2, min($cy, $this->ass->altura * 0.78 - $h / 2));
        $ini = $el['inicio'];
        $fim = $ini + $el['duracao'];

        $this->ass->evento($ini, $fim, sprintf('{\\an5\\pos(%d,%d)\\1c%s\\1a&H20&\\bord0\\shad0\\fscx92\\fscy92\\t(0,180,\\fscx100\\fscy100)\\fad(140,140)\\p1}%s{\\p0}', $W / 2, $cy, Ass::corTag($this->id['fundo']), Ass::retanguloArredondado($W * 0.86, $h, 36 * $this->k)), 'Forma', 34);
        $this->ass->evento($ini + 0.05, $fim, sprintf('{\\an5\\pos(%d,%d)\\fs%d\\c%s\\bord0\\shad0\\fad(160,140)}“', $W * 0.16, $cy - $h / 2 + 10 * $this->k, $this->ass->px(220), Ass::corTag($this->id['destaque'])), 'Titulo', 35);
        $this->ass->evento($ini + 0.1, $fim, sprintf('{\\an5\\pos(%d,%d)\\fs%d\\i1\\bord0\\shad0\\fad(200,140)}%s', $W / 2, $cy - 20 * $this->k, $tam, $this->comDestaque(implode('\\N', array_map([Ass::class, 'escapar'], $linhas)), $el['destaque'])), 'Texto', 35);

        if ($el['autor'] !== '') {
            $this->ass->evento($ini + 0.2, $fim, sprintf('{\\an2\\pos(%d,%d)\\fs%d\\c%s\\bord0\\shad0\\fad(200,140)}— %s', $W / 2, $cy + $h / 2 - 30 * $this->k, $this->ass->px(42), Ass::corTag($this->id['destaque']), Ass::escapar($el['autor'])), 'Texto', 35);
        }
    }

    /** Cinco estrelas que acendem uma a uma (avaliação). */
    private function elEstrelas(array $el): void
    {
        [, $y] = $this->ancora($el['posicao']);
        $passo = 120 * $this->k;
        $x0 = $this->ass->largura / 2 - 2 * $passo;
        $fim = $el['inicio'] + $el['duracao'];
        $escala = Ass::num(100 * $this->k);

        for ($i = 0; $i < 5; $i++) {
            $t0 = $el['inicio'] + $i * 0.12;
            $this->ass->evento($t0, $fim, sprintf('{\\an5\\pos(%d,%d)\\1c%s\\3c&H000000&\\3a&H90&\\bord2\\shad0\\fscx0\\fscy0\\t(0,130,\\fscx%s\\fscy%s)\\t(130,220,\\fscx%s\\fscy%s)\\fad(0,140)\\p1}%s{\\p0}', $x0 + $i * $passo, $y, Ass::corTag($this->id['destaque']), Ass::num(125 * $this->k), Ass::num(125 * $this->k), $escala, $escala, Ass::estrela(50)), 'Forma', 44);
            $this->som($t0, $el['som'] ?? 'pop', 0.4);
        }

        if ($el['texto'] !== '') {
            $this->ass->evento($el['inicio'] + 0.5, $fim, sprintf('{\\an8\\pos(%d,%d)\\fs%d\\fad(160,140)}%s', $this->ass->largura / 2, $y + 80 * $this->k, $this->ass->px(52), Ass::escapar($el['texto'])), 'Titulo', 44);
        }
    }

    /* Ajudantes -------------------------------------------------------- */

    /** [x, y, alinhamento ASS] de uma posição nomeada, dentro da zona segura. */
    private function ancora(string $posicao): array
    {
        $W = $this->ass->largura;
        $H = $this->ass->altura;
        $vertical = $H >= $W;

        $y = match (true) {
            str_starts_with($posicao, 'topo') => $H * ($vertical ? 0.2 : 0.18),
            str_starts_with($posicao, 'baixo') => $H * ($vertical ? 0.68 : 0.78),
            default => $H * 0.45,
        };

        return match (true) {
            str_ends_with($posicao, 'esquerda') => [(int) ($W * 0.07), (int) $y, 4],
            str_ends_with($posicao, 'direita') => [(int) ($W * ($vertical ? 0.84 : 0.9)), (int) $y, 6],
            default => [(int) ($W / 2), (int) $y, 5],
        };
    }

    private function animacaoEntrada(array $el, int $x, int $y): string
    {
        $dist = (int) (70 * $this->k);

        return match ($el['animacao']) {
            'deslizar' => sprintf('\\move(%d,%d,%d,%d,0,220)\\fad(120,150)', $x - $dist, $y, $x, $y),
            'subir' => sprintf('\\move(%d,%d,%d,%d,0,240)\\fad(150,150)', $x, $y + $dist, $x, $y),
            'fade' => sprintf('\\pos(%d,%d)\\fad(220,180)', $x, $y),
            'zoom' => sprintf('\\pos(%d,%d)\\fscx160\\fscy160\\t(0,200,\\fscx100\\fscy100)\\fad(150,150)', $x, $y),
            default => sprintf('\\pos(%d,%d)\\fscx0\\fscy0\\t(0,140,\\fscx110\\fscy110)\\t(140,220,\\fscx100\\fscy100)\\fad(0,150)', $x, $y),
        };
    }

    private function texto(array $el, int $x, int $y, int $an, string $texto, string $estilo, string $extra, int $camada, ?float $larguraCaixa = null): void
    {
        $ini = $el['inicio'];
        $fim = $ini + $el['duracao'];

        // Texto alinhado à esquerda/direita dentro da caixa: desloca pela folga.
        if ($larguraCaixa && $an !== 5) {
            $x += (int) ($an === 4 ? 30 * $this->k : -30 * $this->k);
        }

        if ($el['animacao'] === 'digitar') {
            $plano = preg_replace('/\{[^}]*\}/', '', $texto) ?? $texto;
            $letras = mb_str_split(str_replace('\\N', "\n", $plano));
            $passo = min(0.045, ($el['duracao'] * 0.5) / max(1, count($letras)));
            $acumulado = '';
            foreach ($letras as $i => $letra) {
                $acumulado .= $letra;
                $t1 = $i === count($letras) - 1 ? $fim : $ini + ($i + 1) * $passo;
                $this->ass->evento($ini + $i * $passo, $t1, sprintf('{\\an%d\\pos(%d,%d)%s%s}', $an, $x, $y, $extra, $i === count($letras) - 1 ? '\\fad(0,150)' : '').str_replace("\n", '\\N', $acumulado), $estilo, $camada);
            }

            return;
        }

        $this->ass->evento($ini, $fim, sprintf('{\\an%d%s%s}', $an, $this->animacaoEntrada($el, $x, $y), $extra).$texto, $estilo, $camada);
    }

    private function caixa(array $el, int $x, int $y, int $an, float $w, float $h, string $cor, int $alfa, ?float $raio = null, string $extra = ''): void
    {
        // A forma é centrada; para âncoras laterais, empurra o centro para dentro.
        $cx = match ($an) {
            4 => $x + $w / 2, 6 => $x - $w / 2, default => $x,
        };
        $el['animacao'] = $el['animacao'] === 'digitar' ? 'fade' : $el['animacao'];

        $this->ass->evento($el['inicio'], $el['inicio'] + $el['duracao'], sprintf(
            '{\\an5%s\\1c%s\\1a%s\\bord0\\shad0%s\\p1}%s{\\p0}',
            $this->animacaoEntrada($el, (int) $cx, $y), Ass::corTag($cor), Ass::alfaTag($alfa), $extra,
            Ass::retanguloArredondado($w, $h, $raio ?? 26 * $this->k)
        ), 'Forma', 39);
    }

    private function comDestaque(string $texto, array $destaques): string
    {
        foreach ($destaques as $palavra) {
            $seguro = Ass::escapar($palavra);
            if ($seguro === '') {
                continue;
            }
            $texto = preg_replace('/(?<![\p{L}\p{N}])('.preg_quote($seguro, '/').')(?![\p{L}\p{N}])/iu', '{\\c'.Ass::corTag($this->id['destaque']).'}$1{\\c'.Ass::corTag($this->id['texto']).'}', $texto, 1) ?? $texto;
        }

        return $texto;
    }

    private function retangulo(int $w, int $h): string
    {
        return "m 0 0 l $w 0 l $w $h l 0 $h";
    }

    /** Texto preto ou branco sobre uma cor, pelo que tiver mais contraste. */
    private function contraste(string $hex): string
    {
        $hex = ltrim($hex, '#');
        [$r, $g, $b] = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 150 ? '#111111' : '#FFFFFF';
    }

    private function ms(float $s): int
    {
        return (int) round($s * 1000);
    }

    private function som(float $t, ?string $som, float $forca): void
    {
        if ($som && $som !== 'nenhum') {
            $this->sons[] = ['t' => round($t, 3), 'som' => $som, 'forca' => $forca];
        }
    }
}
