<?php

namespace App\Video\Render;

/**
 * Documento de legenda ASS (Advanced SubStation), desenhado pelo libass
 * dentro do FFmpeg.
 *
 * Por que ASS e não HTML/Chrome (o HyperFrames do pacote de skills): numa
 * VPS com cPanel, subir um Chromium headless por animação come memória e
 * quebra fácil. O ASS tem tudo o que um Reels precisa — fontes da marca,
 * cor por palavra, escala/rotação/movimento animados (\t, \move, \fad),
 * desenho vetorial (\p1) para cartões, pílulas, setas e estrelas — e é
 * renderizado pelo próprio FFmpeg, no mesmo passe do vídeo.
 *
 * Coordenadas em pixels do vídeo final (PlayRes = resolução de saída).
 */
class Ass
{
    private array $estilos = [];

    private array $eventos = [];

    public function __construct(public readonly int $largura, public readonly int $altura) {}

    /**
     * Fator de escala em relação ao layout de referência (vertical 1080x1920).
     * Pelo lado menor; no horizontal um pouco menor, porque a tela é vista de longe.
     */
    public function k(): float
    {
        return min($this->largura, $this->altura) / 1080 * ($this->largura > $this->altura ? 0.85 : 1.0);
    }

    public function px(float $valor): int
    {
        return (int) round($valor * $this->k());
    }

    public function estilo(string $nome, string $fonte, int $tamanho, string $cor = '#FFFFFF', string $contorno = '#000000', float $borda = 0, float $sombra = 0, bool $negrito = true, int $alinhamento = 5, int $estiloBorda = 1, string $fundo = '#000000', int $alfaFundo = 0x80): void
    {
        $this->estilos[$nome] = sprintf(
            'Style: %s,%s,%d,%s,%s,%s,%s,%d,0,0,0,100,100,0,0,%d,%s,%s,%d,0,0,0,1',
            $nome, $this->fonte($fonte), $tamanho, self::cor($cor), self::cor($cor), self::cor($contorno), self::cor($fundo, $alfaFundo),
            $negrito ? -1 : 0, $estiloBorda, self::num($borda), self::num($sombra), $alinhamento
        );
    }

    public function evento(float $inicio, float $fim, string $texto, string $estilo = 'Base', int $camada = 0): void
    {
        if ($fim - $inicio < 0.01) {
            return;
        }

        $this->eventos[] = [$camada, $inicio, sprintf(
            'Dialogue: %d,%s,%s,%s,,0,0,0,,%s',
            $camada, self::tempo($inicio), self::tempo($fim), $estilo, $texto
        )];
    }

    public function vazio(): bool
    {
        return $this->eventos === [];
    }

    public function documento(): string
    {
        usort($this->eventos, fn ($a, $b) => [$a[1], $a[0]] <=> [$b[1], $b[0]]);

        if (! isset($this->estilos['Base'])) {
            $this->estilo('Base', 'Montserrat', $this->px(60));
        }

        return implode("\n", [
            '[Script Info]',
            'ScriptType: v4.00+',
            'PlayResX: '.$this->largura,
            'PlayResY: '.$this->altura,
            'WrapStyle: 2',
            'ScaledBorderAndShadow: yes',
            'YCbCr Matrix: TV.709',
            '',
            '[V4+ Styles]',
            'Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding',
            ...array_values($this->estilos),
            '',
            '[Events]',
            'Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text',
            ...array_column($this->eventos, 2),
            '',
        ]);
    }

    /* Conversões ------------------------------------------------------- */

    /** #RRGGBB → &HAABBGGRR (alfa 00 = opaco, FF = invisível). */
    public static function cor(string $hex, int $alfa = 0): string
    {
        $hex = ltrim(trim($hex), '#');

        if (! preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            $hex = 'FFFFFF';
        }

        return sprintf('&H%02X%s%s%s', max(0, min(255, $alfa)), substr($hex, 4, 2), substr($hex, 2, 2), substr($hex, 0, 2));
    }

    /** Cor para tag inline: \c&HBBGGRR& */
    public static function corTag(string $hex): string
    {
        $hex = ltrim(trim($hex), '#');

        if (! preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            $hex = 'FFFFFF';
        }

        return sprintf('&H%s%s%s&', substr($hex, 4, 2), substr($hex, 2, 2), substr($hex, 0, 2));
    }

    public static function alfaTag(int $alfa): string
    {
        return sprintf('&H%02X&', max(0, min(255, $alfa)));
    }

    public static function tempo(float $s): string
    {
        $cs = (int) round(max(0, $s) * 100);

        return sprintf('%d:%02d:%02d.%02d', intdiv($cs, 360000), intdiv($cs, 6000) % 60, intdiv($cs, 100) % 60, $cs % 100);
    }

    public static function num(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }

    /**
     * Texto que vem de fora (fala transcrita, Claude, equipe) não pode abrir
     * tag de override: chaves e barra invertida viram caracteres inofensivos.
     */
    public static function escapar(string $texto): string
    {
        return str_replace(['\\', '{', '}', "\r", "\n"], ['∖', '(', ')', '', '\\N'], $texto);
    }

    private function fonte(string $fonte): string
    {
        return str_replace(',', ' ', $fonte);
    }

    /* Medidas (estimativa sem abrir a fonte) ---------------------------- */

    /** Largura aproximada do texto em px. Erra para mais (caixas folgadas > texto vazando). */
    public static function larguraTexto(string $texto, float $tamanho): float
    {
        $total = 0.0;

        foreach (mb_str_split($texto) as $c) {
            $total += match (true) {
                $c === ' ' => 0.28,
                str_contains('iIl1.,:;!|\'`', $c) => 0.3,
                str_contains('fjtr()[]-', $c) => 0.4,
                str_contains('MWmw@%', $c) => 0.9,
                mb_strtoupper($c) === $c && mb_strtolower($c) !== $c => 0.7,
                is_numeric($c) => 0.62,
                default => 0.56,
            };
        }

        return $total * $tamanho;
    }

    /** Quebra em linhas que caibam em $max px. */
    public static function quebrar(string $texto, float $tamanho, float $max): array
    {
        $linhas = [];
        $atual = '';

        foreach (preg_split('/\s+/u', trim($texto)) ?: [] as $palavra) {
            $teste = $atual === '' ? $palavra : $atual.' '.$palavra;

            if ($atual !== '' && self::larguraTexto($teste, $tamanho) > $max) {
                $linhas[] = $atual;
                $atual = $palavra;
            } else {
                $atual = $teste;
            }
        }

        if ($atual !== '') {
            $linhas[] = $atual;
        }

        return $linhas ?: [''];
    }

    /* Desenho vetorial (\p1) ------------------------------------------- */

    /**
     * O libass alinha um desenho pela caixa que vai da ORIGEM (0,0) até o
     * ponto máximo — coordenadas negativas não entram na conta. Uma forma
     * desenhada em volta de (0,0) com \an5 saía deslocada meia largura para
     * cima e para a esquerda (e a rotação de uma seta piorava). Por isso todo
     * desenho é transladado para começar em (0,0): aí \an5 centra de verdade,
     * e escala/rotação acontecem em volta do centro.
     */
    public static function normalizar(string $desenho): string
    {
        $tokens = preg_split('/\s+/', trim($desenho)) ?: [];
        $xs = [];
        $ys = [];
        $eixo = 0;

        foreach ($tokens as $t) {
            if (is_numeric($t)) {
                $eixo % 2 === 0 ? $xs[] = (float) $t : $ys[] = (float) $t;
                $eixo++;
            } else {
                $eixo = 0;
            }
        }

        if (! $xs || ! $ys) {
            return $desenho;
        }

        [$dx, $dy] = [-min($xs), -min($ys)];
        $eixo = 0;

        return implode(' ', array_map(function ($t) use (&$eixo, $dx, $dy) {
            if (! is_numeric($t)) {
                $eixo = 0;

                return $t;
            }

            $valor = (float) $t + ($eixo % 2 === 0 ? $dx : $dy);
            $eixo++;

            return self::num($valor);
        }, $tokens));
    }

    public static function retanguloArredondado(float $w, float $h, float $r): string
    {
        return self::normalizar(self::retanguloArredondadoCentrado($w, $h, $r));
    }

    private static function retanguloArredondadoCentrado(float $w, float $h, float $r): string
    {
        $r = max(0, min($r, $w / 2, $h / 2));
        $x0 = -$w / 2;
        $y0 = -$h / 2;
        $x1 = $w / 2;
        $y1 = $h / 2;
        $c = $r * 0.4477; // aproximação do quarto de círculo por Bézier
        $f = fn ($n) => self::num($n);

        return implode(' ', [
            'm', $f($x0 + $r), $f($y0),
            'l', $f($x1 - $r), $f($y0),
            'b', $f($x1 - $c), $f($y0), $f($x1), $f($y0 + $c), $f($x1), $f($y0 + $r),
            'l', $f($x1), $f($y1 - $r),
            'b', $f($x1), $f($y1 - $c), $f($x1 - $c), $f($y1), $f($x1 - $r), $f($y1),
            'l', $f($x0 + $r), $f($y1),
            'b', $f($x0 + $c), $f($y1), $f($x0), $f($y1 - $c), $f($x0), $f($y1 - $r),
            'l', $f($x0), $f($y0 + $r),
            'b', $f($x0), $f($y0 + $c), $f($x0 + $c), $f($y0), $f($x0 + $r), $f($y0),
        ]);
    }

    public static function circulo(float $raio): string
    {
        $c = $raio * 0.5523;
        $f = fn ($n) => self::num($n);

        return self::normalizar(implode(' ', [
            'm', 0, $f(-$raio),
            'b', $f($c), $f(-$raio), $f($raio), $f(-$c), $f($raio), 0,
            'b', $f($raio), $f($c), $f($c), $f($raio), 0, $f($raio),
            'b', $f(-$c), $f($raio), $f(-$raio), $f($c), $f(-$raio), 0,
            'b', $f(-$raio), $f(-$c), $f(-$c), $f(-$raio), 0, $f(-$raio),
        ]));
    }

    public static function estrela(float $raio, int $pontas = 5, float $interno = 0.45): string
    {
        $pontos = [];

        for ($i = 0; $i < $pontas * 2; $i++) {
            $r = $i % 2 === 0 ? $raio : $raio * $interno;
            $a = -M_PI / 2 + $i * M_PI / $pontas;
            $pontos[] = self::num(cos($a) * $r).' '.self::num(sin($a) * $r);
        }

        return self::normalizar('m '.array_shift($pontos).' l '.implode(' ', $pontos));
    }

    /** Formas dos stickers, desenhadas num quadrado de ~100 px (já normalizadas). */
    public static function forma(string $nome): string
    {
        return self::normalizar(self::formaCentrada($nome));
    }

    private static function formaCentrada(string $nome): string
    {
        return match ($nome) {
            'seta' => 'm -50 -10 l 15 -10 l 15 -32 l 52 0 l 15 32 l 15 10 l -50 10',
            'check' => 'm -48 2 l -30 -16 l -12 2 l 32 -42 l 50 -24 l -12 38',
            'x' => 'm -40 -26 l -26 -40 l 0 -14 l 26 -40 l 40 -26 l 14 0 l 40 26 l 26 40 l 0 14 l -26 40 l -40 26 l -14 0',
            'estrela' => self::estrela(50),
            'brilho' => 'm 0 -52 b 6 -12 12 -6 52 0 b 12 6 6 12 0 52 b -6 12 -12 6 -52 0 b -12 -6 -6 -12 0 -52',
            'coracao' => 'm 0 -18 b -10 -46 -50 -40 -48 -10 b -46 14 -18 30 0 46 b 18 30 46 14 48 -10 b 50 -40 10 -46 0 -18',
            'raio' => 'm 10 -52 l -34 6 l -4 6 l -14 52 l 34 -8 l 4 -8',
            'balao' => self::retanguloArredondadoCentrado(110, 70, 22).' m -20 34 l -34 58 l 2 34',
            'sublinhado' => 'm -60 -6 b -20 -12 20 -12 60 -6 l 60 6 b 20 0 -20 0 -60 6',
            'fogo' => 'm 0 -55 b 18 -25 40 -10 34 18 b 30 40 12 52 0 52 b -12 52 -30 40 -34 18 b -38 -4 -18 -10 -12 -28 b -6 -16 -2 -10 4 -8 b 8 -24 4 -40 0 -55',
            default => self::circulo(50),
        };
    }
}
