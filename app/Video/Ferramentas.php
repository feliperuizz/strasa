<?php

namespace App\Video;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Executa FFmpeg, FFprobe, Claude e Python sem shell (argumentos em lista:
 * nada do que vem do usuário ou do Claude é interpretado pelo shell).
 *
 * Cuidados de servidor compartilhado embutidos aqui:
 *  - prioridade baixa (nice/ionice) no Linux, para o site continuar rápido;
 *  - limite de threads no FFmpeg;
 *  - timeout em todo processo (um FFmpeg travado não segura a fila para sempre);
 *  - saída lida em fluxo (progresso), sem acumular GBs na memória do PHP.
 */
class Ferramentas
{
    public static function windows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }

    /** proc_open é o que o Symfony Process usa. Muitos cPanels desligam. */
    public static function podeExecutar(): bool
    {
        if (! function_exists('proc_open')) {
            return false;
        }

        $desligadas = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return ! in_array('proc_open', $desligadas, true);
    }

    public static function localizar(string $binario): ?string
    {
        if (str_contains($binario, '/') || str_contains($binario, '\\')) {
            return is_file($binario) ? $binario : null;
        }

        return (new ExecutableFinder)->find($binario);
    }

    private static function prioridade(): array
    {
        if (self::windows()) {
            return [];
        }

        static $prefixo = null;

        if ($prefixo === null) {
            $prefixo = [];
            $nice = (int) config('video.ferramentas.nice', 15);

            if ($nice > 0 && ($bin = self::localizar('nice'))) {
                $prefixo = [$bin, '-n', (string) $nice];
            }

            if ($ionice = self::localizar('ionice')) {
                $prefixo = array_merge($prefixo, [$ionice, '-c', '3']);
            }
        }

        return $prefixo;
    }

    /**
     * Roda um comando. $aoSair recebe cada pedaço de stdout (progresso).
     * Devolve [stdout, stderr]. Lança FalhaDeFerramenta se sair com erro.
     */
    public static function rodar(array $comando, ?string $cwd = null, int $timeout = 600, ?callable $aoSair = null, array $env = [], bool $baixaPrioridade = true, ?string $entrada = null): array
    {
        if (! self::podeExecutar()) {
            throw new FalhaDeFerramenta('O PHP deste servidor está com proc_open desligado (disable_functions). Sem ele não dá para chamar o FFmpeg. Veja leia/estudio-de-video.md.');
        }

        $cmd = $baixaPrioridade ? array_merge(self::prioridade(), $comando) : $comando;
        $processo = new Process($cmd, $cwd, $env ? array_merge(self::ambienteBase(), $env) : null, $entrada, $timeout > 0 ? $timeout : null);

        $stdout = '';
        $stderr = '';

        try {
            $processo->run(function ($tipo, $buffer) use (&$stdout, &$stderr, $aoSair) {
                if ($tipo === Process::OUT) {
                    if ($aoSair) {
                        $aoSair($buffer);
                    }
                    // Guarda só o fim: o stdout do FFmpeg com -progress é infinito.
                    $stdout = substr($stdout.$buffer, -400000);
                } else {
                    $stderr = substr($stderr.$buffer, -20000);
                }
            });
        } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException) {
            throw new FalhaDeFerramenta(basename($comando[0]).' passou do tempo limite ('.$timeout.' s) e foi interrompido.');
        }

        if (! $processo->isSuccessful()) {
            throw new FalhaDeFerramenta(
                basename($comando[0]).' falhou (código '.$processo->getExitCode().'): '.self::resumirErro($stderr ?: $stdout),
                $stderr
            );
        }

        return [$stdout, $stderr];
    }

    /** Variáveis que o processo filho precisa herdar (PATH, HOME, SystemRoot no Windows). */
    private static function ambienteBase(): array
    {
        $base = [];

        foreach (['PATH', 'Path', 'HOME', 'USERPROFILE', 'SystemRoot', 'TEMP', 'TMP', 'LANG', 'APPDATA', 'LOCALAPPDATA'] as $chave) {
            $valor = getenv($chave);
            if ($valor !== false) {
                $base[$chave] = $valor;
            }
        }

        return $base;
    }

    public static function resumirErro(string $saida): string
    {
        $linhas = array_values(array_filter(array_map('trim', explode("\n", $saida)), fn ($l) => $l !== ''));

        // A causa costuma estar numa linha "Error/Invalid/No such…" no meio; o fim é genérico.
        $causas = array_values(array_filter($linhas, fn ($l) => preg_match('/error|invalid|no such|cannot|unable|not found|failed to|does not|unknown|out of memory|killed/i', $l)
            && ! preg_match('/Conversion failed|Terminating thread|Nothing was written|Task finished with error/i', $l)));

        $escolhidas = $causas ? array_slice(array_unique($causas), 0, 4) : array_slice($linhas, -4);

        return mb_substr(implode(' | ', $escolhidas), 0, 900);
    }

    /* FFmpeg ----------------------------------------------------------- */

    public static function ffmpegBin(): string
    {
        return (string) config('video.ferramentas.ffmpeg', 'ffmpeg');
    }

    public static function ffprobeBin(): string
    {
        return (string) config('video.ferramentas.ffprobe', 'ffprobe');
    }

    public static function threads(): string
    {
        return (string) max(1, (int) config('video.ferramentas.threads', 2));
    }

    /**
     * FFmpeg com progresso. $duracao é a duração esperada da saída (s) para
     * converter o out_time em percentual; $progresso recebe 0..1.
     */
    public static function ffmpeg(array $args, ?string $cwd = null, ?float $duracao = null, ?callable $progresso = null, ?int $timeout = null): string
    {
        $comando = array_merge([self::ffmpegBin(), '-hide_banner', '-nostdin', '-y', '-filter_threads', self::threads()], self::limitarThreads($args));

        $ultimo = 0.0;
        $aoSair = null;

        if ($progresso && $duracao > 0) {
            array_splice($comando, 4, 0, ['-progress', 'pipe:1', '-nostats']);
            $aoSair = function (string $buffer) use ($duracao, $progresso, &$ultimo) {
                if (preg_match_all('/out_time_(?:us|ms)=(\d+)/', $buffer, $m)) {
                    $t = (int) end($m[1]) / 1_000_000;
                    $fracao = min(1.0, $t / $duracao);
                    // Não martela o banco: só avisa a cada 2%.
                    if ($fracao - $ultimo >= 0.02) {
                        $ultimo = $fracao;
                        $progresso($fracao);
                    }
                }
            };
        }

        [, $stderr] = self::rodar($comando, $cwd, $timeout ?? (int) config('video.limites.ffmpeg_timeout', 3000), $aoSair);

        return $stderr;
    }

    /**
     * Threads limitadas em TODO comando: decodificador (antes de cada -i) e
     * codificador (antes da saída). Num servidor de 56 núcleos o FFmpeg abre
     * dezenas de threads sozinho, e a hospedagem compartilhada limita threads
     * por conta: "ff_frame_thread_encoder_init failed" / "Resource temporarily
     * unavailable". Também deixa folga de CPU para o site.
     */
    private static function limitarThreads(array $args): array
    {
        $t = self::threads();
        $saida = [];

        foreach ($args as $i => $arg) {
            if ($arg === '-i' && ($args[$i - 2] ?? null) !== '-threads') {
                array_push($saida, '-threads', $t);
            }
            $saida[] = $arg;
        }

        // Antes do arquivo de saída (último argumento), se ninguém pôs.
        $ultimo = array_pop($saida);
        $temNaSaida = false;
        for ($i = count($saida) - 1; $i >= 0 && $saida[$i] !== '-i'; $i--) {
            if ($saida[$i] === '-threads') {
                $temNaSaida = true;
                break;
            }
        }
        if (! $temNaSaida) {
            array_push($saida, '-threads', $t);
        }
        $saida[] = $ultimo;

        return $saida;
    }

    /** ffprobe em JSON. */
    public static function sondar(string $arquivo): array
    {
        [$saida] = self::rodar([
            self::ffprobeBin(), '-v', 'error', '-print_format', 'json',
            '-show_format', '-show_streams', $arquivo,
        ], null, 120, null, [], false);

        $dados = json_decode($saida, true);

        if (! is_array($dados)) {
            throw new FalhaDeFerramenta('Não consegui ler o arquivo '.basename($arquivo).' (o ffprobe não reconheceu o formato).');
        }

        return $dados;
    }

    /** Versão principal do FFmpeg (builds "git"/N-xxxx contam como novos). */
    public static function versaoFfmpeg(): int
    {
        static $versao = null;

        if ($versao === null) {
            try {
                [$saida] = self::rodar([self::ffmpegBin(), '-hide_banner', '-version'], null, 30, null, [], false);
                $versao = preg_match('/ffmpeg version n?(\d+)\./i', $saida, $m) ? (int) $m[1] : 99;
            } catch (\Throwable) {
                $versao = 0;
            }
        }

        return $versao;
    }

    /** Argumentos para ler o grafo de filtros de um arquivo (evita linha de comando gigante). */
    public static function argGrafo(string $arquivo): array
    {
        return self::versaoFfmpeg() >= 7 ? ['-/filter_complex', $arquivo] : ['-filter_complex_script', $arquivo];
    }

    /** Filtros disponíveis no FFmpeg instalado (para o diagnóstico e para fallbacks). */
    public static function filtros(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        try {
            [$saida] = self::rodar([self::ffmpegBin(), '-hide_banner', '-filters'], null, 30, null, [], false);
        } catch (\Throwable) {
            return $cache = [];
        }

        preg_match_all('/^\s*[TSC.]{2,3}\s+(\w+)\s/m', $saida, $m);

        return $cache = array_flip($m[1]);
    }

    public static function temFiltro(string $nome): bool
    {
        return isset(self::filtros()[$nome]);
    }

    /** Escapa um valor para dentro de um filtro (filtergraph) do FFmpeg. */
    public static function escFiltro(string $valor): string
    {
        return str_replace(['\\', ':', "'", ',', ';', '[', ']'], ['\\\\', '\\:', "\\'", '\\,', '\\;', '\\[', '\\]'], $valor);
    }

    /**
     * Caminho de $alvo relativo à pasta $de. Usado dentro de filtros do FFmpeg,
     * onde "C:" do Windows exige dois níveis de escape; relativo não tem ":".
     */
    public static function relativo(string $de, string $alvo): string
    {
        $de = explode('/', trim(str_replace('\\', '/', realpath($de) ?: $de), '/'));
        $para = explode('/', trim(str_replace('\\', '/', realpath($alvo) ?: $alvo), '/'));

        if (strcasecmp($de[0] ?? '', $para[0] ?? '') !== 0) {
            return str_replace('\\', '/', $alvo); // outro disco no Windows: não há relativo
        }

        while ($de && $para && strcasecmp($de[0], $para[0]) === 0) {
            array_shift($de);
            array_shift($para);
        }

        return str_repeat('../', count($de)).implode('/', $para);
    }

    /** Segundos com 3 casas, ponto decimal (independente do locale). */
    public static function s(float $segundos): string
    {
        return number_format(max(0, $segundos), 3, '.', '');
    }
}
