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
