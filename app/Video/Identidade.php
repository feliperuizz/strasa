<?php

namespace App\Video;

use App\Models\VideoProfile;

/** Perfil de vídeo do cliente → parâmetros que o render usa. */
final class Identidade
{
    public static function de(VideoProfile $perfil): array
    {
        $cor = fn (?string $hex, string $padrao) => preg_match('/^#[0-9a-f]{6}$/i', (string) $hex) ? strtoupper($hex) : $padrao;

        return [
            'primaria' => $cor($perfil->cor_primaria, '#111827'),
            'destaque' => $cor($perfil->cor_destaque, '#F59E0B'),
            'texto' => $cor($perfil->cor_texto, '#FFFFFF'),
            'fundo' => $cor($perfil->cor_fundo, '#0B0B0F'),
            'fonte_titulo' => $perfil->fonteTituloEfetiva() ?: 'Montserrat',
            'fonte_texto' => $perfil->fonte_texto ?: 'Montserrat',
            'legenda_estilo' => $perfil->legenda_estilo ?: 'karaoke',
            'legenda_posicao' => $perfil->legenda_posicao ?: 'baixo',
            'caixa_alta' => (bool) $perfil->legenda_caixa_alta,
            'logo_posicao' => $perfil->logo_posicao ?: 'topo_direita',
            'logo_tamanho' => max(6, min(40, (int) $perfil->logo_tamanho ?: 16)),
            'logo_opacidade' => max(20, min(100, (int) $perfil->logo_opacidade ?: 90)),
            'grade' => $perfil->cor_grade ?: 'natural',
        ];
    }

    /** Campos que podem ser trocados só numa edição (o resto vem do perfil do cliente). */
    public const AJUSTAVEIS = ['cor_primaria', 'cor_destaque', 'cor_texto', 'cor_fundo', 'fonte_titulo', 'fonte_texto',
        'legenda_estilo', 'legenda_posicao', 'legenda_caixa_alta', 'logo_posicao', 'logo_tamanho', 'cor_grade'];

    /**
     * Identidade do cliente + o que a equipe mudou SÓ nesta edição
     * (opcoes.identidade). Valores inválidos são ignorados: vale o do cliente.
     */
    public static function daEdicao(VideoProfile $perfil, array $ajustes): array
    {
        $id = self::de($perfil);
        $hex = fn ($v) => is_string($v) && preg_match('/^#[0-9a-f]{6}$/i', $v) ? strtoupper($v) : null;

        foreach (['cor_primaria' => 'primaria', 'cor_destaque' => 'destaque', 'cor_texto' => 'texto', 'cor_fundo' => 'fundo'] as $campo => $chave) {
            if ($cor = $hex($ajustes[$campo] ?? null)) {
                $id[$chave] = $cor;
            }
        }

        foreach (['fonte_titulo', 'fonte_texto'] as $campo) {
            if (in_array($ajustes[$campo] ?? null, VideoProfile::FONTES, true)) {
                $id[$campo] = $ajustes[$campo];
            }
        }

        if (isset(VideoProfile::ESTILOS_LEGENDA[$ajustes['legenda_estilo'] ?? ''])) {
            $id['legenda_estilo'] = $ajustes['legenda_estilo'];
        }
        if (in_array($ajustes['legenda_posicao'] ?? null, ['baixo', 'centro', 'alto'], true)) {
            $id['legenda_posicao'] = $ajustes['legenda_posicao'];
        }
        if (isset($ajustes['legenda_caixa_alta'])) {
            $id['caixa_alta'] = (bool) $ajustes['legenda_caixa_alta'];
        }
        if (isset(VideoProfile::POSICOES_LOGO[$ajustes['logo_posicao'] ?? ''])) {
            $id['logo_posicao'] = $ajustes['logo_posicao'];
        }
        if (isset($ajustes['logo_tamanho']) && is_numeric($ajustes['logo_tamanho'])) {
            $id['logo_tamanho'] = max(6, min(40, (int) $ajustes['logo_tamanho']));
        }
        if (isset(VideoProfile::GRADES[$ajustes['cor_grade'] ?? ''])) {
            $id['grade'] = $ajustes['cor_grade'];
        }

        return $id;
    }

    /** Filtro de cor do FFmpeg para cada "cor da imagem" do perfil. */
    public static function filtroGrade(string $grade): string
    {
        return match ($grade) {
            'quente' => 'colorbalance=rs=0.05:gs=0.01:bs=-0.06:rm=0.04:bm=-0.04,eq=saturation=1.08:contrast=1.04',
            'frio' => 'colorbalance=rs=-0.04:bs=0.06:bm=0.03,eq=saturation=0.98:contrast=1.05',
            'vivo' => 'eq=saturation=1.28:contrast=1.06:brightness=0.01',
            'cinema' => 'curves=preset=medium_contrast,colorbalance=rs=-0.02:bs=0.05:rh=0.04:bh=-0.03,eq=saturation=0.92',
            'pb' => 'hue=s=0,eq=contrast=1.12',
            default => 'eq=saturation=1.05:contrast=1.03',
        };
    }
}
