<?php

namespace App\Video\Render;

use App\Video\Ferramentas;
use Illuminate\Support\Facades\File;

/**
 * Kit de efeitos sonoros sintetizado pelo próprio FFmpeg (aevalsrc).
 *
 * Sem download e sem dúvida de licença: o som é gerado no servidor por uma
 * fórmula. Não é um banco de som profissional, mas cobre o vocabulário do
 * pacote de skills (whoosh, impacto, pop, clique, ding, riser, glitch,
 * tique). Para trocar por sons melhores, coloque um WAV com o mesmo nome na
 * pasta (config video.ferramentas.sons) — o arquivo da pasta tem prioridade.
 *
 * "pico" = segundos do início do arquivo até o ponto alto. A mixagem alinha
 * o pico do whoosh no quadro da troca (regra de sincronia da edit-sfx).
 */
class Sons
{
    public const KIT = [
        'whoosh' => ['d' => 0.7, 'pico' => 0.42, 'nivel' => 0.55,
            'expr' => '(random(0)*2-1)*pow(sin(PI*t/0.7),3)', 'filtro' => 'highpass=f=250,lowpass=f=3200,aecho=0.6:0.4:25:0.25'],
        'swoosh' => ['d' => 0.38, 'pico' => 0.22, 'nivel' => 0.45,
            'expr' => '(random(0)*2-1)*pow(sin(PI*t/0.38),2)', 'filtro' => 'highpass=f=900,lowpass=f=7000'],
        'pop' => ['d' => 0.14, 'pico' => 0.0, 'nivel' => 0.6,
            'expr' => 'sin(2*PI*(260+900*exp(-45*t))*t)*exp(-24*t)', 'filtro' => 'lowpass=f=5000'],
        'clique' => ['d' => 0.04, 'pico' => 0.0, 'nivel' => 0.5,
            'expr' => '(random(0)*2-1)*exp(-260*t)', 'filtro' => 'highpass=f=1800'],
        'impacto' => ['d' => 1.1, 'pico' => 0.0, 'nivel' => 0.85,
            'expr' => '0.9*sin(2*PI*(48+60*exp(-12*t))*t)*exp(-3.6*t)+0.45*(random(0)*2-1)*exp(-22*t)', 'filtro' => 'lowpass=f=2600'],
        'ding' => ['d' => 1.3, 'pico' => 0.0, 'nivel' => 0.4,
            'expr' => '0.5*sin(2*PI*1318.5*t)*exp(-3.2*t)+0.22*sin(2*PI*2637*t)*exp(-5*t)+0.14*sin(2*PI*1975.5*t)*exp(-4*t)', 'filtro' => 'anull'],
        'riser' => ['d' => 1.4, 'pico' => 1.4, 'nivel' => 0.4,
            'expr' => '0.55*(random(0)*2-1)*pow(t/1.4,2.2)+0.3*sin(2*PI*(180+720*t/1.4)*t)*pow(t/1.4,2)', 'filtro' => 'highpass=f=200'],
        'glitch' => ['d' => 0.32, 'pico' => 0.0, 'nivel' => 0.32,
            'expr' => '0.5*sgn(sin(2*PI*(220+660*floor(mod(t*37,4)))*t))*lt(mod(t,0.06),0.035)', 'filtro' => 'lowpass=f=6000'],
        'tique' => ['d' => 0.045, 'pico' => 0.0, 'nivel' => 0.32,
            'expr' => 'sin(2*PI*2300*t)*exp(-110*t)', 'filtro' => 'anull'],
    ];

    public static function pasta(): string
    {
        return rtrim(str_replace('\\', '/', (string) config('video.ferramentas.sons')), '/');
    }

    public static function caminho(string $nome): ?string
    {
        $arquivo = self::pasta()."/{$nome}.wav";

        return is_file($arquivo) ? $arquivo : null;
    }

    public static function info(string $nome): array
    {
        return self::KIT[$nome] ?? self::KIT['pop'];
    }

    /** Gera os que faltam. $forcar regrava todos. Devolve os nomes gerados. */
    public static function gerar(bool $forcar = false): array
    {
        File::ensureDirectoryExists(self::pasta(), 0775);
        $gerados = [];

        foreach (self::KIT as $nome => $som) {
            $saida = self::pasta()."/{$nome}.wav";

            if (! $forcar && is_file($saida)) {
                continue;
            }

            Ferramentas::ffmpeg([
                '-f', 'lavfi', '-i', sprintf("aevalsrc='%s':s=48000:d=%s", $som['expr'], $som['d']),
                '-af', $som['filtro'].',afade=t=out:st='.max(0, $som['d'] - 0.03).':d=0.03,dynaudnorm=p=0.9:m=10,aformat=channel_layouts=stereo',
                '-c:a', 'pcm_s16le', $saida,
            ], null, null, null, 60);

            $gerados[] = $nome;
        }

        return $gerados;
    }
}
