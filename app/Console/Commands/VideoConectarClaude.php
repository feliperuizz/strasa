<?php

namespace App\Console\Commands;

use App\Video\Claude\ClaudeCli;
use App\Video\Ferramentas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Conecta a conta da assinatura do Claude ao servidor.
 *
 * Normalmente chamado pelo scripts/video/logar-claude.sh, logo depois do
 * `claude setup-token` (que dá um token de longa duração da SUA conta).
 * Aqui o token é testado de verdade (uma pergunta mínima ao Claude) e só
 * então gravado no .env — token errado não chega a ser salvo.
 *
 *   php artisan video:conectar-claude                 pede o token (oculto)
 *   php artisan video:conectar-claude --login-local   usa o login feito com `claude` → /login nesta conta
 */
class VideoConectarClaude extends Command
{
    protected $signature = 'video:conectar-claude {--bin= : Caminho do claude} {--login-local : Usar as credenciais salvas em ~/.claude em vez de token} {--token= : Token (evite: fica no histórico do shell)}';

    protected $description = 'Conecta a conta da assinatura do Claude ao Estúdio de vídeo (testa e grava no .env)';

    public function handle(): int
    {
        $bin = $this->option('bin') ?: (ClaudeCli::binario() ?? (is_file(getenv('HOME').'/.local/bin/claude') ? getenv('HOME').'/.local/bin/claude' : null));

        if (! $bin || ! is_file($bin)) {
            $this->error('Claude Code não encontrado. Rode antes: bash scripts/video/instalar-servidor.sh');

            return self::FAILURE;
        }

        $token = null;
        if (! $this->option('login-local')) {
            $token = trim((string) ($this->option('token') ?: $this->secret('Cole aqui o token gerado (começa com sk-ant-; não aparece na tela)')));
            if (! preg_match('/^sk-ant-[A-Za-z0-9_\-]{20,}$/', $token)) {
                $this->error('Isso não parece um token do Claude (deve começar com "sk-ant-"). Nada foi gravado.');

                return self::FAILURE;
            }
        }

        // Testa com os valores novos antes de gravar.
        config([
            'video.claude.binario' => $bin,
            'video.claude.token' => $token,
            'video.claude.home' => getenv('HOME') ?: config('video.claude.home'),
            'video.claude.ativo' => true,
        ]);

        $this->info('Testando a conexão com o Claude…');
        try {
            $r = ClaudeCli::testar();
        } catch (\Throwable $e) {
            $this->error('Não funcionou: '.$e->getMessage());
            $this->line('Nada foi gravado. Gere um token novo (claude setup-token) e tente de novo.');

            return self::FAILURE;
        }
        $this->info('Claude respondeu: "'.mb_substr($r['texto'], 0, 40).'" — conexão ok.');

        $this->gravarEnv([
            'VIDEO_ATIVO' => 'true',
            'VIDEO_CLAUDE_ATIVO' => 'true',
            'VIDEO_CLAUDE_BIN' => $bin,
            'VIDEO_CLAUDE_HOME' => (string) (getenv('HOME') ?: ''),
            'VIDEO_CLAUDE_TOKEN' => (string) $token,
        ]);

        if (app()->configurationIsCached()) {
            Artisan::call('config:cache');
            $this->line('config:cache refeito.');
        }

        $this->newLine();
        $this->info('Pronto: o Estúdio de vídeo vai editar com a sua conta do Claude.');
        $this->line('O token vale por cerca de 1 ano. Para trocar de conta ou renovar: bash scripts/video/logar-claude.sh');

        return self::SUCCESS;
    }

    /** Troca (ou acrescenta) as chaves no .env, sem mexer no resto. */
    private function gravarEnv(array $valores): void
    {
        $arquivo = base_path('.env');
        $conteudo = is_file($arquivo) ? (string) file_get_contents($arquivo) : '';
        @copy($arquivo, $arquivo.'.antes-do-claude');

        foreach ($valores as $chave => $valor) {
            $linha = $chave.'='.(preg_match('/\s/', $valor) ? '"'.$valor.'"' : $valor);
            $padrao = '/^'.preg_quote($chave, '/').'=.*$/m';
            $conteudo = preg_match($padrao, $conteudo)
                ? preg_replace($padrao, str_replace('$', '\$', $linha), $conteudo)
                : rtrim($conteudo)."\n".$linha."\n";
        }

        file_put_contents($arquivo, $conteudo);
        @chmod($arquivo, 0600);
        $this->line('Gravado no .env (cópia anterior: .env.antes-do-claude).');
    }
}
