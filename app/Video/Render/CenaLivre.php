<?php

namespace App\Video\Render;

use App\Video\Plano\Linha;
use App\Video\Plano\Validador;

/**
 * Validação da "cena livre" (scripts/video/motion/cena.py): a animação que o
 * Claude compõe do zero, camada por camada. Tudo vira dado limpo com limites
 * — chaves conhecidas, números presos, textos cortados, cores conferidas —
 * e as palavras-gatilho ({"arquivo","t"} / {"segmento","t"}) viram segundos
 * desde o início da cena. Nada aqui é executado: o motor só desenha.
 */
class CenaLivre
{
    private const TIPOS = ['texto', 'retangulo', 'circulo', 'linha', 'caminho', 'icone', 'logo', 'imagem', 'particulas', 'barra', 'numero', 'grupo'];

    private const ANIMAVEIS = ['x', 'y', 'escala', 'escala_x', 'escala_y', 'rotacao', 'opacidade', 'progresso', 'desfoque', 'w', 'h', 'dx', 'dy'];

    private const NUMEROS = [
        'x', 'y', 'escala', 'escala_x', 'escala_y', 'rotacao', 'opacidade', 'progresso', 'desfoque', 'w', 'h', 'tamanho', 'alfa',
        'espacamento', 'largura_max', 'entrelinha', 'fundo_alfa', 'borda_alfa', 'raio', 'brilho', 'sombra', 'traco', 'tracejado',
        'pulso_velocidade', 'tamanho_svg', 'fundo_raio', 'quantidade', 'velocidade', 'de', 'ate', 'decimais', 'contar_dur', 'cascata',
    ];

    private const BOOLEANOS = ['caixa_alta', 'quebrar', 'seta', 'pulso', 'suave', 'preencher'];

    private const TEXTOS = ['id' => 20, 'texto' => 160, 'prefixo' => 8, 'sufixo' => 8, 'sigla' => 4];

    private const ENTRADAS = ['subir', 'descer', 'esquerda', 'direita', 'pop', 'escala', 'zoom', 'desfoque', 'girar', 'fade', 'mascara', 'desenhar', 'digitar', 'letras'];

    private const SAIDAS = ['fade', 'cair', 'subir', 'encolher', 'esquerda', 'direita', 'zoom', 'apagar', 'nenhuma'];

    private const CORES = ['destaque', 'texto', 'primaria', 'fundo', 'suave', 'cartao', 'branco', 'preto'];

    private int $nos = 0;

    private function __construct(private Linha $linha, private float $inicio, private float $duracao, private $texto) {}

    /** @param callable $texto fn($valor, $max): string (limpeza do Validador) */
    public static function normalizar(array $el, array $normal, Linha $linha, callable $texto): ?array
    {
        $v = new self($linha, (float) $normal['inicio'], (float) $normal['duracao'], $texto);
        $camadas = $v->camadas($el['camadas'] ?? [], 0);

        if (! $camadas) {
            return null;
        }

        $cam = is_array($el['camera'] ?? null) ? $el['camera'] : [];
        $fundo = $el['fundo'] ?? null;

        return array_merge($normal, array_filter([
            'formato' => ($el['formato'] ?? '') === 'tela_cheia' ? 'tela_cheia' : 'sobreposicao',
            'conceito' => $texto($el['conceito'] ?? '', 200),
            'storyboard' => $texto($el['storyboard'] ?? '', 800),
            'oculta_legenda' => (bool) ($el['oculta_legenda'] ?? (($el['formato'] ?? '') === 'tela_cheia')),
            'fundo' => is_string($fundo) ? $v->cor($fundo) : (is_array($fundo) ? $v->fundo($fundo) : null),
            'camera' => $cam ? array_filter([
                'zoom' => isset($cam['zoom']) ? array_map(fn ($z) => $v->num($z, 0.5, 3), array_slice((array) $cam['zoom'], 0, 2)) : null,
                'dur' => isset($cam['dur']) ? $v->num($cam['dur'], 0.1, 30) : null,
                'curva' => $v->curva($cam['curva'] ?? null),
                'deriva' => isset($cam['deriva']) ? array_map(fn ($d) => $v->num($d, -600, 600), array_slice((array) $cam['deriva'], 0, 2)) : null,
                'foco' => isset($cam['foco']) ? array_map(fn ($d) => $v->num($d, 0, 2000), array_slice((array) $cam['foco'], 0, 2)) : null,
                'punches' => array_values(array_map(fn ($p) => ['em' => $v->tempo($p['em'] ?? 0), 'forca' => $v->num($p['forca'] ?? 0.15, 0, 0.6), 'dur' => $v->num($p['dur'] ?? 1.2, 0.2, 4)],
                    array_filter(array_slice((array) ($cam['punches'] ?? []), 0, 6), 'is_array'))),
            ], fn ($x) => $x !== null && $x !== []) : null,
            'camadas' => $camadas,
            'sons' => array_values(array_filter(array_map(fn ($s) => is_array($s) && in_array($s['som'] ?? '', Validador::SONS, true) && $s['som'] !== 'nenhum'
                ? ['em' => $v->tempo($s['em'] ?? 0), 'som' => $s['som'], 'forca' => $v->num($s['forca'] ?? 0.6, 0.1, 1)] : null, array_slice((array) ($el['sons'] ?? []), 0, 12)))),
        ], fn ($x) => $x !== null && $x !== '' && $x !== []));
    }

    private function camadas(mixed $lista, int $nivel): array
    {
        $saida = [];
        foreach (array_slice(is_array($lista) ? array_values($lista) : [], 0, 60) as $c) {
            if (! is_array($c) || ! in_array($c['tipo'] ?? '', self::TIPOS, true) || ++$this->nos > 120) {
                continue;
            }
            $saida[] = $this->camada($c, $nivel);
        }

        return $saida;
    }

    private function camada(array $c, int $nivel): array
    {
        $n = ['tipo' => $c['tipo']];

        foreach (self::NUMEROS as $k) {
            if (isset($c[$k]) && is_numeric($c[$k])) {
                $n[$k] = $this->num($c[$k], -6000, 6000);
            }
        }
        foreach (self::BOOLEANOS as $k) {
            if (array_key_exists($k, $c)) {
                $n[$k] = (bool) $c[$k];
            }
        }
        foreach (self::TEXTOS as $k => $max) {
            if (isset($c[$k]) && is_scalar($c[$k])) {
                $n[$k] = ($this->texto)($c[$k], $max);
            }
        }
        foreach (['cor', 'fundo_pilula', 'borda', 'cor_brilho', 'trilho', 'fundo'] as $k) {
            if (isset($c[$k]) && ($cor = $this->cor($c[$k]))) {
                $n[$k] = $cor;
            }
        }
        if (isset($c['cores']) && is_array($c['cores'])) {
            $n['cores'] = array_values(array_filter(array_map(fn ($x) => $this->cor($x), array_slice($c['cores'], 0, 4))));
        }

        $n['fonte'] = in_array($c['fonte'] ?? '', ['titulo', 'forte', 'texto', 'mono'], true) ? $c['fonte'] : 'forte';
        if (isset($c['alinhar']) && in_array($c['alinhar'], ['centro', 'esq', 'dir'], true)) {
            $n['alinhar'] = $c['alinhar'];
        }
        if (isset($c['estilo']) && in_array($c['estilo'], ['solido', 'cartao', 'contorno'], true)) {
            $n['estilo'] = $c['estilo'];
        }
        if (($c['ancora'] ?? '') === 'esquerda') {
            $n['ancora'] = 'esquerda';
        }
        if (isset($c['destaque'])) {
            $n['destaque'] = array_values(array_filter(array_map(fn ($d) => ($this->texto)($d, 30), array_slice((array) $c['destaque'], 0, 8))));
        }
        if (isset($c['curva_contagem'])) {
            $n['curva_contagem'] = $this->curva($c['curva_contagem']);
        }
        if (isset($c['contar_em'])) {
            $n['contar_em'] = $this->tempo($c['contar_em']);
        }

        // Tempo de cada palavra (entra na fala).
        if (isset($c['palavras']) && is_array($c['palavras'])) {
            $n['palavras'] = array_map(fn ($p) => $this->tempo($p), array_slice($c['palavras'], 0, 60));
        }

        // Formas.
        if (isset($c['pontos']) && is_array($c['pontos'])) {
            $n['pontos'] = array_values(array_filter(array_map(fn ($p) => is_array($p) && count($p) >= 2 && is_numeric($p[0]) && is_numeric($p[1])
                ? [$this->num($p[0], -3000, 4000), $this->num($p[1], -3000, 5000)] : null, array_slice($c['pontos'], 0, 40))));
        }
        if (isset($c['d']) && is_string($c['d']) && preg_match('/^[MmLlHhVvCcSsQqTtAaZz0-9eE.,\s\-+]{1,4000}$/', $c['d'])) {
            $n['d'] = $c['d'];
        }
        if (isset($c['area']) && is_array($c['area'])) {
            $n['area'] = array_map(fn ($a) => $this->num($a, -2000, 4000), array_slice(array_values($c['area']), 0, 4));
        }

        // Ícone (lucide:nome | marca:slug), logo (o servidor resolve o arquivo) e imagem.
        if (isset($c['nome']) && is_string($c['nome']) && preg_match('/^(lucide:|marca:)?[a-z0-9][a-z0-9\-]{0,60}$/', strtolower($c['nome']))) {
            $n['nome'] = strtolower($c['nome']);
        }
        if (isset($c['logo']) && is_string($c['logo']) && preg_match("/^[\\p{L}\\p{N}][\\p{L}\\p{N} .&+'’\\-]{0,39}$/u", trim($c['logo']))) {
            $n['logo'] = trim($c['logo']);
        }
        if (isset($c['site']) && is_string($c['site']) && preg_match('#^(https?://)?([a-z0-9-]+\.)+[a-z]{2,}(/.*)?$#i', $c['site'])) {
            $n['site'] = strtolower($c['site']);
        }
        if (($c['tipo'] === 'imagem')) {
            $n['arquivo'] = 'logo_cliente'; // única imagem livre: a logo do cliente
        }

        // Animação.
        foreach (['entrada' => self::ENTRADAS, 'saida' => self::SAIDAS] as $k => $tipos) {
            if (! isset($c[$k])) {
                continue;
            }
            $e = is_string($c[$k]) ? ['tipo' => $c[$k]] : (is_array($c[$k]) ? $c[$k] : []);
            if (in_array($e['tipo'] ?? '', $tipos, true)) {
                $n[$k] = array_filter([
                    'tipo' => $e['tipo'],
                    'em' => isset($e['em']) ? $this->tempo($e['em']) : null,
                    'dur' => isset($e['dur']) ? $this->num($e['dur'], 0.05, 10) : null,
                    'curva' => isset($e['curva']) ? $this->curva($e['curva']) : null,
                ], fn ($x) => $x !== null);
            }
        }
        if (isset($c['anim']) && is_array($c['anim'])) {
            $n['anim'] = array_values(array_filter(array_map(function ($k) {
                if (! is_array($k)) {
                    return null;
                }
                $props = fn ($d) => is_array($d) ? array_map(fn ($x) => $this->num($x, -6000, 6000), array_intersect_key(array_filter($d, 'is_numeric'), array_flip(self::ANIMAVEIS))) : [];

                return array_filter(['em' => $this->tempo($k['em'] ?? 0), 'dur' => $this->num($k['dur'] ?? 0.4, 0, 30),
                    'de' => $props($k['de'] ?? null), 'para' => $props($k['para'] ?? null), 'curva' => $this->curva($k['curva'] ?? null)], fn ($x) => $x !== []);
            }, array_slice($c['anim'], 0, 12))));
        }

        if ($c['tipo'] === 'grupo' && $nivel < 4) {
            $n['filhos'] = $this->camadas($c['filhos'] ?? [], $nivel + 1);
        }

        return $n;
    }

    private function fundo(array $f): array
    {
        return array_filter([
            'cor' => isset($f['cor']) ? $this->cor($f['cor']) : null,
            'cores' => isset($f['cores']) && is_array($f['cores']) ? array_values(array_filter(array_map(fn ($x) => $this->cor($x), array_slice($f['cores'], 0, 4)))) : null,
            'tipo' => ($f['tipo'] ?? '') === 'radial' ? 'radial' : null,
            'angulo' => isset($f['angulo']) ? $this->num($f['angulo'], -360, 360) : null,
            'centro_y' => isset($f['centro_y']) ? $this->num($f['centro_y'], 0, 1) : null,
            'grade' => isset($f['grade']) ? (bool) $f['grade'] : null,
            'grade_passo' => isset($f['grade_passo']) ? $this->num($f['grade_passo'], 20, 400) : null,
            'grade_velocidade' => isset($f['grade_velocidade']) ? $this->num($f['grade_velocidade'], -200, 200) : null,
            'vinheta' => isset($f['vinheta']) ? (bool) $f['vinheta'] : null,
            'brilho' => is_array($f['brilho'] ?? null) ? array_filter([
                'x' => isset($f['brilho']['x']) ? $this->num($f['brilho']['x'], -500, 1600) : null,
                'y' => isset($f['brilho']['y']) ? $this->num($f['brilho']['y'], -500, 2500) : null,
                'raio' => isset($f['brilho']['raio']) ? $this->num($f['brilho']['raio'], 50, 2000) : null,
                'forca' => isset($f['brilho']['forca']) ? $this->num($f['brilho']['forca'], 0, 1) : null,
                'cor' => isset($f['brilho']['cor']) ? $this->cor($f['brilho']['cor']) : null,
            ], fn ($x) => $x !== null) : null,
        ], fn ($x) => $x !== null && $x !== []);
    }

    /** Segundos desde o início da cena: número direto, ou palavra-gatilho resolvida na linha do tempo. */
    private function tempo(mixed $v): float
    {
        if (is_numeric($v)) {
            return round(max(-1.0, min((float) $v, $this->duracao + 2)), 3);
        }
        $abs = is_array($v) ? $this->linha->resolver($v) : null;

        return $abs === null ? 0.0 : round(max(0.0, min($abs - $this->inicio, $this->duracao + 2)), 3);
    }

    private function num(mixed $v, float $min, float $max): float
    {
        $f = is_numeric($v) ? (float) $v : $min;

        return round(max($min, min($max, is_finite($f) ? $f : $min)), 3);
    }

    private function cor(mixed $v): ?string
    {
        if (! is_string($v)) {
            return null;
        }
        if (in_array($v, self::CORES, true)) {
            return $v;
        }

        return preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? strtoupper($v) : null;
    }

    private function curva(mixed $v): ?string
    {
        return is_string($v) && preg_match('/^(none|linear|power[0-4]|quad|cubic|quart|quint|expo|circ|sine|back|elastic)(\.(in|out|inout|inOut))?(\(\d(\.\d+)?\))?$/i', $v) ? strtolower($v) : null;
    }
}
