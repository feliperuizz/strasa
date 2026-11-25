<?php

namespace App\Video\Render;

/**
 * Legendas queimadas no vídeo, no ritmo da fala (ofício da edit-legendas):
 *
 * - grupos de 1–3 palavras (karaokê), quebrados em fronteira natural (pausa,
 *   pontuação); nunca termina grupo em artigo/preposição ("o", "de", "pra");
 * - a palavra acende na cor de destaque NA HORA em que é dita;
 * - hesitação ("hum", "ãh") não aparece; número falado vira algarismo;
 * - grafia: só corrige o que o plano mandou (correcoes), nunca o que foi dito;
 * - some sob tela cheia/citação (que já mostram texto).
 *
 * Recebe palavras já no TEMPO DA SEQUÊNCIA (o Montador converte).
 */
class Legendas
{
    private const FILLERS = '/^(h+u+m+|hm+|a+h+n*|ã+h*|é{2,}|e+h+|u+h+m*|aham)$/iu';

    private const NAO_TERMINA = ['o', 'a', 'os', 'as', 'um', 'uma', 'uns', 'umas', 'de', 'do', 'da', 'dos', 'das', 'em', 'no', 'na', 'nos', 'nas', 'e', 'que', 'pra', 'pro', 'para', 'com', 'por', 'pelo', 'pela', 'se', 'meu', 'minha', 'seu', 'sua', 'nosso', 'nossa', 'ao', 'à', 'mais', 'muito', 'r$'];

    private const NUMEROS = [
        'dois' => '2', 'duas' => '2', 'três' => '3', 'quatro' => '4', 'cinco' => '5', 'seis' => '6', 'sete' => '7',
        'oito' => '8', 'nove' => '9', 'dez' => '10', 'onze' => '11', 'doze' => '12', 'treze' => '13', 'quatorze' => '14',
        'catorze' => '14', 'quinze' => '15', 'dezesseis' => '16', 'dezessete' => '17', 'dezoito' => '18', 'dezenove' => '19',
        'vinte' => '20', 'trinta' => '30', 'quarenta' => '40', 'cinquenta' => '50', 'cem' => '100', 'mil' => '1000',
    ];

    public const LIMITES = [
        'karaoke' => ['palavras' => 3, 'chars' => 22],
        'pop' => ['palavras' => 1, 'chars' => 18],
        'caixa' => ['palavras' => 5, 'chars' => 30],
        'simples' => ['palavras' => 7, 'chars' => 42],
    ];

    /**
     * @param  array  $palavras  [['w' => 'Olá', 'i' => 0.5, 'f' => 0.8], ...] no tempo da sequência
     * @param  array  $id  identidade (cores, fontes, legenda_estilo, legenda_posicao, caixa_alta)
     * @param  array  $bloco  plano['legendas']
     * @param  array  $ocultar  [[inicio, fim], ...] em que a legenda some
     * @return array grupos [['id' => 'lg01', 'inicio', 'fim', 'palavras' => [...]]]
     */
    public static function gerar(Ass $ass, array $palavras, array $id, array $bloco, array $ocultar = []): array
    {
        $estilo = $bloco['estilo'] ?? $id['legenda_estilo'] ?? 'karaoke';
        $estilo = isset(self::LIMITES[$estilo]) ? $estilo : 'karaoke';
        $posicao = $bloco['posicao'] ?? $id['legenda_posicao'] ?? 'baixo';
        $tamanhoFator = (float) ($bloco['tamanho'] ?? 1.0);

        $limpas = self::preparar($palavras, $bloco['correcoes'] ?? [], (bool) ($id['caixa_alta'] ?? true), $ocultar);
        $grupos = self::agrupar($limpas, self::LIMITES[$estilo]);

        if (! $grupos) {
            return [];
        }

        $k = $ass->k();
        $tamanho = (int) round(match ($estilo) {
            'pop' => 112, 'caixa' => 64, 'simples' => 54, default => 84,
        } * $k * $tamanhoFator);

        $y = (int) round($ass->altura * match ($posicao) {
            'alto' => 0.26, 'centro' => 0.52, default => $ass->largura > $ass->altura ? 0.84 : 0.70,
        });
        $x = (int) round($ass->largura / 2);

        $fonte = $id['fonte_titulo'] ?? 'Montserrat';
        $contorno = '#000000';

        if ($estilo === 'caixa') {
            $ass->estilo('Legenda', $fonte, $tamanho, $id['texto'], $contorno, 14 * $k, 0, true, 5, 3, $id['primaria'], 0x10);
        } elseif ($estilo === 'simples') {
            $ass->estilo('Legenda', $fonte, $tamanho, $id['texto'], $contorno, 3 * $k, 3 * $k, true, 5);
        } else {
            $ass->estilo('Legenda', $fonte, $tamanho, $id['texto'], $contorno, 6.5 * $k, 2.5 * $k, true, 5);
        }

        $destaques = array_map(fn ($d) => self::chave($d), $bloco['destaques'] ?? []);
        $corDestaque = Ass::corTag($id['destaque']);
        $corTexto = Ass::corTag($id['texto']);
        $larguraMax = $ass->largura * 0.8;
        $saida = [];

        foreach ($grupos as $n => $grupo) {
            $proximo = $grupos[$n + 1]['palavras'][0]['i'] ?? INF;
            $ultima = end($grupo['palavras']);
            $fimGrupo = min($proximo, $ultima['f'] + 0.45);
            $fimGrupo = max($fimGrupo, $grupo['palavras'][0]['i'] + 0.3);
            $textos = array_column($grupo['palavras'], 'texto');
            $quebra = self::pontoDeQuebra($textos, $tamanho, $larguraMax);

            $saida[] = [
                'id' => sprintf('lg%02d', $n + 1),
                'inicio' => round($grupo['palavras'][0]['i'], 3),
                'fim' => round($fimGrupo, 3),
                'texto' => implode(' ', $textos),
            ];

            if ($estilo === 'simples') {
                $texto = self::montar($textos, null, $quebra, $destaques, $corDestaque, $corTexto);
                $ass->evento($grupo['palavras'][0]['i'], $fimGrupo, "{\\an5\\pos($x,$y)\\fad(80,60)}".$texto, 'Legenda', 60);

                continue;
            }

            foreach ($grupo['palavras'] as $j => $p) {
                $inicio = $j === 0 ? $p['i'] : $p['i'];
                $fim = $grupo['palavras'][$j + 1]['i'] ?? $fimGrupo;

                if ($estilo === 'pop') {
                    $cor = in_array(self::chave($p['texto']), $destaques, true) ? $corDestaque : $corTexto;
                    $ass->evento($inicio, $fim, "{\\an5\\pos($x,$y)\\c$cor\\fscx70\\fscy70\\t(0,80,\\fscx112\\fscy112)\\t(80,160,\\fscx100\\fscy100)}".Ass::escapar($p['texto']), 'Legenda', 60);

                    continue;
                }

                $entrada = $j === 0 ? '\\fad(40,0)\\fscx86\\fscy86\\t(0,110,\\fscx100\\fscy100)' : '';
                $texto = self::montar($textos, $j, $quebra, $destaques, $corDestaque, $corTexto);
                $ass->evento($inicio, $fim, "{\\an5\\pos($x,$y)$entrada}".$texto, 'Legenda', 60);
            }
        }

        return $saida;
    }

    /** Texto do grupo com a palavra atual acesa. */
    private static function montar(array $textos, ?int $atual, ?int $quebra, array $destaques, string $corDestaque, string $corTexto): string
    {
        $partes = [];

        foreach ($textos as $j => $t) {
            $seguro = Ass::escapar($t);
            $ehDestaque = in_array(self::chave($t), $destaques, true);

            if ($j === $atual) {
                $escala = $ehDestaque ? 116 : 106;
                $seguro = "{\\c$corDestaque\\fscx$escala\\fscy$escala}$seguro{\\c$corTexto\\fscx100\\fscy100}";
            } elseif ($ehDestaque && $atual === null) {
                $seguro = "{\\c$corDestaque}$seguro{\\c$corTexto}";
            }

            $partes[] = ($quebra !== null && $j === $quebra ? '\\N' : ($j > 0 ? ' ' : '')).$seguro;
        }

        return implode('', $partes);
    }

    /** Índice da palavra que começa a 2ª linha, se o grupo não couber numa. */
    private static function pontoDeQuebra(array $textos, int $tamanho, float $max): ?int
    {
        if (count($textos) < 2 || Ass::larguraTexto(implode(' ', $textos), $tamanho) <= $max) {
            return null;
        }

        $melhor = 1;
        $menorDiferenca = INF;

        for ($i = 1; $i < count($textos); $i++) {
            $a = Ass::larguraTexto(implode(' ', array_slice($textos, 0, $i)), $tamanho);
            $b = Ass::larguraTexto(implode(' ', array_slice($textos, $i)), $tamanho);
            if (abs($a - $b) < $menorDiferenca) {
                $menorDiferenca = abs($a - $b);
                $melhor = $i;
            }
        }

        return $melhor;
    }

    /** Tira hesitação, aplica correções e caixa alta, descarta o que está oculto. */
    public static function preparar(array $palavras, array $correcoes, bool $caixaAlta, array $ocultar): array
    {
        $mapa = [];
        $multi = [];
        foreach ($correcoes as $de => $para) {
            $tokens = array_values(array_filter(array_map([self::class, 'chave'], preg_split('/\s+/u', trim((string) $de)) ?: [])));
            if (count($tokens) > 1) {
                $multi[] = [$tokens, $para];
            } elseif ($tokens) {
                $mapa[$tokens[0]] = $para;
            }
        }

        $palavras = self::juntarCorrecoes($palavras, $multi);
        $saida = [];

        foreach ($palavras as $p) {
            $bruta = trim((string) ($p['w'] ?? ''));
            $chave = self::chave($bruta);

            if ($chave === '' || preg_match(self::FILLERS, $chave)) {
                continue;
            }

            foreach ($ocultar as [$a, $b]) {
                if ($p['i'] >= $a - 0.05 && $p['i'] < $b) {
                    continue 2;
                }
            }

            // Pontuação que importa para quebrar o grupo, antes de limpar.
            $fimDeFrase = (bool) preg_match('/[.!?;:,…]$/u', $bruta);

            $texto = $mapa[$chave] ?? $bruta;
            $texto = preg_replace('/[.,;:…"“”]+$/u', '', $texto) ?? $texto;
            $texto = preg_replace('/^["“”]+/u', '', $texto) ?? $texto;

            if (isset(self::NUMEROS[$chave]) && ! isset($mapa[$chave])) {
                $texto = self::NUMEROS[$chave];
            }

            if ($caixaAlta) {
                $texto = mb_strtoupper($texto);
            }

            $saida[] = ['texto' => $texto, 'i' => (float) $p['i'], 'f' => max((float) $p['f'], (float) $p['i'] + 0.08), 'fim_frase' => $fimDeFrase];
        }

        return $saida;
    }

    /**
     * Correção de mais de uma palavra ("na vália" → "navalha", "A gente" →
     * "Agende"): a transcrição quebra um termo em dois; aqui os pedaços viram
     * uma palavra só, do início do primeiro ao fim do último.
     */
    private static function juntarCorrecoes(array $palavras, array $multi): array
    {
        if (! $multi) {
            return $palavras;
        }

        usort($multi, fn ($a, $b) => count($b[0]) <=> count($a[0])); // a mais longa primeiro
        $saida = [];
        $n = count($palavras);

        for ($i = 0; $i < $n; $i++) {
            foreach ($multi as [$tokens, $para]) {
                $k = count($tokens);
                if ($i + $k > $n) {
                    continue;
                }
                for ($j = 0; $j < $k; $j++) {
                    if (self::chave((string) $palavras[$i + $j]['w']) !== $tokens[$j]) {
                        continue 2;
                    }
                }
                $ultima = $palavras[$i + $k - 1];
                $pontuacao = preg_match('/([.!?,;:…]+)$/u', (string) $ultima['w'], $m) ? $m[1] : '';
                $saida[] = ['w' => $para.$pontuacao, 'i' => $palavras[$i]['i'], 'f' => $ultima['f']];
                $i += $k - 1;

                continue 2;
            }
            $saida[] = $palavras[$i];
        }

        return $saida;
    }

    public static function agrupar(array $palavras, array $limite): array
    {
        $grupos = [];
        $atual = [];

        foreach ($palavras as $n => $p) {
            $atual[] = $p;
            $proxima = $palavras[$n + 1] ?? null;
            $pausa = $proxima ? $proxima['i'] - $p['f'] : INF;
            $chars = mb_strlen(implode(' ', array_column($atual, 'texto')));
            $charsComProxima = $proxima ? $chars + 1 + mb_strlen($proxima['texto']) : INF;

            $deveQuebrar = $proxima === null
                || $pausa > 0.35
                || $p['fim_frase']
                || count($atual) >= $limite['palavras']
                || $charsComProxima > $limite['chars'];

            // Não deixa "de", "o", "pra" pendurado no fim do grupo, a menos que haja pausa.
            if ($deveQuebrar && $proxima && $pausa <= 0.35 && ! $p['fim_frase'] && $limite['palavras'] > 1
                && in_array(self::chave($p['texto']), self::NAO_TERMINA, true) && count($atual) > 1) {
                array_pop($atual);
                $grupos[] = ['palavras' => $atual];
                $atual = [$p];

                continue;
            }

            if ($deveQuebrar) {
                $grupos[] = ['palavras' => $atual];
                $atual = [];
            }
        }

        if ($atual) {
            $grupos[] = ['palavras' => $atual];
        }

        return $grupos;
    }

    public static function chave(string $texto): string
    {
        return mb_strtolower(trim(preg_replace('/[^\p{L}\p{N}$%]+/u', '', $texto) ?? ''));
    }
}
