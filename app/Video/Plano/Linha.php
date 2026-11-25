<?php

namespace App\Video\Plano;

/**
 * Linha do tempo de um plano: onde cada segmento cai no vídeo final.
 *
 * Os tempos do plano ficam no TEMPO DO ARQUIVO DE ORIGEM (regra do pacote
 * de skills: sobrevivem a mudanças no corte). Esta classe converte:
 *   origem (arquivo f3, 12,4 s) → sequência (0:05,2 do vídeo final)  e volta.
 *
 * Transição com duração sobrepõe: o segmento i começa d segundos antes do
 * fim do anterior (é assim que o xfade do FFmpeg funciona). Tudo em quadros
 * inteiros (30 fps) numa função só, para vídeo, legenda e som baterem.
 */
class Linha
{
    /** @var array<int, array> */
    public array $itens = [];

    public float $total = 0.0;

    public function __construct(array $segmentos, public readonly int $fps = 30)
    {
        $cursor = 0.0;

        foreach (array_values($segmentos) as $i => $seg) {
            $velocidade = (float) ($seg['velocidade'] ?? 1.0);
            $bruto = $seg['tipo'] === 'clip'
                ? ((float) $seg['fim'] - (float) $seg['inicio']) / max(0.01, $velocidade)
                : (float) $seg['duracao'];
            $dur = $this->quadro($bruto);

            $trans = $i === 0 ? 'corte' : ($seg['transicao'] ?? 'corte');
            $transDur = $trans === 'corte' ? 0.0 : $this->quadro(min((float) ($seg['transicao_duracao'] ?? 0.3), $dur / 2, $this->itens[$i - 1]['dur'] / 2));

            $inicioSeq = max(0.0, $cursor - $transDur);

            $this->itens[] = $seg + [
                'velocidade' => $velocidade,
                'dur' => $dur,
                'seq_inicio' => $this->quadro($inicioSeq),
                'seq_fim' => $this->quadro($inicioSeq + $dur),
                'trans' => $trans,
                'trans_dur' => $transDur,
            ];

            $cursor = $inicioSeq + $dur;
        }

        $this->total = $this->quadro($cursor);
    }

    public function quadro(float $segundos): float
    {
        return round(round($segundos * $this->fps) / $this->fps, 4);
    }

    /** Tempo de origem → tempo no vídeo final. Null se o trecho foi cortado. */
    public function paraSequencia(string $arquivo, float $t): ?float
    {
        foreach ($this->itens as $item) {
            if ($item['tipo'] === 'clip' && $item['arquivo'] === $arquivo
                && $t >= $item['inicio'] - 0.0005 && $t <= $item['fim'] + 0.0005) {
                return round($item['seq_inicio'] + ($t - $item['inicio']) / $item['velocidade'], 4);
            }
        }

        return null;
    }

    /** Tempo no vídeo final → [arquivo, tempo de origem, id do segmento]. */
    public function paraOrigem(float $tSeq): ?array
    {
        // De trás para frente: na sobreposição de uma transição vale o segmento que está entrando.
        foreach (array_reverse($this->itens) as $item) {
            if ($tSeq >= $item['seq_inicio'] && $tSeq < $item['seq_fim'] + 0.0005) {
                if ($item['tipo'] !== 'clip') {
                    return ['arquivo' => $item['arquivo'] ?? $item['tipo'], 't' => null, 'segmento' => $item['id']];
                }

                return [
                    'arquivo' => $item['arquivo'],
                    't' => round($item['inicio'] + ($tSeq - $item['seq_inicio']) * $item['velocidade'], 3),
                    'segmento' => $item['id'],
                ];
            }
        }

        return null;
    }

    public function segmento(string $id): ?array
    {
        foreach ($this->itens as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }

        return null;
    }

    /** Instantes dos cortes/transições no vídeo final (para câmera e som). */
    public function cortes(): array
    {
        return array_map(fn ($i) => ['t' => $i['seq_inicio'], 'trans' => $i['trans'], 'id' => $i['id']], array_slice($this->itens, 1));
    }

    /**
     * Resolve uma âncora de tempo do plano para segundos do vídeo final:
     *   2.5                         → 2,5 s da sequência
     *   {"arquivo":"f3","t":12.4}   → tempo de origem (some se foi cortado)
     *   {"segmento":"s04","t":0.5}  → 0,5 s depois do início do segmento
     *   "inicio" | "fim"
     */
    public function resolver(mixed $ancora, float $duracao = 0.0): ?float
    {
        if (is_numeric($ancora)) {
            return max(0.0, min((float) $ancora, $this->total));
        }

        if ($ancora === 'inicio') {
            return 0.0;
        }

        if ($ancora === 'fim') {
            return max(0.0, $this->total - $duracao);
        }

        if (! is_array($ancora)) {
            return null;
        }

        if (isset($ancora['segmento'])) {
            $seg = $this->segmento((string) $ancora['segmento']);

            return $seg ? min($seg['seq_inicio'] + max(0.0, (float) ($ancora['t'] ?? 0)), $this->total) : null;
        }

        if (isset($ancora['arquivo'], $ancora['t'])) {
            return $this->paraSequencia((string) $ancora['arquivo'], (float) $ancora['t']);
        }

        return null;
    }

    public static function formatar(float $segundos): string
    {
        $segundos = max(0, $segundos);

        return sprintf('%d:%04.1f', intdiv((int) $segundos, 60), fmod($segundos, 60));
    }
}
