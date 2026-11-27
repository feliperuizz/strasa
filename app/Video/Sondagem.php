<?php

namespace App\Video;

/**
 * Lê o ffprobe de um arquivo e devolve só o que o estúdio usa.
 *
 * Armadilhas de iPhone tratadas aqui (do pacote de skills, regras §2):
 * - vídeo gravado em pé vem com rotação 90° nos metadados: largura/altura
 *   "de exibição" trocam de lugar;
 * - HEVC 10-bit HDR (HLG / Dolby Vision / PQ): marcado como hdr, e o motor
 *   converte para SDR BT.709 antes de qualquer escala — senão fica lavado
 *   ou estourado no Instagram.
 */
class Sondagem
{
    private const CODECS_IMAGEM = ['mjpeg', 'png', 'webp', 'bmp', 'tiff', 'gif', 'heif', 'hevc_image'];

    public static function arquivo(string $caminho): array
    {
        return self::interpretar(Ferramentas::sondar($caminho));
    }

    public static function interpretar(array $probe): array
    {
        $streams = $probe['streams'] ?? [];
        $video = collect($streams)->first(fn ($s) => ($s['codec_type'] ?? '') === 'video' && ! (($s['disposition']['attached_pic'] ?? 0) == 1));
        $audio = collect($streams)->first(fn ($s) => ($s['codec_type'] ?? '') === 'audio');
        $duracao = (float) ($probe['format']['duration'] ?? $video['duration'] ?? $audio['duration'] ?? 0);

        if (! $video && ! $audio) {
            throw new FalhaDeFerramenta('O arquivo não tem vídeo nem áudio que o FFmpeg reconheça.');
        }

        if (! $video) {
            return [
                'tipo' => 'audio', 'duracao' => round($duracao, 3), 'largura' => null, 'altura' => null,
                'fps' => null, 'rotacao' => 0, 'hdr' => false, 'tem_audio' => true,
            ];
        }

        $rotacao = self::rotacao($video);
        $largura = (int) ($video['width'] ?? 0);
        $altura = (int) ($video['height'] ?? 0);

        if (abs($rotacao) % 180 === 90) {
            [$largura, $altura] = [$altura, $largura];
        }

        $codec = strtolower((string) ($video['codec_name'] ?? ''));
        $quadros = (int) ($video['nb_frames'] ?? 0);
        $ehImagem = in_array($codec, self::CODECS_IMAGEM, true) && ($duracao < 0.1 || $quadros <= 1 || $codec !== 'gif');

        $transfer = strtolower((string) ($video['color_transfer'] ?? ''));
        $hdr = in_array($transfer, ['smpte2084', 'arib-std-b67'], true)
            || collect($video['side_data_list'] ?? [])->contains(fn ($d) => str_contains(strtolower($d['side_data_type'] ?? ''), 'dovi'));

        return [
            'tipo' => $ehImagem ? 'imagem' : 'video',
            'duracao' => $ehImagem ? 0.0 : round($duracao, 3),
            'largura' => $largura,
            'altura' => $altura,
            'fps' => $ehImagem ? null : self::fps($video),
            'rotacao' => $rotacao,
            'hdr' => $hdr,
            'tem_audio' => $audio !== null && ! $ehImagem,
            'codec' => $codec,
            'vfr' => self::vfr($video),
            'gravado_em' => self::gravadoEm($probe, $video),
        ];
    }

    /** Data e hora da gravação (iPhone grava a hora local; outros, creation_time em UTC). */
    private static function gravadoEm(array $probe, array $video): ?string
    {
        $tags = array_change_key_case((array) ($probe['format']['tags'] ?? []));
        $valor = $tags['com.apple.quicktime.creationdate'] ?? $tags['creation_time'] ?? ($video['tags']['creation_time'] ?? null);

        try {
            return $valor ? \Carbon\Carbon::parse($valor)->toIso8601String() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function rotacao(array $video): int
    {
        if (isset($video['tags']['rotate'])) {
            return (int) $video['tags']['rotate'];
        }

        foreach ($video['side_data_list'] ?? [] as $dado) {
            if (isset($dado['rotation'])) {
                return (int) $dado['rotation'];
            }
        }

        return 0;
    }

    private static function fps(array $video): ?float
    {
        foreach (['avg_frame_rate', 'r_frame_rate'] as $campo) {
            $valor = (string) ($video[$campo] ?? '');
            if (preg_match('#^(\d+)/(\d+)$#', $valor, $m) && (int) $m[2] > 0 && (int) $m[1] > 0) {
                return round((int) $m[1] / (int) $m[2], 3);
            }
        }

        return null;
    }

    /** Taxa variável (comum em gravação de tela e alguns Androids): o motor força 30 fps. */
    private static function vfr(array $video): bool
    {
        $r = (string) ($video['r_frame_rate'] ?? '');
        $avg = (string) ($video['avg_frame_rate'] ?? '');

        return $r !== '' && $avg !== '' && $r !== $avg && $avg !== '0/0';
    }
}
