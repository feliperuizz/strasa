# Estúdio de vídeo — guia

Editor de vídeo dentro do STRASA, por cliente. A equipe envia o material, escolhe o estilo e escreve o briefing; o **Claude** (Claude Code rodando no servidor, com a assinatura da agência) assiste ao material e decide a edição; o **FFmpeg** executa. Cada cliente tem identidade de vídeo própria (cores, fonte, logo, legenda, chamada) e um **caderno de aprendizados** que cresce com as revisões.

Menu: **Estúdio de vídeo** (painel geral) e, na barra lateral, **▶ Estúdio** em cada cliente.

---

## 1. Estilos

| Estilo | Para quê | Material |
|---|---|---|
| **Tour do espaço** | Ambiente do cliente em vídeo ritmado, cortes na batida da música | 8–20 clipes curtos, sem fala |
| **Fala com legenda** | Alguém falando para o celular: cortes nas pausas/erros, legenda palavra por palavra, zoom alternado | 1+ vídeo com fala |
| **Apresentador animado** | Fala com animações em volta: palavras-chave, listas item a item, setas, números, telas cheias, sons | 1+ vídeo com fala |
| **Antes e depois** | Transformação com revelação na batida | arquivos marcados "antes"/"depois" (+ processo) |
| **Oferta / vitrine** | Produto/serviço, preço de/por, urgência, chamada | fotos ou clipes + preços no briefing |
| **Depoimento de cliente** | Prova social: cortes limpos, nome, citação, estrelas | vídeo do cliente falando |

O guia de ofício de cada estilo (o que o Claude lê) fica em `resources/video/estilos/*.md`; as regras gerais em `resources/video/regras.md` — adaptados do pacote de skills de edição. **Para mudar o gosto da agência, edite esses arquivos.**

## 2. Fluxo

1. **Rascunho** — envia os arquivos (upload em pedaços de 8 MB, retomável), escreve o briefing, escolhe música/formato/duração. Tudo salva sozinho.
2. **Editar com o Claude** — vai para a fila. Etapas: preparar (proxies leves, HDR→SDR, folhas de quadros, cenas, silêncios) → transcrever → Claude decide (escreve `plano.json`) → prévia em meia resolução.
3. **Revisão** — assiste à prévia e:
   - pede ajuste por texto na **Conversa** ("em 0:12 tira essa frase", "mo04 mais lento", "música mais baixa"); ou
   - usa a **edição rápida do plano** (tirar trechos/animações, corrigir texto e grafia de legenda, volume, cartão final) — funciona sem o Claude.
   Quando o pedido vale para os próximos vídeos ("zoom mais sutil"), o Claude sugere um **aprendizado**; a equipe aprova ("Sim, sempre") e ele entra em todas as edições seguintes do cliente.
4. **Aprovar e gerar o final** — dos originais, em resolução cheia, com capa e legenda do post.
5. **Anexar ao card** — o MP4 (e a capa) viram anexos de um card novo ou existente, e seguem o **fluxo de aprovação do cliente** de sempre. "Refazer em outro estilo" reaproveita o mesmo material.

## 3. Como funciona por dentro

```
app/Video/
  Edicao.php           orquestra as etapas (retoma de onde parou)
  Preparador.php       proxies, áudio, cenas, silêncios, folhas de quadros, logo, batidas
  Transcricao.php      api | faster_whisper | ffmpeg (+ scripts/video/transcrever.py)
  Batidas.php          BPM e batidas em PHP puro; cortes na batida
  Claude/ClaudeCli.php `claude -p` com ferramentas limitadas
  Claude/Contexto.php  monta contexto.md, guias, mapa da prévia, pedido de ajuste
  Plano/Validador.php  confere e conserta o plano (nada vira comando)
  Plano/PilotoAutomatico.php  edição por regras quando o Claude não está disponível
  Render/Montador.php  FFmpeg em 2 passadas (segmentos com cache → montagem)
  Render/Ass.php, Legendas.php, Elementos.php   legendas e animações em ASS (libass)
  Render/Sons.php      efeitos sonoros sintetizados (sem licença de terceiros)
  Diagnostico.php      confere o servidor
app/Jobs/ProcessarEdicao.php   fila "video"
```

- **O Claude nunca executa nada.** Ele lê arquivos e escreve `plano.json` (esquema em `resources/video/plano.md`). O PHP valida e o FFmpeg roda com argumentos em lista. Sem Bash, sem internet, leitura de `.env`/chaves negada, numa pasta própria da edição. Texto falado no vídeo e briefing são tratados como material, não como instrução (proteção contra "prompt injection" vindo de um vídeo).
- **Animações em ASS, não em Chrome.** O pacote de skills usa HyperFrames (HTML + Chromium). Numa VPS com cPanel isso pesa e quebra fácil; o ASS é desenhado pelo libass dentro do próprio FFmpeg, no mesmo passe: títulos, listas, etiquetas, preço, contador, nome/endereço, stickers vetoriais (seta, círculo, check, estrela, brilho, coração, raio, fogo, balão), tela cheia, flash, citação, estrelas.
- **Tempos no arquivo de origem** (regra do pacote): o plano sobrevive a mudanças de corte; a `Linha` converte origem ↔ vídeo final (com velocidade e transições).
- **Render em duas passadas:** cada segmento vira um intermediário já no tamanho final (um decodificador 4K por vez = memória limitada), com cache por conteúdo — num ajuste, só a montagem é refeita. Depois a montagem junta cortes/transições, logo, ASS, voz, música com ducking, efeitos e normaliza em −14 LUFS / −1 dBTP.

## 4. Instalação no servidor (uma vez)

**O motor é o Claude, pela CLI, com a conta da assinatura da agência.** Não há outra assinatura: a legenda usa transcrição local gratuita (faster-whisper), porque o Claude não ouve áudio.

Por SSH, como o usuário do cPanel (sem root, sem abrir porta):

```bash
cd ~/public_html/strasa
bash scripts/video/instalar-servidor.sh
```

Ele instala FFmpeg estático, Claude Code e faster-whisper na sua home, cria `~/strasa-video/dados`, baixa fontes, gera os sons, roda o diagnóstico e **imprime as linhas para o `.env`**. Cole no `.env`, rode `php artisan config:cache` e responda "s" para conectar a conta (ou rode depois o passo abaixo).

### Conectar a conta do Claude

```bash
cd ~/public_html/strasa && bash scripts/video/logar-claude.sh
```

1. Aparece um **link** no terminal → abra no seu computador ou celular.
2. Entre com a **conta da assinatura** e autorize → a página mostra um **código** → cole no terminal.
3. Aparece o **token** (`sk-ant-…`) → copie e cole quando pedir (não aparece na tela).
4. O sistema **testa** o token com uma pergunta real ao Claude e só então grava no `.env` (cópia do anterior em `.env.antes-do-claude`) e refaz o `config:cache`.

O token vale ~1 ano. Para renovar ou trocar de conta, rode o mesmo script. Alternativa sem token: logar com `claude` → `/login` nessa conta do SSH e rodar `php artisan video:conectar-claude --login-local`.

Sem o Claude conectado, a edição **para com erro claro** (a faixa "Motor" fica vermelha). O piloto automático por regras só entra se `VIDEO_PILOTO_RESERVA=true`.

### Depois
- **Cron** (cPanel > Cron Jobs, a cada minuto) — já deve existir: `cd ~/public_html/strasa && php artisan schedule:run >> /dev/null 2>&1`. É ele que liga o worker da fila de vídeo.
- `php artisan video:diagnostico --teste` — tem que terminar com "Estúdio pronto".
- Transcrição precisa de **Python 3.9+**. Se o servidor só tiver um mais antigo, o instalador avisa; peça ao suporte um `python3.11` e rode de novo. O modelo `small` (~500 MB) baixa na primeira legenda; `VIDEO_WHISPER_MODEL=medium` acerta mais e é ~2x mais lento.
- Coloque músicas com licença na **Biblioteca** e preencha a **identidade de vídeo** de cada cliente.

### Vídeo final no Google Drive e exclusão
- Upload, render e ajustes acontecem no servidor (rápido). Quando o **final** fica pronto, o job `ArquivarNoDrive` sobe o MP4 (e a capa) para o Drive dos anexos (`VIDEO_NUVEM_PASTA/Cliente/AAAA-MM-DD título vN.mp4`) e apaga do servidor o final, as prévias antigas e o cache do render. O player e o download passam a buscar do Drive (Range, `AttachmentStreamer::streamDisco`); anexar ao card baixa um temporário. A capa e a prévia atual ficam no servidor; os brutos seguem a retenção (`VIDEO_RETENCAO_DIAS`).
- Sem `GOOGLE_DRIVE_REFRESH_TOKEN` (ou `VIDEO_NUVEM_DISCO=nenhum`), tudo fica no servidor como antes. Envio que falhar: a tela mostra "Enviar de novo".
- **Excluir** funciona em qualquer estado (inclusive travada/processando): lixeira no cartão da lista e "Excluir" no cabeçalho; opcionalmente apaga também a cópia do Drive. Pasta que o worker recriar depois vira órfã e o `video:limpar` apaga.

### Motor de motion (componentes animados)
Os componentes premium (`janela` de terminal digitando, `contador` gigante com brilho, moldura `rec`, `pilula`, `apps` riscados/com check, `comentario`, `passos`, `cards`, `notificacao`, `grafico`, `comparacao`, `seguir`, `busca`, `marca`) são desenhados por `scripts/video/motion/` (Python + Skia) e entram como um vídeo com transparência sobreposto na montagem (abaixo da legenda).
- **Por que não HTML/Chrome** (HyperFrames, Remotion — o que os editores virais com Claude usam): a hospedagem compartilhada limita threads/processos por conta e o Chrome abre dezenas. Skia é o motor gráfico do Chrome, num processo só.
- **Compatibilidade:** skia-python 1xx pede glibc 2.28; em servidor mais antigo o pip/uv instala o 87.x sozinho — o código funciona nos dois (testado). SVG com arco compacto é normalizado (`normalizar_d`).
- **Logos (`App\Video\Logos`):** o Claude escreve o nome da marca ("CapCut", "Adobe Premiere Pro") e o servidor resolve antes do render: logo enviado na Biblioteca → Simple Icons (CC0, ~3.400, vetor) → App Store (API pública da Apple, ícone oficial 512 px) → Wikipédia (só arquivo "logo/icon") → site (apple-touch-icon, com `site` no plano). Achados ficam em `VIDEO_ICONES_PATH/web` (cache global; busca sem resultado espera 7 dias) e aparecem na Biblioteca, onde dá para esquecer ou trocar. Apps ambíguos (Premiere, Photoshop, After Effects…) buscam pelo nome oficial e exigem o fabricante (`Logos::CONHECIDOS`). Sem nada: bloco com sigla. Ícones genéricos: Lucide (ISC, ~2.400).
- **O Claude** lê `guias/motion.md` (catálogo, regra de ouro "a tela reage à fala", densidade de 2–4 s, roteiro de referência). Cada item entra na palavra-gatilho (`itens[].em`).
- **Desempenho:** ~55 ms/quadro em 1080p (sombras/brilhos em cache, alfa pré-multiplicado), ~4x menos na prévia. Cache por conteúdo: ajuste que não mexe nos componentes não refaz o motion.
- Se o motor falhar ou não estiver instalado, o vídeo sai sem os componentes (com aviso), nunca perde a edição.
- Testar o visual sem render completo: `render.py` aceita `"quadros": [t…]` + `"pasta_quadros"` (+ `"fundo"`) e salva PNGs.

### Narração com voz gerada
O Claude escreve o texto (ancorado nos segmentos, ~2,7 palavras/s) e a voz é gerada **no próprio servidor, de graça** por `scripts/video/narrar.py`:
- **Kokoro** (padrão, Apache-2.0): vozes Dora (feminina), Alex e Santa (masculinas). ~330 MB, gera 10 s de voz em ~3 s com 4 threads. Python próprio em `~/strasa-video/venv-voz` (pede numpy 2; não pode dividir com o faster-whisper).
- **Chatterbox** (opcional, MIT): voz "Humana+", a mais natural, e aceita clonar uma voz (`vozes/referencias/<id>.wav` + uma entrada em `config/video.php`). Pesado: torch + ~3 GB, ~8x mais lento que o tempo real em CPU. Só com `--com-chatterbox` no instalador.
- Na tela: "Narração com voz gerada" no briefing (com a voz e o botão Ouvir), voz padrão por cliente na identidade, texto editável na aba Plano (só a fala mudada é gerada de novo) e o pedido "coloca uma narração" no ajuste.
- Cada fala vira um WAV com cache por texto em `renders/narracao/`. A música abaixa sob a voz (mesmo ducking da fala gravada) e a legenda sai do texto da narração.
- `php artisan video:vozes` gera as amostras do botão Ouvir; `--testar="texto" --voz=alex` gera um teste avulso.

## 5. Limitações do servidor — o que pode dar problema e o que foi feito

| Risco | O que acontecia | Proteção no código |
|---|---|---|
| Request do PHP tem tempo limite (LiteSpeed/PHP) | Render no clique morreria no meio | Tudo roda na fila; a tela só acompanha |
| Fila padrão com `retry_after` de 90 s | Render de 10 min seria pego de novo e rodaria em dobro | Conexão de fila `video` com 4 h (`config/queue.php`) e worker próprio |
| Cron roda `schedule:run` a cada minuto | Um render longo prenderia os outros agendamentos (convites, avisos) | Worker de vídeo com `runInBackground()` + `withoutOverlapping(150)` + `--max-jobs=1` (processo novo por edição, sem acumular memória) |
| Upload grande (4K = 400 MB/min) | `post_max_size`, limite do LiteSpeed, **100 MB por request do Cloudflare**, 4G caindo | Upload em pedaços de 8 MB, retomável (pedaço repetido responde 409 com o ponto certo) |
| CPU/disco disputados com o site | Site lento durante o render | `nice`/`ionice` (prioridade baixa), `VIDEO_THREADS=2`, 1 edição por vez (`VIDEO_SIMULTANEAS`) |
| Memória (OOM killer) | 4K HEVC decodificado em paralelo mataria o processo | 1 decodificador por vez (1ª passada); proxies de 720p para análise e prévia |
| Processo morto no meio (OOM, reinício, kill) | Edição "editando" para sempre e fila travada por 2 h | `video:vigiar` (5 em 5 min): marca como interrompida, destrava vagas; "Tentar de novo" retoma de onde parou |
| Disco cheio | Derrubaria o site (sessão e cache são arquivo) | Recusa upload/edição abaixo de `VIDEO_DISCO_MINIMO_GB`; `video:limpar` (03:30) apaga brutos após `VIDEO_RETENCAO_DIAS` (o final fica) |
| `proc_open` desligado no cPanel | Nada roda | O diagnóstico detecta e explica |
| Firewall (CSF) barrando saída | Claude e download de fontes falham | Diagnóstico testa a saída 443; **nenhuma porta de entrada é aberta** |
| Limite de uso da assinatura do Claude | Edição falharia | Edição fica "aguardando limite" e volta sozinha para a fila (`VIDEO_CLAUDE_ESPERA_LIMITE`) |
| Claude fora do ar / não conectado | Estúdio parado | Erro claro com o que fazer (faixa "Motor" vermelha, diagnóstico); a edição rápida manual do plano continua funcionando; piloto por regras opcional (`VIDEO_PILOTO_RESERVA`) |
| Conexões MySQL presas | O "Too many connections" de 26/08 | `DB::disconnect()` antes de render/transcrição/streaming de vídeo |
| iPhone HDR (HLG/Dolby Vision) e rotação | Cor lavada, vídeo deitado | Tonemap para SDR BT.709 antes de escalar; rotação lida do ffprobe |
| `git reset --hard` do deploy | Apagaria arquivos dentro do projeto | Dados em `VIDEO_STORAGE_PATH`, fora do projeto |
| `APP_URL` velho em produção | Links gerados fora de request quebram | O estúdio só gera links dentro de request (`route()`) |

**Tempo esperado** (VPS 2 núcleos): prévia de 30 s ≈ 1–3 min de render; o Claude leva 1–4 min para decidir e ~1 min por ajuste; o final de 60 s ≈ 3–8 min.

## 6. Comandos

```bash
php artisan video:diagnostico [--teste]   # confere tudo (o --teste renderiza 2 s e chama o Claude)
php artisan video:instalar [--forcar]     # fontes + efeitos sonoros
php artisan video:conectar-claude         # testa e grava o token da conta (usado pelo logar-claude.sh)
php artisan video:vozes [--refazer]       # vozes da narração instaladas + amostras do botão Ouvir
php artisan video:vigiar                  # agendado: recupera edição interrompida
php artisan video:limpar [--simular]      # agendado: retenção de disco
```

## 7. Problemas comuns

- **Fica "Na fila" para sempre** → o cron não está rodando (`video:diagnostico` mostra "agendador (cron)").
- **"O Claude do servidor não está logado"** → token vencido ou trocado: `bash scripts/video/logar-claude.sh`.
- **Sem legenda** → faster-whisper não instalado ou Python antigo (`video:diagnostico` mostra).
- **Letras trocadas na legenda** → fontes erradas: `php artisan video:instalar --forcar` (o subconjunto certo é `latin`; o `latin-ext` não tem o alfabeto básico).
- **Edição parou com erro do FFmpeg** → a mensagem mostra a linha da causa. "Tentar de novo" reaproveita tudo o que já foi feito.
- **Música silenciada no Instagram** → música sem licença. Use bancos com licença comercial ou poste sem música e escolha o áudio no app.

## 8. Testes

`php vendor/bin/phpunit tests/Unit/EstudioMotorTest.php` — linha do tempo, validador, legendas (agrupamento, hesitação, correções de várias palavras), batidas, desenho ASS, transcrição, leitura de fonte.
