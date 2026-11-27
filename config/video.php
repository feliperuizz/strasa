<?php

/*
|--------------------------------------------------------------------------
| Estúdio de vídeo
|--------------------------------------------------------------------------
| Editor de vídeo por cliente: o Claude (CLI no servidor) decide a edição,
| o FFmpeg executa. Tudo aqui é lido do .env — lembre do
| `php artisan config:cache` depois de mudar (o deploy já roda).
|
| Guia de instalação e limitações do servidor: leia/estudio-de-video.md
| Conferir se está tudo pronto: php artisan video:diagnostico
*/

return [

    // Desliga o estúdio inteiro (menu some, rotas respondem 404).
    'ativo' => (bool) env('VIDEO_ATIVO', true),

    /*
    | Onde ficam brutos, arquivos de trabalho e renders. Fica FORA do bucket:
    | o FFmpeg precisa de arquivo local. Em produção, prefira uma pasta fora
    | do projeto (ex.: /home/cons1766/strasa-video), que não fica dentro do
    | document root e não some no `git reset --hard` do deploy.
    */
    'armazenamento' => env('VIDEO_STORAGE_PATH', storage_path('app/video')),

    'ferramentas' => [
        'ffmpeg' => env('VIDEO_FFMPEG', 'ffmpeg'),
        'ffprobe' => env('VIDEO_FFPROBE', 'ffprobe'),
        // Prioridade baixa para o site continuar respondendo enquanto renderiza.
        // Só no Linux; no Windows (dev) é ignorado.
        'nice' => (int) env('VIDEO_NICE', 15),
        // Threads do FFmpeg. Numa VPS com 2–4 núcleos, 2 deixa folga para o site.
        'threads' => (int) env('VIDEO_THREADS', 2),
        'fontes' => env('VIDEO_FONTS_PATH', storage_path('app/video-fontes')),
        'sons' => env('VIDEO_SFX_PATH', storage_path('app/video-sons')),
    ],

    /*
    | Fila própria. A fila "database" padrão tem retry_after de 90 s: um
    | render de 10 min seria dado como perdido e rodaria de novo, em dobro.
    | A conexão "video" (config/queue.php) tem retry_after longo e um worker
    | só dela (routes/console.php), que nunca disputa com convites e avisos.
    */
    'fila' => [
        'conexao' => env('VIDEO_QUEUE_CONNECTION', 'video'),
        'nome' => 'video',
        // Quantas edições processam ao mesmo tempo no servidor inteiro.
        'simultaneas' => (int) env('VIDEO_SIMULTANEAS', 1),
        // Sem sinal de vida por este tempo, a edição é dada como interrompida.
        'sem_sinal_minutos' => (int) env('VIDEO_SEM_SINAL_MINUTOS', 20),
    ],

    'limites' => [
        // Por arquivo enviado e por edição (MB).
        'arquivo_mb' => (int) env('VIDEO_MAX_ARQUIVO_MB', 2048),
        'edicao_mb' => (int) env('VIDEO_MAX_EDICAO_MB', 6144),
        'arquivos_por_edicao' => (int) env('VIDEO_MAX_ARQUIVOS', 40),
        // Duração somada dos brutos (min). Transcrição e análise crescem com isso.
        'brutos_minutos' => (int) env('VIDEO_MAX_MINUTOS', 20),
        // Estilos longos (vlog): mais material e mais arquivos por edição.
        'brutos_minutos_longo' => (int) env('VIDEO_MAX_MINUTOS_LONGO', 90),
        'arquivos_por_edicao_longo' => (int) env('VIDEO_MAX_ARQUIVOS_LONGO', 150),
        // Pedaço do upload. Abaixo do limite de 100 MB do Cloudflare e de
        // qualquer post_max_size razoável; um pedaço que falha é reenviado.
        'pedaco_mb' => (int) env('VIDEO_PEDACO_MB', 8),
        // Abaixo disto de disco livre, não aceita edição nova.
        'disco_minimo_gb' => (float) env('VIDEO_DISCO_MINIMO_GB', 5),
        // Tempo máximo de cada processo do FFmpeg (s).
        'ffmpeg_timeout' => (int) env('VIDEO_FFMPEG_TIMEOUT', 9000),
    ],

    /*
    | Onde o vídeo final fica guardado de vez. O upload e a edição acontecem no
    | servidor (rápido); quando o final fica pronto, ele sobe para este disco
    | (o Google Drive que os anexos já usam) e é apagado do servidor — o player
    | e o download passam a buscar de lá. Vazio = o Drive, se estiver configurado
    | (GOOGLE_DRIVE_REFRESH_TOKEN); "nenhum" = fica no servidor.
    */
    'nuvem' => [
        'disco' => env('VIDEO_NUVEM_DISCO'),
        'pasta' => env('VIDEO_NUVEM_PASTA', 'Estúdio de vídeo'),
    ],

    // Dias até apagar brutos e arquivos de trabalho de edições finalizadas.
    // O vídeo final continua (e, se foi para um card, já está no bucket).
    'retencao_dias' => (int) env('VIDEO_RETENCAO_DIAS', 21),

    /*
    | Claude Code em modo headless (`claude -p`). Login por token de longa
    | duração: rode `claude setup-token` no SEU computador e cole o token em
    | VIDEO_CLAUDE_TOKEN — o servidor não precisa abrir porta nem navegador.
    */
    'claude' => [
        'ativo' => (bool) env('VIDEO_CLAUDE_ATIVO', true),
        'binario' => env('VIDEO_CLAUDE_BIN', 'claude'),
        'token' => env('VIDEO_CLAUDE_TOKEN'),
        // Vazio = o padrão da assinatura.
        'modelo' => env('VIDEO_CLAUDE_MODEL'),
        'max_turnos' => (int) env('VIDEO_CLAUDE_MAX_TURNS', 40),
        'timeout' => (int) env('VIDEO_CLAUDE_TIMEOUT', 1200),
        // Vídeo longo (vlog): ler 15+ min de transcrição e muitas folhas pede mais turnos e tempo.
        'max_turnos_longo' => (int) env('VIDEO_CLAUDE_MAX_TURNS_LONGO', 150),
        'timeout_longo' => (int) env('VIDEO_CLAUDE_TIMEOUT_LONGO', 3600),
        // HOME do usuário dono do login do Claude (cron do cPanel às vezes vem sem HOME).
        'home' => env('VIDEO_CLAUDE_HOME', env('HOME')),
        // Quando a assinatura bate no limite de uso, tenta de novo depois disto.
        'espera_limite_minutos' => (int) env('VIDEO_CLAUDE_ESPERA_LIMITE', 30),
        // O motor é o Claude. Ligue só se quiser que, sem Claude, saia uma
        // edição por regras (piloto automático) em vez de erro.
        'piloto_reserva' => (bool) env('VIDEO_PILOTO_RESERVA', false),
        // Depois do primeiro plano, o Claude olha as animações renderizadas sobre o
        // quadro real do vídeo e corrige (mais uma rodada da assinatura por edição).
        'revisao_visual' => (bool) env('VIDEO_CLAUDE_REVISAO', true),
    ],

    /*
    | Transcrição palavra por palavra (legendas e cortes de fala).
    |  - "api": endpoint compatível com OpenAI (OpenAI, Groq...). Tira o peso
    |    do servidor; recomendado em VPS pequena.
    |  - "faster_whisper": Python local (scripts/video/transcrever.py).
    |  - "ffmpeg": filtro whisper do FFmpeg 8 (whisper.cpp). Sem tempo por
    |    palavra: os tempos são distribuídos dentro de cada trecho.
    |  - "nenhum": estilos de fala ficam sem legenda.
    */
    'transcricao' => [
        'driver' => env('VIDEO_TRANSCRICAO', 'faster_whisper'),
        'idioma' => env('VIDEO_TRANSCRICAO_IDIOMA', 'pt'),
        'api_url' => env('VIDEO_TRANSCRICAO_API_URL', 'https://api.openai.com/v1'),
        'api_chave' => env('VIDEO_TRANSCRICAO_API_KEY'),
        'api_modelo' => env('VIDEO_TRANSCRICAO_API_MODEL', 'whisper-1'),
        'python' => env('VIDEO_PYTHON', 'python3'),
        'modelo_local' => env('VIDEO_WHISPER_MODEL', 'small'),
        'ffmpeg_modelo' => env('VIDEO_WHISPER_CPP_MODEL'),
        'timeout' => (int) env('VIDEO_TRANSCRICAO_TIMEOUT', 1800),
    ],

    /*
    | Narração com voz gerada (o Claude escreve o texto, a voz sai daqui).
    | Tudo local e gratuito — scripts/video/narrar.py, num Python só dele
    | (o instalador cria). Motores:
    |  - kokoro: natural, leve e rápido. É o padrão.
    |  - chatterbox: mais humano e clona voz, mas pesado (torch + ~3 GB) e
    |    lento no processador. Opcional: VIDEO_CHATTERBOX_PYTHON.
    */
    'narracao' => [
        'python' => env('VIDEO_NARRACAO_PYTHON'),
        'chatterbox_python' => env('VIDEO_CHATTERBOX_PYTHON'),
        'modelos' => env('VIDEO_NARRACAO_MODELOS', storage_path('app/video-vozes')),
        'voz_padrao' => env('VIDEO_NARRACAO_VOZ', 'dora'),
        'timeout' => (int) env('VIDEO_NARRACAO_TIMEOUT', 1800),
        // Palavras por segundo na velocidade 1,0 (medido no Kokoro em pt-BR).
        // O Claude usa isto para o texto caber no vídeo.
        'palavras_por_segundo' => 2.7,
        'vozes' => [
            'dora' => ['nome' => 'Dora', 'genero' => 'feminina', 'descricao' => 'Feminina, clara e simpática', 'motor' => 'kokoro', 'id' => 'pf_dora'],
            'alex' => ['nome' => 'Alex', 'genero' => 'masculina', 'descricao' => 'Masculina, jovem e direta', 'motor' => 'kokoro', 'id' => 'pm_alex'],
            'santa' => ['nome' => 'Santa', 'genero' => 'masculina', 'descricao' => 'Masculina, mais grave e calma', 'motor' => 'kokoro', 'id' => 'pm_santa'],
            'humana' => ['nome' => 'Humana+', 'genero' => 'feminina', 'descricao' => 'A mais natural, porém demora mais para gerar', 'motor' => 'chatterbox', 'id' => 'padrao'],
        ],
    ],

    /*
    | Motor de motion (scripts/video/motion): componentes animados desenhados
    | com Skia num Python só dele (o instalador cria). Ícones: Simple Icons
    | (marcas, CC0) e Lucide (genéricos, ISC), baixados pelo instalador.
    */
    'motion' => [
        'python' => env('VIDEO_MOTION_PYTHON'),
        'icones' => env('VIDEO_ICONES_PATH', storage_path('app/video-icones')),
    ],

    'render' => [
        'fps' => 30,
        'previa_escala' => 0.5,
        'previa_crf' => 28,
        'final_crf' => (int) env('VIDEO_FINAL_CRF', 20),
        'final_preset' => env('VIDEO_FINAL_PRESET', 'medium'),
        'loudness' => -14,
    ],
];
