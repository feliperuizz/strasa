<?php

namespace App\Video;

use App\Video\Claude\ClaudeCli;
use App\Video\Plano\Validador;
use App\Video\Render\Montador;
use App\Video\Render\Sons;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Confere se o servidor aguenta o estúdio — e diz como resolver o que faltar.
 * Cada item: [grupo, nome, status (ok|aviso|erro), detalhe, como resolver].
 *
 * `php artisan video:diagnostico` (rápido) ou `--teste` (render de 2 s e
 * uma pergunta real ao Claude).
 */
class Diagnostico
{
    private array $itens = [];

    public function rodar(bool $testes = false): array
    {
        $this->itens = [];
        $this->php();
        $this->ffmpeg();
        $this->recursos();
        $this->fila();
        $this->transcricao();
        $this->narracao();
        $this->motion();
        $this->claude($testes);

        if ($testes) {
            $this->testeDeRender();
        }

        $resumo = [
            'quando' => now()->toIso8601String(),
            'itens' => $this->itens,
            'erros' => count(array_filter($this->itens, fn ($i) => $i[2] === 'erro')),
            'avisos' => count(array_filter($this->itens, fn ($i) => $i[2] === 'aviso')),
        ];

        Cache::put('video:diagnostico', $resumo, now()->addDays(7));

        return $resumo;
    }

    public static function ultimo(): ?array
    {
        return Cache::get('video:diagnostico');
    }

    private function item(string $grupo, string $nome, string $status, string $detalhe, string $resolver = ''): void
    {
        $this->itens[] = [$grupo, $nome, $status, $detalhe, $resolver];
    }

    private function php(): void
    {
        $this->item('PHP', 'proc_open', Ferramentas::podeExecutar() ? 'ok' : 'erro',
            Ferramentas::podeExecutar() ? 'liberado' : 'desligado em disable_functions',
            'No cPanel: MultiPHP INI Editor (ou PHP Selector > Options) e tire proc_open de disable_functions — pelo menos no PHP da linha de comando, que é o que roda a fila.');

        $memoria = ini_get('memory_limit');
        $bytes = $this->bytes((string) $memoria);
        $this->item('PHP', 'memory_limit (CLI)', $bytes === -1 || $bytes >= 512 * 1048576 ? 'ok' : 'aviso', (string) $memoria,
            'O worker usa pouca memória (o peso fica no FFmpeg), mas 512M evita surpresa com planos grandes.');
    }

    private function ffmpeg(): void
    {
        $bin = Ferramentas::localizar(Ferramentas::ffmpegBin());
        $probe = Ferramentas::localizar(Ferramentas::ffprobeBin());

        if (! $bin || ! $probe) {
            $this->item('FFmpeg', 'instalado', 'erro', 'não encontrado ('.Ferramentas::ffmpegBin().')',
                'Rode scripts/video/instalar-servidor.sh (baixa o FFmpeg estático para ~/bin, sem root) e aponte VIDEO_FFMPEG/VIDEO_FFPROBE.');

            return;
        }

        $versao = Ferramentas::versaoFfmpeg();
        $this->item('FFmpeg', 'versão', $versao >= 6 ? 'ok' : 'aviso', $versao >= 99 ? 'build de desenvolvimento' : "$versao.x", 'Use 6 ou mais novo (o estático do instalador é 7.x).');

        foreach ([
            'ass' => ['erro', 'legendas e animações (libass)'],
            'zoompan' => ['erro', 'câmera/zoom'],
            'xfade' => ['erro', 'transições'],
            'loudnorm' => ['erro', 'volume padrão de redes'],
            'sidechaincompress' => ['aviso', 'música abaixando sob a voz'],
            'zscale' => ['aviso', 'vídeo HDR do iPhone (sem ele, cor lavada)'],
            'gradients' => ['aviso', 'fundo animado do cartão final'],
            'whisper' => ['ok', 'transcrição pelo próprio FFmpeg (opcional)'],
        ] as $filtro => [$falta, $para]) {
            $tem = Ferramentas::temFiltro($filtro);
            $this->item('FFmpeg', "filtro $filtro", $tem ? 'ok' : $falta, ($tem ? 'tem' : 'não tem')." — $para",
                $tem ? '' : 'Troque pelo FFmpeg estático do instalador (johnvansickle.com), que vem completo.');
        }

        try {
            [$enc] = Ferramentas::rodar([$bin, '-hide_banner', '-encoders'], null, 30, null, [], false);
            $this->item('FFmpeg', 'codificador H.264', str_contains($enc, 'libx264') ? 'ok' : 'erro', str_contains($enc, 'libx264') ? 'libx264' : 'sem libx264', 'Use um build com libx264 (o estático tem).');
        } catch (\Throwable $e) {
            $this->item('FFmpeg', 'codificador H.264', 'erro', $e->getMessage());
        }

        $fontes = glob(rtrim((string) config('video.ferramentas.fontes'), '/').'/*.ttf') ?: [];
        $this->item('Estúdio', 'fontes', count($fontes) >= 5 ? 'ok' : 'aviso', count($fontes).' arquivos', 'php artisan video:instalar');

        $sons = array_filter(array_keys(Sons::KIT), fn ($s) => Sons::caminho($s));
        $this->item('Estúdio', 'efeitos sonoros', count($sons) === count(Sons::KIT) ? 'ok' : 'aviso', count($sons).' de '.count(Sons::KIT), 'php artisan video:instalar');
    }

    private function recursos(): void
    {
        $raiz = Armazenamento::raiz();
        File::ensureDirectoryExists($raiz, 0775);
        $gravavel = is_writable($raiz);
        $this->item('Servidor', 'pasta do estúdio', $gravavel ? 'ok' : 'erro', $raiz, 'Crie a pasta e dê permissão de escrita ao usuário do PHP (VIDEO_STORAGE_PATH).');

        $livre = Armazenamento::espacoLivreGb();
        $minimo = (float) config('video.limites.disco_minimo_gb', 5);
        $this->item('Servidor', 'disco livre', $livre === null ? 'aviso' : ($livre < $minimo ? 'erro' : ($livre < 20 ? 'aviso' : 'ok')), $livre === null ? 'desconhecido' : "$livre GB",
            '1 min de 4K do iPhone ≈ 400 MB de bruto + ~30% de trabalho. Abaixo do mínimo o estúdio recusa edição nova. A retenção (video:limpar) apaga brutos antigos.');

        if (is_readable('/proc/meminfo')) {
            $info = (string) file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)/', $info, $t);
            preg_match('/MemAvailable:\s+(\d+)/', $info, $a);
            $total = round(((int) ($t[1] ?? 0)) / 1048576, 1);
            $disp = round(((int) ($a[1] ?? 0)) / 1048576, 1);
            $this->item('Servidor', 'memória', $disp < 1 ? 'erro' : ($disp < 2 ? 'aviso' : 'ok'), "{$disp} GB livres de {$total} GB",
                'O render decodifica um segmento por vez (~300–600 MB para 4K HEVC). A transcrição local (faster-whisper small) usa ~1 GB: em VPS pequena, prefira VIDEO_TRANSCRICAO=api.');
        }

        $nucleos = is_readable('/proc/cpuinfo') ? substr_count((string) file_get_contents('/proc/cpuinfo'), 'processor') : null;
        if ($nucleos) {
            $carga = function_exists('sys_getloadavg') ? (sys_getloadavg()[0] ?? 0) : 0;
            $this->item('Servidor', 'CPU', $nucleos < 2 ? 'aviso' : 'ok', "$nucleos núcleos, carga agora ".round($carga, 2).', FFmpeg usando '.Ferramentas::threads().' thread(s)',
                'O render roda com prioridade baixa (nice) e threads limitadas (VIDEO_THREADS) para o site continuar rápido. Final de 60 s em 2 núcleos: ~3–8 min.');
        }

        if (! Ferramentas::windows()) {
            $this->item('Servidor', 'prioridade baixa (nice)', Ferramentas::localizar('nice') ? 'ok' : 'aviso', Ferramentas::localizar('nice') ? 'disponível' : 'sem nice', 'Sem nice o render disputa CPU com o site.');
        }
    }

    private function fila(): void
    {
        try {
            $temTabela = Schema::hasTable('jobs');
        } catch (\Throwable) {
            $temTabela = false;
        }

        $this->item('Fila', 'tabela jobs', $temTabela ? 'ok' : 'erro', $temTabela ? 'existe' : 'não existe', 'php artisan migrate');

        if ($temTabela) {
            $pendentes = DB::table('jobs')->where('queue', 'video')->count();
            $presos = DB::table('jobs')->where('queue', 'video')->whereNotNull('reserved_at')->where('reserved_at', '<', now()->subHours(3)->timestamp)->count();
            $this->item('Fila', 'edições na fila', $presos ? 'aviso' : 'ok', "$pendentes na fila".($presos ? ", $presos reservadas há mais de 3 h" : ''), 'Se ficar parado, confira o cron do schedule:run e o video:vigiar.');
        }

        $marca = Armazenamento::raiz().'/.agendador';
        $ultimo = is_file($marca) ? trim((string) file_get_contents($marca)) : null;
        $rodando = $ultimo && now()->diffInMinutes(\Carbon\Carbon::parse($ultimo)) <= 10;
        $this->item('Fila', 'agendador (cron)', $rodando ? 'ok' : 'erro', $ultimo ? 'último sinal '.\Carbon\Carbon::parse($ultimo)->diffForHumans() : 'nunca rodou',
            'No cPanel > Cron Jobs, a cada minuto: cd /home/USUARIO/public_html/strasa && php artisan schedule:run >> /dev/null 2>&1 — é ele que liga o worker da fila de vídeo.');

        $conexao = config('queue.connections.'.config('video.fila.conexao'));
        $this->item('Fila', 'conexão "video"', $conexao && ($conexao['retry_after'] ?? 0) >= 3600 ? 'ok' : 'erro',
            $conexao ? 'retry_after '.($conexao['retry_after'] ?? '?').' s' : 'não configurada',
            'Precisa de retry_after longo (config/queue.php), senão um render longo roda duas vezes.');
    }

    private function transcricao(): void
    {
        $driver = Transcricao::driver();

        if ($driver === 'nenhum') {
            $this->item('Transcrição', 'motor', 'aviso', 'desligada', 'Sem ela, os estilos de fala saem sem legenda. Instale a local e gratuita: bash scripts/video/instalar-servidor.sh (VIDEO_TRANSCRICAO=faster_whisper, Python 3.9+).');

            return;
        }

        $this->item('Transcrição', "motor ($driver)", Transcricao::disponivel() ? 'ok' : 'erro', Transcricao::disponivel() ? 'configurado' : 'configurado, mas falta algo', match ($driver) {
            'api' => 'Defina VIDEO_TRANSCRICAO_API_KEY (e _API_URL/_API_MODEL se não for a OpenAI).',
            'faster_whisper' => 'Instale: python3 -m pip install --user faster-whisper e confira VIDEO_PYTHON.',
            'ffmpeg' => 'Precisa de FFmpeg 8 com --enable-whisper e VIDEO_WHISPER_CPP_MODEL apontando para um ggml-*.bin.',
            default => '',
        });
    }

    private function motion(): void
    {
        $python = Render\Motion::python();
        $icones = is_dir(Render\Motion::pastaIcones().'/lucide');
        $ok = Render\Motion::disponivel();
        $this->item('Motion', 'componentes animados (Skia)', $ok ? 'ok' : 'aviso',
            $ok ? 'instalado' : (! $python ? 'motor não instalado' : (! $icones ? 'faltam os ícones' : 'falta algo')),
            $ok ? '' : 'Rode de novo: bash scripts/video/instalar-servidor.sh (cria o venv-motion, baixa os ícones e mostra VIDEO_MOTION_PYTHON / VIDEO_ICONES_PATH para o .env). Sem ele, os vídeos saem só com as animações simples.');
    }

    private function narracao(): void
    {
        foreach (['kokoro' => 'Kokoro (vozes Dora, Alex, Santa)', 'chatterbox' => 'Chatterbox (voz Humana+)'] as $motor => $nome) {
            $python = Narracao::python($motor);

            if ($motor === 'chatterbox' && ! $python) {
                $this->item('Narração', $nome, 'ok', 'opcional, não instalado (pesado: ~4 GB; instale só se o servidor aguentar)');

                continue;
            }

            $pronto = Narracao::motorPronto($motor);
            $this->item('Narração', $nome, $pronto ? 'ok' : 'aviso', $pronto ? 'instalado' : ($python ? 'falta o modelo de voz' : 'não instalado'),
                $pronto ? '' : 'Rode de novo: bash scripts/video/instalar-servidor.sh (instala a voz e mostra as linhas do .env).');
        }

        if (Narracao::disponivel()) {
            $faltam = array_keys(array_filter(Narracao::disponiveis(), fn ($v, $id) => ! Narracao::amostra($id), ARRAY_FILTER_USE_BOTH));
            if ($faltam) {
                $this->item('Narração', 'amostras das vozes', 'aviso', 'faltam: '.implode(', ', $faltam), 'php artisan video:vozes');
            }
        }
    }

    private function claude(bool $testar): void
    {
        if (! config('video.claude.ativo')) {
            $this->item('Claude', 'Claude no servidor', 'aviso', 'desligado (VIDEO_CLAUDE_ATIVO=false)', 'Sem o Claude, as edições usam o piloto automático (regras) e o ajuste por texto fica indisponível.');

            return;
        }

        $bin = ClaudeCli::binario();
        if (! $bin) {
            $this->item('Claude', 'Claude Code instalado', config('video.claude.piloto_reserva') ? 'aviso' : 'erro', 'não encontrado ('.config('video.claude.binario').')',
                'No SSH: curl -fsSL https://claude.ai/install.sh | bash (instala em ~/.local/bin, sem root) e aponte VIDEO_CLAUDE_BIN para o caminho completo.');

            return;
        }

        try {
            [$versao] = Ferramentas::rodar([$bin, '--version'], null, 30, null, config('video.claude.home') ? ['HOME' => config('video.claude.home')] : [], false);
            $this->item('Claude', 'Claude Code instalado', 'ok', trim($versao).' em '.$bin);
        } catch (\Throwable $e) {
            $this->item('Claude', 'Claude Code instalado', 'erro', $e->getMessage());
        }

        $token = filled(config('video.claude.token'));
        $home = (string) config('video.claude.home');
        $credenciais = $home && (is_file("$home/.claude/.credentials.json") || is_file("$home/.claude.json"));
        $this->item('Claude', 'login', $token || $credenciais ? 'ok' : 'erro', $token ? 'token configurado' : ($credenciais ? 'login do usuário '.$home : 'sem login'),
            'Por SSH no servidor: bash scripts/video/logar-claude.sh (gera o token com a sua conta da assinatura e grava no .env).');

        $conexao = @fsockopen('ssl://api.anthropic.com', 443, $errno, $errstr, 6);
        if ($conexao) {
            fclose($conexao);
        }
        $this->item('Claude', 'saída para api.anthropic.com:443', $conexao ? 'ok' : 'erro', $conexao ? 'conecta' : "bloqueado ($errstr)",
            'O firewall (CSF/iptables) está barrando a saída. Libere TCP_OUT 443 para o usuário do PHP. O estúdio não precisa abrir NENHUMA porta de entrada.');

        if ($testar && $conexao) {
            try {
                $r = ClaudeCli::testar();
                $this->item('Claude', 'teste real', str_contains(mb_strtolower($r['texto']), 'ok') ? 'ok' : 'aviso', 'respondeu: '.mb_substr($r['texto'], 0, 60));
            } catch (\Throwable $e) {
                $this->item('Claude', 'teste real', 'erro', mb_substr($e->getMessage(), 0, 300));
            }
        }
    }

    /** Render de 2 s com cartão, título, legenda e som: prova fonte, libass, x264, aac, loudnorm. */
    private function testeDeRender(): void
    {
        $dir = storage_path('app/video-diagnostico');
        File::ensureDirectoryExists($dir, 0775);
        $saida = "$dir/teste.mp4";
        @unlink($saida);
        File::deleteDirectory("$dir/cache");

        try {
            $plano = (new Validador)->normalizar([
                'segmentos' => [['arquivo' => 'cartao', 'duracao' => 2.0]],
                'elementos' => [['tipo' => 'titulo', 'texto' => 'Estúdio OK ção', 'em' => 0.1, 'duracao' => 1.8], ['tipo' => 'sticker', 'forma' => 'estrela', 'em' => 0.3, 'duracao' => 1.5]],
            ], [], ['fala' => false]);

            $inicio = microtime(true);
            (new Montador($plano, [], Identidade::de(new \App\Models\VideoProfile), $dir, 'final', 1080, 1920, [], null, null,
                glob(rtrim((string) config('video.ferramentas.fontes'), '/').'/*.ttf') ?: []))->renderizar($saida);
            $info = Sondagem::arquivo($saida);
            $this->item('Teste', 'render de 2 s (1080x1920)', abs($info['duracao'] - 2) < 0.3 ? 'ok' : 'aviso', sprintf('%.1f s de vídeo em %.1f s', $info['duracao'], microtime(true) - $inicio));
        } catch (\Throwable $e) {
            $this->item('Teste', 'render de 2 s', 'erro', mb_substr($e->getMessage(), 0, 400));
        }
    }

    private function bytes(string $valor): int
    {
        if ($valor === '-1') {
            return -1;
        }

        $n = (int) $valor;

        return match (strtoupper(substr($valor, -1))) {
            'G' => $n * 1024 ** 3, 'M' => $n * 1024 ** 2, 'K' => $n * 1024, default => $n,
        };
    }
}
