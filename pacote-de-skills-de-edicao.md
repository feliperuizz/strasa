# Pacote de skills de edição para colar no Claude

*Nov 20, 2026*

## Como usar

Este documento traz um fluxo de edição de vídeo pronto: 11 skills (o comando `/editar` e 10 etapas) e um perfil de estilo de exemplo. Você cola tudo no seu Claude Code e ele cria os arquivos.

1. **Antes:** tenha o app do Claude (plano Pro ou superior) e instale a skill base, que traz os guias que estas skills consultam. Numa sessão da aba **Code**, envie: `Instala esta skill para mim: https://github.com/LeiteZ/fluxo-de-edicao`
2. **Abra uma sessão nova** na aba Code.
3. **Copie deste documento tudo, da seção "Mensagem de instalação" até o fim**, cole na conversa e envie. Outra opção: exporte o documento em Markdown e arraste o arquivo para a conversa com a mensagem de instalação.
4. O Claude cria os arquivos, ajusta as pastas para o seu computador e mostra um resumo.
5. Abra outra sessão nova e digite `/fluxo-de-edicao` para adaptar o fluxo ao seu jeito: onde você revisa (Premiere, DaVinci ou vídeo pronto), formato (horizontal ou Reels) e o **seu** estilo. O perfil daqui é só um ponto de partida.

O fluxo foi montado para **Reels verticais, revisão assistindo o MP4 pronto, estilo escuro com laranja**. Os scripts de cada etapa ainda não existem: eles nascem e são testados no primeiro vídeo.

---

## Mensagem de instalação

*Copie daqui até o fim do documento.*

Olá, Claude. Quero instalar um fluxo de edição de vídeo em skills. Abaixo estão 17 arquivos, cada um com o caminho e o conteúdo. Por favor:

1. Antes de tudo, me pergunte em qual pasta eu quero guardar meus vídeos (sugira uma, ex.: `C:\Videos-Claude` no Windows ou `~/Videos-Claude` no Mac). Nos arquivos ela aparece como "C:\edição de videos". Troque esse caminho pelo que eu escolher em todos os arquivos, e ajuste barras e comandos se eu estiver no Mac.
2. Confira se a skill fluxo-de-edicao já está em `~/.claude/skills/`. Se não estiver, me avise e ofereça instalar de https://github.com/LeiteZ/fluxo-de-edicao.
3. Crie cada arquivo no caminho indicado, com o conteúdo exatamente como está (só com os caminhos ajustados). Se já existir uma skill com o mesmo nome, me mostre a diferença e pergunte antes de sobrescrever.
4. Crie também a pasta de ferramentas (`<minha pasta>\_ferramentas`, com scripts, sons e perfil\fontes) e um `_ferramentas\montagem.md` dizendo que o fluxo foi importado e que falta adaptá-lo com /fluxo-de-edicao.
5. Não instale nenhum programa agora. Os scripts citados nas skills ainda não existem no meu computador: eles são escritos e testados no primeiro vídeo, e as instalações (Python, FFmpeg, Node.js, WhisperX, OpenCV) acontecem quando forem necessárias, sempre com o meu "sim".
6. No fim, mostre a lista do que foi criado e me diga para abrir uma sessão nova e digitar /fluxo-de-edicao para adaptar o fluxo ao meu jeito (onde eu reviso, formato, estilo).

---

## Arquivo 1 · `~/.claude/skills/editar/regras-comuns.md`

```markdown
# Regras comuns das skills edit-*

Lidas uma vez por sessão por todas as skills de edição. Mudou uma regra geral? Mude aqui, não em cada skill.

## 0. Onde fica cada coisa
- **Raiz:** `C:\edição de videos\`. Um vídeo por pasta: `AAAA-MM-DD_Nome\` com `Brutos\`, `Assets\`, `Docs\`, `Renders\`, `_CLAUDE\`.
- **Ferramentas:** `C:\edição de videos\_ferramentas\`
  - scripts: `_ferramentas\scripts\` (reaproveitados entre vídeos; cada script novo é testado e documentado na skill que o usa);
  - Python geral (OpenCV, NumPy, PySceneDetect): `_ferramentas\.venv\Scripts\python.exe`;
  - Python da transcrição (WhisperX, torch CPU): `_ferramentas\.venv-whisper\Scripts\python.exe`;
  - sons: `_ferramentas\sons\`; fontes: `_ferramentas\perfil\fontes\`.
- **Perfil (gosto):** `_ferramentas\perfil\`: `identidade.md`, `estilo-motion.md`, `estilo-camera.md`, `estilo-cortes.md`, `estilo-som.md`, `catalogo.md` (quando existir), `referencias\`.
- **Guias do ofício (consulta detalhada, armadilhas):** `~\.claude\skills\fluxo-de-edicao\guias\` (`etapa-*.md`, `oficio-motion.md`, `hyperframes.md`, `entrega-no-editor.md`).
- **PATH no PowerShell:** um shell aberto antes das instalações pode não achar `ffmpeg`/`node`. Recarregue: `$env:Path = [Environment]::GetEnvironmentVariable('Path','Machine') + ';' + [Environment]::GetEnvironmentVariable('Path','User')`.
- Rode Python com `-I` e com `PYTHONIOENCODING=utf-8` (a pasta tem acento).

## 1. Conversa e segurança
- Falar em português, curto e sem jargão (a pessoa está começando em edição). Explicar o porquê de uma escolha em uma frase.
- **Nunca mover, renomear ou apagar** nada de `Brutos\`, `Assets\` (o que a pessoa pôs lá) ou `Docs\`. Tudo o que o Claude gera fica em `_CLAUDE\` (trabalho) e `Renders\` (entregas).
- **Nunca sobrescrever** um arquivo já entregue: versão nova (`_v01`, `_v02`…).
- Apagar, quando precisar: mover para `_CLAUDE\_lixeira\` e deixar a pessoa apagar.
- Instalar algo novo: explicar o que é, de onde vem e o tamanho, e esperar o "sim".

## 2. Formato padrão (perfil principal)
- **Reels/Shorts vertical 9:16**, saída **1080x1920, 30 fps**, H.264 + AAC, `-movflags +faststart`.
- Material de iPhone: **HEVC 10-bit HDR (HLG/Dolby Vision)** com rotação 90°. Tudo que for gerado a partir dele passa por **tonemap para SDR BT.709** (senão fica lavado ou estourado no Instagram). Filtro que funcionou (FFmpeg 9 Gyan): `zscale=t=linear:npl=100,format=gbrpf32le,zscale=p=bt709,tonemap=hable:desat=0,zscale=t=bt709:m=bt709:r=tv,format=yuv420p`. A rotação é aplicada automaticamente pelo FFmpeg.
- **Zona segura do Reels:** nada importante nos ~8% de cima, ~20% de baixo e ~12% da direita.
- Se um vídeo novo vier horizontal ou com outra taxa, avisar e confirmar o formato antes de seguir.

## 3. Estado do vídeo sem ler inteiro
O `_CLAUDE\projeto.json` cresce muito. Ler e mudar sempre pelo script de estado `_ferramentas\scripts\estado.py` (escrito na primeira `edit-iniciar`):
- `estado.py <pasta> status` mostra onde o vídeo está;
- `estado.py <pasta> <bloco>` mostra o resumo de um bloco (`--json` para o bloco inteiro; `--item <id>` para um item);
- `--set <caminho> <valor>` muda um campo; `--etapa <etapa>=<status>` muda o status de uma etapa;
- `--pendencia <etapa> "<texto>"` anota uma pendência; `--resolver <id>` resolve;
- `estado.py <pasta> init` cria o estado (só na `edit-iniciar`; nunca sobrescreve).

Alerta de desatualizado: ao gravar `motions`, `camera`, `legendas` ou `sfx`, salve nele `cortes_versao` = a `cortes.versao` usada; se o corte mudar depois, o `status` avisa.

No `--set`, num caminho que passa por uma lista, um trecho que não é número procura o item pelo id (`cortes.segmentos.s001.fim`).

Status de etapa: `pendente`, `em_andamento`, `aguardando_aprovacao`, `concluido`, `nao_precisa`.

Tempos de corte e marcadores ficam no **tempo do arquivo de origem** (sobrevivem a novas versões do corte). Ids estáveis (`s001`, `mo04`, `cam07`, `lg03`, `sfx12`).

## 4. Revisão sem programa de edição
- A pessoa revisa **assistindo**. As paradas de revisão são: (1) cortes, (2) plano das animações (pelo chat), (3) animações prontas, (4) render final.
- Ela cita o **tempo do vídeo que assistiu** ("em 0:12 tira essa frase") ou o **número do item** ("mo04 mais lento"). Converter o tempo do vídeo para o tempo de origem pela lista de cortes.
- Rascunhos de revisão em resolução menor (540x960) para sair rápido; o final em 1080x1920.
- Pedir para a pessoa juntar vários ajustes antes de renderizar de novo.

## 5. Caderno de aprendizados (`_CLAUDE\aprendizados.md`)
Anotar na hora, durante a revisão de qualquer etapa, toda correção, elogio ou preferência que vale para outros vídeos:
- "zoom mais sutil", "esse som ficou ótimo": **anota**;
- "tira essa frase": **não anota** (é só deste vídeo);
- na dúvida, anota com "(talvez só deste vídeo)".

Formato: `- [ ] <etapa> · <o que foi pedido, com contexto> · <dd/mm>`. Depois de aplicado: `- [x] … → <onde entrou>`; só daquele vídeo: `- [-] …`. Nunca apagar linhas. Anotar não é mudar: as skills só mudam quando a pessoa pedir (seção 7).

## 6. Trechos, ponto salvo e fechar uma etapa
- Cortes e plano de animação olham o vídeo inteiro. Render, câmera e som podem ir por trecho ("até 0:30", "o gancho"). Um item pertence ao trecho em que começa.
- **Ponto salvo:** trecho ou etapa terminado, revisões aplicadas, entrega gerada. Só aí sugerir `/clear` e `/editar`.
- **Pendência:** pedido ainda não aplicado é anotado na hora pelo script de estado.
- **Fechar a etapa** (aprovada inteira): marcar `concluido`, dizer quantos aprendizados ficaram anotados, sugerir `/clear` e `/editar`.
- **Uma etapa por vez.** Nunca encadear a próxima sozinho: a pessoa diz "sim".
- Se algo aprovado mudar depois (ex.: o corte muda depois das animações), avisar o que precisa ser refeito.

## 7. Aplicar os aprendizados (quando a pessoa pedir: `/editar aprendizados`)
1. Ler as linhas `- [ ]` do caderno.
2. Propor onde cada uma entra: gosto vai para o perfil (`estilo-*.md`, `identidade.md`, `catalogo.md`); ofício (vale para qualquer vídeo) vai para a skill da etapa; o que vale para todas vai para este arquivo.
3. A pessoa filtra. Gravar só o aprovado, mostrando antes o texto das mudanças maiores. Marcar cada linha.

## 8. Economia de limite
- `/clear` entre as etapas (o histórico longo é reprocessado a cada mensagem).
- Ler resumos e recortes: a transcrição por frase, o estado pelo script, um quadro só quando há dúvida real.
- Tarefas longas (transcrição, render) em segundo plano.
- Modelo sugerido por etapa: iniciar, assets, a skill mestra → o mais leve que funcione; preparar → intermediário; cortes, motion-mapa, motion-render, câmera → o mais capaz; legendas, sfx, render → intermediário a capaz.
```

---

## Arquivo 2 · `~/.claude/skills/editar/SKILL.md`

```markdown
---
name: editar
description: "Ponto de entrada das skills de edição de vídeo (Reels vertical, estilo cinema escuro): mostra onde o vídeo está (etapas, pendências, alertas, aprendizados anotados) e abre a skill da etapa certa. Também aplica os aprendizados (/editar aprendizados). Use em /editar, ao abrir ou retomar um vídeo, depois de um /clear, ou quando a pessoa perguntar em que pé está o vídeo."
---
# editar

Fluxo: edit-iniciar → edit-preparar → edit-cortes → edit-motion-mapa → edit-assets → edit-motion-render → edit-camera → edit-legendas → edit-sfx → edit-render

Antes de tudo, leia `regras-comuns.md` (nesta pasta), uma vez por sessão.

## /editar
1. **Onde estou:** a pasta atual deve ser a de um vídeo (`C:\edição de videos\AAAA-MM-DD_Nome\`). Se estiver na raiz `C:\edição de videos\`, liste as pastas de vídeo e pergunte qual. Se não parecer um projeto, pergunte se o Claude Code foi aberto no lugar certo.
2. **Sem projeto:** se não existir `_CLAUDE\projeto.json`, diga que o vídeo ainda não foi iniciado e ofereça a `edit-iniciar`.
3. **Status:** rode `estado.py <pasta> status` e mostre curto: etapas, o que já foi feito por trecho, pendências, alertas e quantos aprendizados estão anotados.
4. **Próximo passo:** diga qual é e pergunte se segue. Com o "sim", abra a skill da etapa e siga as instruções dela.
   - Etapa aguardando revisão: o próximo passo é continuar essa mesma etapa, nunca pular.
   - Alerta de algo desatualizado (ex.: o corte mudou depois do plano de animação): mostre primeiro e espere a decisão.
   - A pessoa pediu outra etapa ou um trecho ("sim, só o gancho"): abra essa etapa e passe o trecho.
5. **Uma etapa por vez.** Não encadear etapas sozinho e não dar `/clear` (isso é sempre da pessoa).
6. **Modelo:** lembre, numa linha, o modelo sugerido para a etapa (regras comuns §8). Trocar logo depois do `/clear` não custa nada.

## /editar aprendizados
Siga a seção 7 das regras comuns.

## Regras
- Não editar vídeo nem rodar scripts de etapa: isso é das skills de cada etapa.
- Nunca mover, renomear ou apagar arquivos do projeto.
```

---

## Arquivo 3 · `~/.claude/skills/edit-iniciar/SKILL.md`

```markdown
---
name: edit-iniciar
description: "Abre um vídeo novo: confere a pasta, cria o estado do projeto e organiza o briefing (assunto, nomes e termos difíceis, música, ordem das partes). Use em /edit-iniciar, quando um vídeo novo chegar ou pela /editar."
---
# edit-iniciar

Abre o projeto e organiza o briefing. Não analisa nenhum arquivo de vídeo (isso é da `edit-preparar`).

- **Lê:** a pasta do vídeo, o perfil (só para anotar qual é).
- **Escreve:** `_CLAUDE\projeto.json` (pelo `estado.py`), `_CLAUDE\briefing.md`, `_CLAUDE\aprendizados.md` (vazio, com cabeçalho).
- **Próximo passo:** `/edit-preparar`

## Passo a passo
1. **Pasta:** a atual deve ser `C:\edição de videos\AAAA-MM-DD_Nome\`. Se a pessoa ainda não criou, ofereça criar com a data de hoje e um nome curto que ela der (com `Brutos\`, `Assets\`, `Docs\`, `Renders\`, `_CLAUDE\`). Se faltar subpasta, pergunte se pode criar (vazia).
2. **Projeto já iniciado** (`_CLAUDE\projeto.json` existe): não sobrescrever; mostrar o status e perguntar se é só para atualizar o briefing.
3. **Script de estado:** se `_ferramentas\scripts\estado.py` ainda não existe, escreva-o agora (só biblioteca padrão), conforme `~\.claude\skills\fluxo-de-edicao\modelos\estado-do-video.md`: `status`, `<bloco>`, `--json`, `--item`, `--set`, `--etapa`, `--pendencia`, `--resolver`, e alertas (ex.: corte mudou depois do plano de animação). Teste e anote o uso nas regras comuns §3 se mudar algo.
4. **Criar o estado:** projeto, perfil (`principal`), formato (`reels_vertical`, 1080x1920, 30 fps), todas as etapas `pendente`, `iniciar` em `em_andamento`.
5. **Briefing:** pergunte numa mensagem curta, sem formulário:
   > "Me conta, do jeito que vier, sobre o que é esse vídeo, quem é a pessoa que aparece, e qualquer direcionamento que você já tenha. Se tiver nomes de marcas, ferramentas ou termos difíceis que ela fala, me passa também. E me diz se já tem uma música escolhida."
   - Organize sem inventar: preserve as palavras e a intenção da pessoa. Seções: sobre o vídeo, quem aparece, direcionamentos gerais, termos e nomes (lista para a transcrição), música, observações por etapa. Omita as vazias.
   - Salve em `_CLAUDE\briefing.md` e mostre. Pergunte de volta só se algo estiver realmente ambíguo.
6. **Ordem das partes:** liste os arquivos de `Brutos\`. Se vierem em partes, a ordem é a do nome (`parte 1`, `parte 2`…); se o nome não disser, pela data de gravação, e confirme com a pessoa. Anote a ordem no estado.
7. **Encerrar:** marcar `iniciar` = `concluido` e dizer o que falta para a próxima etapa (brutos na pasta, música em `Assets\` se houver).

## Regras comuns
Antes de começar, leia `~\.claude\skills\editar\regras-comuns.md` (uma vez por sessão).

## Regras
- Nunca mover, renomear ou apagar arquivos do projeto.
- Falar com a pessoa em português, de forma curta.
```

---

## Arquivo 4 · `~/.claude/skills/edit-preparar/SKILL.md`

```markdown
---
name: edit-preparar
description: "Prepara o bruto para edição: inventário dos arquivos, junta as partes na ordem, converte o HDR do iPhone para cor normal, transcreve palavra por palavra com o WhisperX e acha as notas de edição faladas. Use em /edit-preparar, após a edit-iniciar."
---
# edit-preparar

Transforma o bruto em algo que o Claude consegue ler: inventário, base de trabalho em SDR, transcrição com tempo por palavra, silêncios e notas faladas. Não corta nada.

- **Lê:** `projeto.json` (ordem das partes), `briefing.md` (termos e nomes), arquivos de `Brutos\`.
- **Escreve:** bloco `midia` e `transcricao` e `notas_edicao` no estado; em `_CLAUDE\preparar\`: os proxies SDR, o WAV, a transcrição por palavra (JSON) e **por frase** (texto para o Claude ler), o mapa de silêncios.
- **Próximo passo:** `/edit-cortes`

Se `edit-iniciar` não estiver `concluido`, pare e sugira terminá-la.

## Ofício (guia completo: `fluxo-de-edicao\guias\etapa-iniciar-preparar.md`)
- **Inventário** (ffprobe): duração, fps, resolução, codec, rotação, HDR, áudio, data. Alertas: arquivo ilegível, sem áudio, fps diferente entre partes, VFR.
- **Todos os tempos** ficam no tempo de cada arquivo de origem (`parte`, segundo). Para o Claude ler, uma linha do tempo contínua das partes em ordem, com a parte e o tempo de origem em cada frase.
- **Proxy de trabalho** por parte: SDR (tonemap das regras comuns §2), 540x960, 30 fps, H.264, para análise, rascunhos e detecção de rosto. Os renders finais voltam aos originais.
- **Áudio principal:** o do iPhone (48 kHz). Extrair um WAV 16 kHz mono da linha do tempo contínua (e guardar o deslocamento de cada parte).
- **Transcrição (WhisperX, CPU):** modelo `large-v3`, `compute_type=int8`, idioma `pt`, VAD ligado, alinhamento forçado (modelo de alinhamento português) para o tempo por palavra. Passe os termos do briefing como contexto (`initial_prompt`).
  - **Confira o que foi engolido:** trechos com energia de áudio e sem palavra → retranscrever esses trechos **sem** contexto inicial. Avise a etapa de cortes.
  - Primeiro uso: os modelos (~4 GB) baixam sozinhos; avisar a pessoa que demora.
- Mantenha as hesitações ("é", "hum", recomeços): a etapa de cortes precisa vê-las.
- **Silêncios** acima de ~0,3 s (tempos das palavras + `silencedetect` de apoio). Só mapeia.
- **Notas faladas:** "nota de edição" … "fim da nota", tolerando variações de transcrição. Anotar parte, início, fim (incluindo as expressões), texto, interpretação curta e a etapa a que se refere. Vídeos gravados sem esse costume podem não ter nenhuma.

## Scripts (em `_ferramentas\scripts\`; escrever e testar na primeira vez, anotar o uso aqui)

| Script | O que faz | Armadilhas |
|---|---|---|
| `prep_inventario.py` | ffprobe de cada parte → bloco `midia` | rotação 90 do iPhone; HDR (HLG/Dolby Vision) |
| `prep_proxy.py` | proxies SDR 540x960 30 fps + WAV 16 kHz mono contínuo com os deslocamentos | tonemap obrigatório; não juntar o vídeo das partes num arquivo só (cada parte é uma origem) |
| `prep_transcrever.py` | WhisperX + alinhamento → palavras com tempo (no tempo de origem de cada parte) | rodar com o Python da `.venv-whisper`; contexto inicial pode inventar frases |
| `prep_frases.py` | transcrição por frase com timecode, pausas e notas marcadas; também `silencios.json` e `notas_edicao` | é o único arquivo que o Claude lê inteiro |

Uso (todos testados em 20/11/2026, a partir de `C:\edição de videos`), na ordem:
- `_ferramentas\.venv\Scripts\python.exe -I _ferramentas\scripts\prep_inventario.py <pasta>` (segundos)
- `... .venv\Scripts\python.exe -I ...\prep_proxy.py <pasta>` (segundo plano; ~2 min para 55 s de 4K; pula proxies que já existem)
- `... .venv-whisper\Scripts\python.exe -I ...\prep_transcrever.py <pasta>` (segundo plano; ~3 min com os modelos já baixados)
- `... .venv\Scripts\python.exe -I ...\prep_frases.py <pasta>` (segundos)

Saídas em `_CLAUDE\preparar\`: `proxy_pNN.mp4`, `audio_16k.wav`, `palavras.json`, `frases.txt`, `silencios.json`.

Armadilhas já resolvidas nos scripts: com `-I` a pasta do script não entra no `sys.path` (os scripts a inserem para importar `estado`); no Windows o download do Hugging Face falhava com WinError 1314 (symlink), então o `prep_transcrever.py` força cópia; o aviso de `torchcodec` no log é inofensivo (o áudio é passado já carregado). A confiança baixa (`?`) em palavras curtas comuns ("que", "você") costuma ser só o alinhamento; não é erro de transcrição.

## Passo a passo
1. Status (`estado.py <pasta> status`); `preparar` = `em_andamento`.
2. Inventário e alertas. Mostrar em 3–4 linhas (quantas partes, duração total, formato).
3. Proxies e WAV (segundo plano).
4. Transcrição (segundo plano). Avisar quanto tempo deve levar.
5. Conferir trechos engolidos; silêncios; notas faladas.
6. **Resumo no chat:** duração total, quantas frases, termos que pareceram errados na transcrição (pedir a grafia certa), notas faladas achadas, alertas. Corrigir grafias que a pessoa indicar (só a grafia, nunca o que foi dito).
7. Marcar `concluido` e sugerir `/clear` e `/editar`.

## Regras comuns
Antes de começar, leia `~\.claude\skills\editar\regras-comuns.md` (uma vez por sessão).

## Regras
- Nunca mover, renomear ou apagar arquivos do projeto.
- Falar com a pessoa em português, de forma curta.
```

---

## Arquivo 5 · `~/.claude/skills/edit-cortes/SKILL.md`

```markdown
---
name: edit-cortes
description: "Faz o primeiro corte do vídeo: escolhe os takes, tira erros, repetições, hesitações claras, pausas e notas de edição, no ritmo apertado, e gera um rascunho leve para assistir. Use em /edit-cortes, após a edit-preparar."
---
# edit-cortes

O primeiro corte limpo. Sem zoom, animação, legenda ou efeito. É a base que a pessoa aprova (**parada de revisão 1**).

- **Lê:** transcrição por frase (`_CLAUDE\preparar\`), silêncios, notas de edição, `briefing.md`, perfil `estilo-cortes.md`.
- **Escreve:** bloco `cortes` (segmentos no tempo de origem) e `marcadores` no estado; rascunho em `Renders\<projeto>_cortes_vNN.mp4` (540x960) e a lista de marcadores em `_CLAUDE\cortes\marcadores_vNN.txt`.
- **Próximo passo:** `/edit-motion-mapa`

Se `edit-preparar` não estiver `concluido`, pare e sugira terminá-la.

## Ofício (guia completo: `fluxo-de-edicao\guias\etapa-cortes.md`)
- **Leia por frase, decida por frase.** O arquivo por palavra só por script, num trecho.
- **Takes:** frases repetidas ou parecidas, recomeços, frases abandonadas, "pera", "de novo". Escolha, nesta ordem: a nota de edição; o último take completo; um anterior limpo se o último tropeçou. Dois bons: escolha um e marque o outro.
- Antes de descartar uma frase como incompleta, procure o take que a transcrição pode ter perdido.
- **Nunca no meio da frase.** Corta entre ideias e takes.
- **Sobras de cada parte:** começo e fim de cada clipe costumam ter respiração, olhar para o celular, mão no botão: saem.
- **Hesitações:** corta quando isolada por silêncio e longa; colada na fala fica e vira marcador.
- **Notas de edição:** o trecho inteiro sai.
- **Ordem:** a gravada. Reordenar, só sugerir (frase de impacto que funcionaria melhor no começo de um reel é um caso comum: sugira).
- **Na dúvida, não corta e marca.**

## Perfil
- `estilo-cortes.md`: ritmo apertado, folga de borda ~0,06–0,1 s, planos curtos.

## Scripts

| Script | O que faz | Armadilhas |
|---|---|---|
| `cortes_bordas.py` | ajusta início e fim de cada segmento pela **energia do áudio** + folga do perfil, arredonda para frames (30 fps de saída) | não comer sílaba; muleta curta colada vira marcador, não corte; nunca reajustar um corte que a pessoa já aprovou |
| `cortes_rascunho.py` | monta o rascunho 540x960 a partir dos **proxies**: segmentos em ordem, crossfade de áudio de 2 frames nas junções, 1 frame nas pontas; marcadores queimados no canto (texto pequeno) | tempo em frames num lugar só; o vídeo de saída é 30 fps |

## Passo a passo
1. Status; `cortes` = `em_andamento`.
2. Ler a transcrição por frase. Decidir os segmentos (id, parte, início, fim aproximados nas palavras de borda, começo do texto) e os marcadores.
3. `cortes_bordas.py` → `cortes_rascunho.py` (segundo plano).
4. **Resumo no chat:** duração do bruto → duração do corte; quantos marcadores e de que tipo (com o tempo **do rascunho**); sugestões; caminho do arquivo.
5. `aguardando_aprovacao`.

## Revisão
A pessoa cita o tempo do rascunho ("em 0:12 tira essa frase", "volta a frase do 0:30"). Converter para o tempo de origem pela lista de segmentos, ajustar só o citado, gerar `vNN+1`, anotar aprendizados.

## Aprovação
`concluido` (o corte trava). Se mudar depois do plano de animação, avisar que motion, câmera, legendas e som precisam ser recalculados. Dizer quantos aprendizados ficaram anotados e sugerir `/clear` e `/editar`.

## Regras comuns
Antes de começar, leia `~\.claude\skills\editar\regras-comuns.md` (uma vez por sessão).

## Regras
- Nunca mover, renomear ou apagar arquivos do projeto.
- Falar com a pessoa em português, de forma curta.
```

---

## Arquivo 6 · `~/.claude/skills/edit-motion-mapa/SKILL.md`

```markdown
---
name: edit-motion-mapa
description: "Planeja as animações do vídeo (onde entram, o que mostram, em que formato, com qual palavra) e a intenção da câmera em volta, numa lista numerada mo01, mo02… para aprovar pelo chat. Não anima nada. Use em /edit-motion-mapa, após a edit-cortes."
---
# edit-motion-mapa

A etapa que mais decide a qualidade. Propõe no chat, ajusta com a pessoa e grava o plano (**parada de revisão 2**, só pelo chat). Aqui mudar é barato; depois do render, um conceito fraco custa uma rodada inteira.

- **Lê:** o corte aprovado, a transcrição por frase **no tempo da sequência**, notas de edição de motion, `briefing.md`; perfil: `identidade.md`, `estilo-motion.md`, `estilo-camera.md`, `catalogo.md` (se existir); `fluxo-de-edicao\guias\oficio-motion.md`.
- **Escreve:** bloco `motions` no estado (itens `moNN`, storyboard do gancho, assets pedidos, intenção de câmera, batidas para o som); `_CLAUDE\motion\plano_vNN.md` (legível).
- **Próximo passo:** `/edit-assets` (ou `/edit-motion-render`, se não faltar asset)

Se `edit-cortes` não estiver `concluido`, pare e sugira terminá-la.

## Ofício (guia completo: `fluxo-de-edicao\guias\etapa-motion-mapa.md`)
- **O conteúdo decide a animação:** para cada ideia, a forma visual que explica melhor *aquela* ideia. O catálogo é repertório, nunca obrigação.
- **Reel = gancho do começo ao fim:** os primeiros 3–5 s são planejados frase a frase, com **storyboard obrigatório** (conceito, camadas, câmera, batidas, herói, vida, saída). Depois, uma animação a cada ~3–5 s, alternando tela cheia, card e só ênfase, com um respiro de rosto a cada ~6–10 s (`estilo-motion.md`).
- **Antes de mostrar o gancho:** o conceito nasce desta fala ou serviria para qualquer vídeo? A energia combina? Estou repetindo a mesma fórmula?
- **Onde entra:** processo, passo a passo, comparação, antes e depois, números, listas, conceitos abstratos, ferramentas citadas, a frase-chave. **Não entra** para enfeitar fala que se sustenta sozinha.
- **Formatos no vertical:**
  - **tela cheia** (cobre o rosto, a voz segue): conceitos, processos, números grandes;
  - **card em cima** (terço superior) ou **card embaixo** (acima da zona segura de 20%): coisas curtas; a câmera leva o rosto para o lado oposto;
  - **ênfase sobre a filmagem:** palavra-chave grande + escurecimento atrás.
- **Duração:** o trecho da fala que ilustra; mínimo ~1,5 s em reel, típico 2–5 s. Com texto, segura ~1 s depois da última palavra, salvo corte ou outra animação.
- **Palavra-gatilho** com tempo exato para cada entrada importante. Animação que começa ou termina num corte usa o **tempo exato do corte**, não o da palavra.
- **Câmera em volta (intenção em palavras):** antes ("acelera para o rosto até o corte"), durante ("rosto embaixo, card em cima"), depois ("chega de baixo e freia"). Sob tela cheia, nada.
- **Legendas:** marque os itens em que a legenda deve sumir (tela cheia que já mostra a frase).
- **Assets:** peça o que faltar (logo, print, ícone específico), com tipo e termo de busca. Liste `Assets\` antes. Boneco, forma, diagrama: o render desenha.

## Scripts

| Script | O que faz | Armadilhas |
|---|---|---|
| `motion_falas.py` | transcrição por frase no **tempo da sequência** (pelos segmentos do corte), com o tempo exato de cada corte; tempo por palavra sob demanda de um trecho | mesma conversão de frames do corte (30 fps) |

## Passo a passo
1. Status; `motion_mapa` = `em_andamento`.
2. `motion_falas.py`; definir fim do gancho, tipo do vídeo, fechamento (confirmar numa linha).
3. Gancho com storyboard; depois o corpo.
4. **Mostrar o plano numerado:** uma linha por item: tempo, formato, ideia, palavra-gatilho, câmera. Assets pedidos à parte.
5. `aguardando_aprovacao`.

## Revisão
Por número: "mo03 tira", "mo05 em tela cheia", "mo02 outra ideia". Ao regravar, preservar o status do que já foi aprovado. Anotar aprendizados.

## Aprovação
`concluido`, assets pendentes registrados, sugerir `/clear` e `/editar`.

## Regras comuns
Antes de começar, leia `~\.claude\skills\editar\regras-comuns.md` (uma vez por sessão).

## Regras
- Nunca mover, renomear ou apagar arquivos do projeto.
- Falar com a pessoa em português, de forma curta.
```

---

## Arquivo 7 · `~/.claude/skills/edit-assets/SKILL.md`

```markdown
---
name: edit-assets
description: "Busca os arquivos reais que as animações precisam (logos oficiais, ícones, prints de sites) e mostra as opções numa folha numerada para escolher. Pula sozinha se nada faltar; também funciona avulsa ('busca o logo do Notion'). Use em /edit-assets, após a edit-motion-mapa."
---
# edit-assets

Resolve os assets pedidos no plano de animação. Não desenha nada (boneco, forma e diagrama são do render).

- **Lê:** assets pedidos no bloco `motions`, a pasta `Assets\`, a biblioteca fixa `_ferramentas\assets\` (logos já usados).
- **Escreve:** arquivos em `_CLAUDE\assets\` (os escolhidos; nunca dentro de `Assets\`, que é da pessoa), folhas de contato em `_CLAUDE\assets\folhas\`, `_CLAUDE\assets\creditos.txt`, status de cada asset no estado.
- **Próximo passo:** `/edit-motion-render`

Se `edit-motion-mapa` não estiver `concluido`, pare e sugira terminá-la. Se nenhum asset foi pedido, marque `nao_precisa` e siga.

## Ofício (guia completo: `fluxo-de-edicao\guias\etapa-assets.md`)

| Precisa de… | Quem resolve |
|---|---|
| logo de marca/ferramenta, ícone, print de página pública | esta etapa |
| boneco, forma, cena, diagrama | o render |
| tela logada, gravação pessoal, foto da pessoa | a pessoa (só lembrar, com o nome de arquivo esperado) |
| foto/vídeo de banco | só com fonte de licença clara; senão, a pessoa manda |

- **Fontes:** logos: biblioteca local → SVG oficial → Iconify (`logos`, `devicon`). Ícones: Iconify (Lucide, Phosphor, Tabler…), SVG de uma cor, recolorido no render. Print: navegador headless em 1080x1920 ou 1920x1080, página como ela abre. Pesquise as fontes e licenças atuais antes de escrever o script.
- **Folha de contato numerada por rodada** (uma imagem, opções lado a lado sobre o fundo `#0E0D0C`). Descartar antes: marca errada, logo antigo, print com banner de cookies. A opção 1 é a recomendação. A pessoa responde por número ("a1 1, a3 2, a4 eu mando").
- **Licenças:** logo oficial sem redesenhar; nunca um "parecido". Ícones de coleção aberta; CC BY vai para `creditos.txt`. Imagem sem licença clara não entra.
- Perguntar quais logos vão para a biblioteca fixa.

## Scripts

| Script | O que faz | Armadilhas |
|---|---|---|
| `assets_buscar.py` | busca por tipo e termo, baixa candidatos | conferir licença; SVG antes de PNG |
| `assets_folha.py` | folha de contato numerada | fundo escuro do perfil |

## Resumo no chat
O que entrou (arquivo, fonte, licença), o que faltou e o que a pessoa precisa mandar. O render usa um marcador "ASSET FALTANDO" no lugar do que faltar.

## Regras comuns
Antes de começar, leia `~\.claude\skills\editar\regras-comuns.md` (uma vez por sessão).

## Regras
- Nunca mover, renomear ou apagar arquivos do projeto.
- Falar com a pessoa em português, de forma curta.
```

---

## Arquivo 8 · `~/.claude/skills/edit-motion-render/SKILL.md`

```markdown
---
name: edit-motion-render
description: "Constrói e renderiza cada animação aprovada no plano (HyperFrames: HTML, CSS e GSAP virando vídeo), no estilo do perfil, e mostra cada uma para revisar por número (mo04 mais lento). Pode ir por trecho. Use em /edit-motion-render, após a edit-assets."
---
# edit-motion-render

A etapa mais longa. Constrói cada item aprovado do plano e renderiza (**parada de revisão 3**: a pessoa assiste cada animação).

- **Lê:** itens aprovados do bloco `motions`, assets resolvidos; perfil `identidade.md`, `estilo-motion.md`, peças-base (se houver); `fluxo-de-edicao\guias\oficio-motion.md` e `hyperframes.md` (**ler as armadilhas antes de construir**).
- **Escreve:** um projeto HyperFrames por animação em `_CLAUDE\motion\moNN\`; renders em `_CLAUDE\motion\renders\moNN_vNN.(mp4|mov)`; eventos para o som em `_CLAUDE\motion\eventos.json`; registro no estado; prévia de revisão em `Renders\<projeto>_motion_vNN.mp4`.
- **Próximo passo:** `/edit-camera`

Se `edit-motion-mapa` não estiver `concluido` (e `edit-assets` `concluido` ou `nao_precisa`), pare e sugira terminar.

## Ofício (guia completo: `fluxo-de-edicao\guias\etapa-motion-render.md`)
- **HyperFrames:** `npx --yes hyperframes@<versão> …` (Node.js já instalado). A versão mais nova a cada vídeo novo, **fixa dentro do vídeo** (anotada no estado). Confira comandos e opções na documentação da versão em uso.
- **Composição vertical 1080x1920, 30 fps.** Duração exata da raiz: `(N − 0,5) / 30`, com N = frames que a animação ocupa na sequência.
- **Fonte Sora embutida** por `@font-face` com arquivo local de `_ferramentas\perfil\fontes\` (baixar do Google Fonts na primeira vez, licença OFL). Nunca `<link>` externo.
- **Tela cheia:** cena completa, fundo `#0E0D0C`, câmera virtual viva (aproximação + deriva em camadas separadas), chegada já em movimento e saída própria. Saída MP4.
- **Card / ênfase sobre o rosto:** fundo transparente, só os elementos, dentro da zona segura do Reels. Saída **MOV ProRes 4444 com alfa** (o FFmpeg do Gyan tem `prores_ks`).
- **Gatilhos:** tempo 0 = início do item na sequência; cada elemento entra em `tempo_da_palavra − início`.
- **Uma coisa protagonista por vez;** a palavra-chave em laranja com punch na hora dita; listas item a item na palavra; flash curto só nos ápices (máx. 2–3 por reel).
- **Ciclo de cada animação:** `check` → `snapshot` na entrada, no ápice e na saída → **olhar** (texto legível, ≥28 px em 1080p, nada fora da zona segura, transparência certa) → render em fila, em segundo plano → conferir o arquivo (resolução, fps, número de frames, alfa, a última letra de palavra com gradiente).
- **Eventos para o som** anotados durante a construção (o que chega, sai, bate, troca, conta, revela; tempo, tipo descritivo, força, duração).
- **Asset faltando:** marcador visível "ASSET FALTANDO: …" no mesmo tamanho e tempo.
- **Nunca sobrescrever um render:** `_v01`, `_v02`…
- **Peças-base:** o primeiro card, etiqueta ou fundo que ficar bom vira peça reaproveitável (CSS em `_ferramentas\perfil\pecas\`, movimento em JS), com o "sim" da pessoa.

## Scripts

| Script | O que faz | Armadilhas |
|---|---|---|
| `motion_registrar.py` | confere cada render (resolução, fps, frames = duração do item, alfa no MOV) e registra no estado; recusa o que não bater | duração em frames inteiros |
| `motion_previa.py` | prévia de revisão 540x960: o rascunho do corte com as animações por cima nos tempos certos, com o número `moNN` pequeno no canto | sobreposição: extrair o quadro do MOV para PNG antes de compor para conferir (compor direto com `-ss` saiu vazio) |

## Passo a passo
1. Status; `motion_render` = `em_andamento`. Trecho pedido? Construir só os itens que **começam** nele.
2. Para cada item: construir, conferir, renderizar em fila.
3. `motion_registrar.py` → `motion_previa.py`.
4. **Resumo no chat:** itens prontos, o que ficou diferente do storyboard e por quê, assets faltando, caminho da prévia.
5. `aguardando_aprovacao`.

## Revisão
- Por número: "mo04 mais lento", "mo07 tira". Ajustar só os citados, nova versão, nova prévia.
- **Feedback geral** ("básico", "sem vida", "amador"): **refazer**, não retocar. Reler as referências (`perfil\referencias\fichas.md` e as folhas), dizer numa frase a nova direção de cada item e construir de novo.
- Mudou a duração? Mudar no plano e na composição e registrar de novo.

## Aprovação
`concluido`. Padrão que se repetiu vira item de `perfil\catalogo.md`; elemento bom vira peça-base (com o "sim"). Sugerir `/clear` e `/editar`.

## Regras comuns
Antes de começar, leia `~\.claude\skills\editar\regras-comuns.md` (uma vez por sessão).

## Regras
- Nunca mover, renomear ou apagar arquivos do projeto.
- Falar com a pessoa em português, de forma curta.
```

---

## Arquivo 9 · `~/.claude/skills/edit-camera/SKILL.md`

```markdown
---
name: edit-camera
description: "Desenha o movimento de câmera no rosto da pessoa (zoom alternando nos cortes, punches nas ênfases, rosto acompanhado, câmera casada com a entrada e a saída das animações), numa lista cam01, cam02… Use em /edit-camera, após a edit-motion-render."
---
# edit-camera

O movimento da câmera do apresentador, feito **depois** das animações prontas. Não renderiza vídeo: gera os movimentos (escala e posição por quadro) que a `edit-render` aplica.

- **Lê:** cortes, motions (tempos, formatos, direção de saída de cada item), eventos de motion; perfil `estilo-camera.md`; `fluxo-de-edicao\guias\etapa-camera.md` (cuidados que pareceram bug).
- **Escreve:** bloco `camera` no estado (base + movimentos `camNN` + travas no rosto); `_CLAUDE\camera\curva_vNN.json` (escala e posição por quadro da sequência); cache do rosto em `_CLAUDE\camera\rosto\`; eventos de câmera para o som; prévia em `Renders\<projeto>_camera_vNN.mp4` (540x960).
- **Próximo passo:** `/edit-legendas`

Se `edit-motion-render` não estiver `concluido`, pare e sugira terminá-la.

## Ofício
- **Base automática (script):** a cada corte, alterna plano aberto (100%) e fechado (~125–135%, do perfil); aproximação lenta por baixo (~5–10% por segmento) numa camada própria; o plano de cada corte parte de onde a câmera **de fato** terminou; aviso se um corte muda menos de 5%. O primeiro segmento começa aberto.
- **Casar com as animações:** antes de uma tela cheia que entra no corte, acelerar para o rosto nos últimos 0,2–0,25 s (`power4.in`, +7 a +18%); depois de uma saída rápida, chegar na mesma direção e frear (`expo.out`, 0,4–0,6 s). Card em cima → rosto desce; card embaixo → rosto sobe (`power2.inOut`, ~0,7 s). Sob tela cheia, nada.
- **Ênfases:** punch seco (+6 a +12%, ~0,3 s, `power2.out`) na palavra de ápice; variar a forma; trava no rosto com zoom ≥120% de corte a corte.
- **Vertical e selfie de perto:** olhos a ~35–40% da altura; nunca cortar o queixo de forma estranha; nunca mostrar a borda do quadro (deslocamento limitado pela margem). O material 4K dá ~2x de margem em 1080x1920.
- **Cuidados:** sem salto de escala no meio de um take visível; todo movimento parte de onde a câmera está; não inverter a direção no corte; uma coisa por vez em cada camada.
- **Rosto:** detector YuNet (OpenCV, já instalado em `.venv`; baixar o modelo `.onnx` oficial do OpenCV Zoo na primeira vez, com o "sim"), rodado nos proxies, resultado em cache.

## Scripts

| Script | O que faz | Armadilhas |
|---|---|---|
| `camera_rosto.py` | YuNet nos proxies → posição e tamanho do rosto por quadro (cache) | suavizar; quadros sem rosto mantêm o último |
| `camera_curva.py` | base + movimentos + travas → escala/posição por quadro, com as curvas embutidas; avisos de conflito e de borda | curvas iguais às do `oficio-motion.md` §2; limitar pela margem |
| `camera_previa.py` | prévia 540x960 com a câmera aplicada e as animações por cima, `camNN` no canto | mesma função de aplicar que o render final usa |

## Passo a passo
1. Status; `camera` = `em_andamento`.
2. Rosto (cache) → rascunho dos movimentos numerados (`camNN`: o quê, onde, duração, curva, de quanto a quanto).
3. `camera_curva.py` → `camera_previa.py`.
4. **Resumo no chat:** quantos movimentos, avisos, caminho da prévia. `aguardando_aprovacao`.

## Revisão
Por número: "cam07 mais lento", "cam05 mais forte", "cam12 tira". Se uma animação mudar depois, recalcular. Anotar aprendizados.

## Aprovação
`concluido`; sugerir `/clear` e `/editar`.

## Regras comuns
Antes de começar, leia `~\.claude\skills\editar\regras-comuns.md` (uma vez por sessão).

## Regras
- Nunca mover, renomear ou apagar arquivos do projeto.
- Falar com a pessoa em português, de forma curta.
```

---

## Arquivo 10 · `~/.claude/skills/edit-legendas/SKILL.md`

```markdown
---
name: edit-legendas
description: "Cria as legendas animadas palavra por palavra (fonte Sora, palavra falada em laranja), desviando das animações e da área de botões do Reels. Use em /edit-legendas, após a edit-camera."
---
# edit-legendas

Legenda queimada no vídeo, no ritmo da fala. Em Reels a maioria assiste sem som: a legenda precisa ser lida de relance.

- **Lê:** tempo por palavra (só dos trechos que ficaram no corte, no tempo da sequência), motions (onde há tela cheia ou card, e quais itens pedem esconder a legenda), curva da câmera (onde o rosto está), `briefing.md` (grafia de termos); perfil `identidade.md`, `estilo-motion.md` (seção Legendas).
- **Escreve:** bloco `legendas` no estado (grupos `lgNN` com palavras e tempos); projeto HyperFrames em `_CLAUDE\legendas\` e o render `_CLAUDE\legendas\legendas_vNN.mov` (ProRes 4444 com alfa, 1080x1920, duração da sequência inteira); prévia em `Renders\<projeto>_legendas_vNN.mp4`.
- **Próximo passo:** `/edit-sfx`

Se `edit-camera` não estiver `concluido`, pare e sugira terminá-la.

## Ofício
- **Grupos de 1–3 palavras**, quebrados em fronteiras naturais (pausa, vírgula, fim de ideia); nunca separar artigo do substantivo nem número da unidade.
- **Palavra por palavra:** o grupo aparece com a palavra atual em laranja `#FF6B2C` e as outras em creme `#F4EFE6`; a cor passa para a próxima **na hora em que ela é dita** (tempo do WhisperX). Entrada do grupo rápida (~6–8 q, `power3.out`, leve escala), sem saída longa.
- **Fonte:** Sora 600–700, tamanho que caiba em 2 linhas no máximo dentro de ~76% da largura; contorno ou sombra discreta escura para ler sobre a parede clara.
- **Posição:** um pouco abaixo do centro, **acima dos 20% de baixo** e fora da faixa direita dos botões. Se um card entrar embaixo, a legenda sobe para não colidir; sob tela cheia que já mostra o texto, a legenda some.
- **Grafia:** a da transcrição corrigida no preparar; nunca trocar o que foi dito. Números falados por extenso podem virar algarismos ("três" → "3") se ajudam a leitura (tendência).
- Hesitação que ficou no corte não aparece na legenda.

## Scripts

| Script | O que faz | Armadilhas |
|---|---|---|
| `legendas_grupos.py` | palavras da sequência → grupos `lgNN` com tempos; regras de quebra | só palavras que estão dentro dos segmentos do corte |
| `legendas_render` (projeto HyperFrames gerado) | uma composição com todos os grupos, cada um no seu tempo | fonte Sora local; texto ≥ 56 px em 1080x1920; zona segura |

## Passo a passo
1. Status; `legendas` = `em_andamento`.
2. Grupos → **teste curto** (os primeiros ~5 s) para a pessoa aprovar o visual na **primeira vez de todas**; anotar em `_ferramentas\validacao.md`.
3. Render completo em segundo plano → prévia 540x960 (corte + câmera + animações + legenda).
4. **Resumo:** quantos grupos, palavras com grafia duvidosa, caminho da prévia. `aguardando_aprovacao`.

## Revisão
Por número ou tempo: "lg12 junta com o lg13", "em 0:20 escreve 'IA' em maiúsculo". Anotar aprendizados (estilo de legenda é gosto: vai para o perfil).

## Aprovação
`concluido`; sugerir `/clear` e `/editar`.

## Regras comuns
Antes de começar, leia `~\.claude\skills\editar\regras-comuns.md` (uma vez por sessão).

## Regras
- Nunca mover, renomear ou apagar arquivos do projeto.
- Falar com a pessoa em português, de forma curta.
```

---

## Arquivo 11 · `~/.claude/skills/edit-sfx/SKILL.md`

```markdown
---
name: edit-sfx
description: "Coloca os efeitos sonoros (whoosh, impacto, pop, clique, glitch…) alinhados ao que aparece na tela, em camadas e nos níveis certos, e encaixa a música escolhida por baixo da voz. Lista sfx01, sfx02… Use em /edit-sfx, após a edit-legendas."
---
# edit-sfx

O som entra por último, sobre o corte, as animações, a câmera e as legendas já travados. Decide **onde** entra som, **qual** e **como se empilha**; encaixa a música.

- **Lê:** eventos de corte, câmera e motion (`_CLAUDE\motion\eventos.json`, eventos de câmera), batidas do storyboard; música em `Assets\` (indicada no briefing); biblioteca `_ferramentas\sons\` e seu índice; perfil `estilo-som.md`; `fluxo-de-edicao\guias\etapa-sfx.md`.
- **Escreve:** bloco `sfx` no estado (itens `sfxNN`: evento, camada, arquivo, alinhamento, nível); `_CLAUDE\sfx\mix_vNN.json` (o que o render vai somar); lista de sons que faltam em `_ferramentas\sons\faltando.md`.
- **Próximo passo:** `/edit-render`

Se `edit-legendas` não estiver `concluido`, pare e sugira terminá-la.

## Ofício
- **O som segue o que se vê.** Entrada de tela cheia, card, punch visível, chegada rápida de câmera: som. Jump cut sem mudança de plano, aproximação lenta, trava no rosto, câmera sob tela cheia: sem som.
- **Um protagonista por momento;** no máximo 3 camadas (principal, grave só nos fortes, apoio/doce).
- **Escalada:** o maior impacto do reel vai no ápice do começo; nada maior no corpo.
- **Antecipação:** riser termina no evento e corta seco; ~0,5 s de respiro antes do impacto.
- **Sincronia:** impacto no frame do evento; o **pico** do whoosh no frame da troca/chegada; pop, clique e ding começam no evento; 1 frame adiantado é aceitável.
- **Material:** tecnologia/IA pede glitch e interface digital; pílula laranja pede pop suave; número contando pede tique.
- **Variação:** não repetir o mesmo arquivo em ~20 s.
- **Música:** escolhida pela pessoa; encaixar com fade de entrada e saída e **ducking** sob a voz (sidechain ou envelope pelo tempo das falas). Sem música indicada: perguntar; não escolher sozinho.
- **Níveis:** ponto de partida no perfil (`estilo-som.md`); nada mais alto que a voz, salvo intenção.

## Biblioteca (primeira vez)
A pasta `_ferramentas\sons\` começa vazia. Na primeira vez: propor as categorias que faltam (whoosh, impacto, grave, riser, pop, clique, glitch, interface, ding), pesquisar fontes de sons **gratuitos com licença clara para uso comercial**, mostrar as fontes e licenças à pessoa e **baixar só com o "sim"**. Indexar com `sfx_indexar.py`.

## Scripts

| Script | O que faz | Armadilhas |
|---|---|---|
| `sfx_indexar.py` | índice da biblioteca: duração útil, tempo até o pico, forma, brilho | pular o silêncio do começo |
| `sfx_eventos.py` | junta todos os eventos no tempo da sequência, com id estável e a palavra dita; mede a loudness da voz (LUFS) | ids que não mudam se o evento não mudou |
| `sfx_montar.py` | alinha cada som (pico, início ou fim), calcula o nível, gera `mix_vNN.json` e uma prévia de áudio; avisa: som não achado, repetido, evento sem principal | nada sobreposto na mesma trilha |

## Passo a passo
1. Status; `sfx` = `em_andamento`. Biblioteca vazia? Montar primeiro (acima).
2. `sfx_eventos.py` → rascunho dos sons (`sfxNN`) → `sfx_montar.py`.
3. Prévia rápida 540x960 com o áudio mixado. **Resumo:** quantos sons, onde está o impacto principal, o que faltou. `aguardando_aprovacao`.

## Revisão
"sfx12 mais baixo" (−3 dB), "sfx05 tira", "troca o sfx07", "sfx03 um frame antes", "música mais baixa". Anotar aprendizados.

## Aprovação
`concluido`; sugerir `/clear` e `/editar`.

## Regras comuns
Antes de começar, leia `~\.claude\skills\editar\regras-comuns.md` (uma vez por sessão).

## Regras
- Nunca mover, renomear ou apagar arquivos do projeto.
- Falar com a pessoa em português, de forma curta.
```

---

## Arquivo 12 · `~/.claude/skills/edit-render/SKILL.md`

```markdown
---
name: edit-render
description: "Junta tudo (corte dos originais em 4K com a cor convertida, câmera, animações, legendas, efeitos e música) e exporta o MP4 final do Reels em 1080x1920, pronto para postar. Use em /edit-render, após a edit-sfx."
---
# edit-render

A montagem final com FFmpeg (**parada de revisão 4**). Como a pessoa não usa programa de edição, este é o vídeo que vai para o Instagram.

- **Lê:** cortes (tempo de origem), curva da câmera, renders de motion registrados, legendas, `mix_vNN.json`; os **originais** de `Brutos\` (não os proxies).
- **Escreve:** `Renders\<projeto>_final_vNN.mp4` (1080x1920, 30 fps, H.264 High, AAC 48 kHz, `+faststart`); rascunho `Renders\<projeto>_rascunho_vNN.mp4` (540x960) quando pedido; registro no estado.
- **Próximo passo:** fim. A pessoa posta (ou pede ajustes).

Se `edit-sfx` não estiver `concluido`, pare e sugira terminar.

## Ofício (guia: `fluxo-de-edicao\guias\entrega-no-editor.md`, caminho C)
- **Cor:** cada segmento dos originais HDR → tonemap SDR BT.709 (regras comuns §2) **antes** de qualquer escala. Conferir um quadro: pele natural, parede sem estourar.
- **Câmera:** escala e posição por quadro aplicadas no original (4K vertical) e só depois reduzido para 1080x1920 — o zoom não perde nitidez até ~180%.
- **Camadas, de baixo para cima:** câmera → telas cheias (MP4) → cards/sobreposições (MOV com alfa) → legendas (MOV com alfa).
- **Áudio:** voz dos segmentos com crossfade de 2 frames nas junções; efeitos e música do `mix_vNN.json`; loudness final ~-14 LUFS integrado, pico ≤ -1 dBTP (padrão de plataformas sociais).
- **Tempo em frames inteiros** a 30 fps, numa função só, compartilhada com os outros scripts.
- **Nunca sobrescrever:** `_final_v01`, `_v02`…
- **Revisão rápida:** a pessoa pode pedir o rascunho 540x960 para revisar mais rápido; o final sai em resolução cheia.

## Scripts

| Script | O que faz | Armadilhas |
|---|---|---|
| `render_final.py` | monta tudo a partir do estado; `--rascunho` para 540x960 | HDR→SDR antes de escalar; alfa do ProRes 4444 preservado no overlay; duração final = soma dos segmentos |
| `render_conferir.py` | confere o arquivo: resolução, fps, duração, loudness, picos; extrai 4 quadros (começo, ápice, meio, fim) para olhar | comparar duração com a sequência |

## Passo a passo
1. Status; `render` = `em_andamento`. **Primeira vez de todas:** render de teste com os primeiros ~5 s; a pessoa confere cor, nitidez e som no **celular**; anotar em `_ferramentas\validacao.md`.
2. Render completo (segundo plano; avisar quanto tempo deve levar no PC dela).
3. `render_conferir.py` + olhar os quadros.
4. **Resumo:** caminho do arquivo, duração, tamanho, o que conferir. Sugerir assistir no celular. `aguardando_aprovacao`.

## Revisão
A pessoa cita o tempo do vídeo final ("em 0:12…") ou o número do item. Converter, mandar o pedido para a etapa certa (corte, motion, câmera, legenda, som) se for preciso, e renderizar de novo. Pedir para juntar os ajustes. Anotar aprendizados.

## Aprovação
`concluido`. Lembrar quantos aprendizados ficaram anotados e oferecer `/editar aprendizados` para o próximo vídeo sair melhor.

## Regras comuns
Antes de começar, leia `~\.claude\skills\editar\regras-comuns.md` (uma vez por sessão).

## Regras
- Nunca mover, renomear ou apagar arquivos do projeto.
- Falar com a pessoa em português, de forma curta.
```

---

## Arquivos 13 a 17 · perfil de estilo de exemplo

Estes cinco vão para `<sua pasta de vídeos>\_ferramentas\perfil\`. São o gosto de quem montou este fluxo (escuro com laranja, Reels). Use como ponto de partida e troque pelo seu com `/fluxo-de-edicao`. As referências citadas (ref01 a ref06) não vêm junto.

### Arquivo 13 · `_ferramentas/perfil/identidade.md`

```markdown
# Identidade visual — perfil principal

Origem: escolhida pela pessoa em 2026-11-20 (opção 1 de 3 propostas), para a direção A · cinema escuro. Tendência, não regra: ajuste quando houver marca própria.

## Cores

| Papel | Cor | Uso |
|---|---|---|
| Fundo | `#0E0D0C` (quase preto, levemente quente) | fundo das telas cheias, base dos cards |
| Superfície | `#1C1A18` | cards, pílulas neutras, painéis |
| Superfície de destaque | `#2A1A10` | fundo de etiqueta e pílula em destaque (laranja bem escuro) |
| **Destaque** | **`#FF6B2C`** (laranja energia) | palavra-chave, etiquetas, ícones ativos, linhas, brilho |
| Texto | `#F4EFE6` (creme) | todo texto principal |
| Texto secundário | `#F4EFE6` a ~60% | legendas pequenas, rótulos |

- **Uma cor de destaque só.** O laranja marca o que importa: a palavra-chave da frase, o item ativo de uma lista, o número. Se tudo for laranja, nada se destaca.
- Variações do laranja só para luz e brilho (mais claro `#FF8A55`, mais escuro `#C24A17`), nunca como segunda cor de destaque.
- Sobre o apresentador, o texto fica creme e a ênfase laranja. Fundo de card escuro e translúcido, para ler sobre qualquer imagem.

## Fonte
- **Sora** (Google Fonts, licença OFL, livre para uso comercial). Pesos: 400 para texto corrido e rótulos, 600–700 para títulos e palavras-chave.
- Baixar os arquivos da fonte para `_ferramentas\perfil\fontes\` na primeira vez que o motion-render rodar e embutir no projeto (`@font-face` com arquivo local; nunca `<link>` externo, que mudou o desenho das letras no render, ver `guias/hyperframes.md`).
- Texto mínimo 28 px em 1080p, dentro da área segura (80% do quadro).

## Material
- Cards de **vidro escuro**: preenchimento sólido `#1C1A18` com borda fina mais clara e um leve gradiente de luz. Sem `backdrop-filter` em card com texto animado (armadilha do HyperFrames).
- Pílulas arredondadas para etiquetas ("passo 1", "grátis").
- Ícones de linha (traço fino, uma cor), recoloridos em creme ou laranja.
- Brilho laranja sutil em ênfases (camada própria atrás do texto, nunca `drop-shadow` em texto com gradiente).

## Logo
- Sem logo. Identificador de nome (lower third) só com o nome da pessoa em Sora e um detalhe laranja.

## Nunca usar
- O verde neon da ref01 nem qualquer elemento de marca das referências.
- Segunda cor de destaque concorrendo com o laranja.
- Fundo branco ou claro em tela cheia (a direção é escura).
```

### Arquivo 14 · `_ferramentas/perfil/estilo-motion.md`

```markdown
# Estilo de motion — perfil principal

Origem: 6 referências medidas em 2026-11-20, direção A · cinema escuro (ref01, ref04) escolhida pela pessoa. Tendência, não regra. Mesma numeração do `guias/oficio-motion.md`; onde este arquivo não fala, vale o ofício.

**Formato principal: Reels/Shorts vertical 9:16 (1080x1920), ~30–90 s.** As referências são horizontais: o *jeito de mover* vem delas, o *layout* é adaptado ao vertical.

## §0 Princípio
- Cada ideia falada vira uma metáfora visual própria (estrada que troca de estilo, terminal digitando, "8 etapas" numeradas). Nada de template repetido. (ref01)
- Em reel curto, o vídeo inteiro se comporta como gancho: densidade alta do começo ao fim, com um respiro (só o rosto) a cada ~6–10 s.

## §1 Velocidade
- Entradas de elemento rápidas: duração mediana medida de **~0,3–0,5 s**. Usar ~12–15 q a 30 fps, como o ofício.
- Saídas mais rápidas que as entradas (~8 q).
- Planos curtos: nas referências, uma tela nova a cada **~1,3–1,7 s**. Em reel, mirar troca visual (corte, card, tela cheia ou zoom) a cada **1,5–3 s**.

## §2 Curvas
- A medição de curvas foi inconclusiva. Usar as curvas do ofício (§2): entrada `expo.out`/`power3.out`, saída `power3.in`, câmera virtual `power2.out` + deriva linear.
- Overshoot: leve (`back.out` só em pílulas e etiquetas pequenas, ~1 em cada 3 entradas).

## §3 Câmera virtual (dentro das animações de tela cheia)
- Sempre viva: aproximação + deriva em camadas separadas (ofício §3), com zoom contínuo de 20–60% no plano.

## §4 Troca de cena
- **Flash e explosão** como pontuação: flash curto (2–3 q) na entrada de tela cheia de ápice, no máximo 2–3 por reel.
- Speed ramp entre telas cheias encadeadas (ofício §4). Wipe de barra laranja como transição alternativa.

## §5 Entradas e saídas
- Listas e passos entram **item a item, na palavra**.
- Palavra-chave da frase em laranja `#FF6B2C`, com punch na hora em que é dita.

## §9 Sobre o apresentador (adaptado ao vertical)
- No vertical, **o card entra em cima (terço superior) ou embaixo (acima da zona segura)** e a câmera desloca o rosto para o lado oposto.
- **Zona segura do Reels:** nada importante nos ~20% de baixo nem na faixa direita (~12%) dos botões; nada nos ~8% de cima.
- Identificador de nome (lower third) curto, no começo, em Sora + detalhe laranja.

## Densidade por trecho

| Trecho | Densidade |
|---|---|
| Primeiros 3–5 s | máxima: tela cheia ou card forte já no 1º segundo |
| Corpo | uma animação a cada ~3–5 s, alternando tela cheia, card e só ênfase |
| Fechamento / CTA | um card de chamada claro, com tempo de leitura |

## Legendas
- Palavra por palavra, Sora 600–700, creme `#F4EFE6`; a palavra falada em laranja. 1–3 palavras por vez. Posição: um pouco abaixo do centro, acima da zona segura de baixo. Somem sob tela cheia que já tenha o mesmo texto.
```

### Arquivo 15 · `_ferramentas/perfil/estilo-camera.md`

```markdown
# Estilo de câmera — perfil principal

Origem: medição de 2026-11-20 (transformação afim global quadro a quadro), referências da direção A. Tendência, não regra.

## Quanto movimento
- A câmera **quase nunca para**: nas referências escolhidas ela anda em ~70% do tempo de cada plano. **Alvo: câmera andando ~60–70% do tempo.**
- **Punch frequente:** cerca de metade dos planos tem variação de zoom acima de 5% em 0,5 s. Alvo: punch em ~1 de cada 2 cortes, variando a forma (seco, com speed ramp, recuo).

## Valores
- Plano aberto 100%; **plano fechado ~125–135%** (material 4K vertical dá ~2x de margem em 1080x1920).
- Aproximação lenta por baixo: ~5–10% por segmento.
- Punch de ênfase: +6 a +12% em ~0,3 s.
- Trava no rosto (YuNet) nas ênfases, com zoom ≥120%.

## Vertical
- Selfie de perto: no plano fechado, conferir que o queixo e o topo da cabeça não cortam feio; olhos a ~35–40% da altura.
- Quando um card entra em cima, a câmera desce o rosto; quando entra embaixo, sobe.
```

### Arquivo 16 · `_ferramentas/perfil/estilo-cortes.md`

```markdown
# Estilo de cortes — perfil principal

Origem: entrevista de 2026-11-20 + medição das referências. Tendência, não regra.

- **Ritmo apertado:** quase sem ar entre as frases. Folga de borda ~0,06–0,1 s (só para não comer sílaba).
- Planos curtos: um corte ou mudança de plano a cada ~1,5–3 s, contando os zooms da câmera.
- Hesitações e recomeços claros saem; na dúvida, não corta e marca.
- Notas faladas: "nota de edição" … "fim da nota" saem do corte.
- Material em vários clipes curtos: a ordem é a do nome (`parte 1`…), e cada parte pode ter sobra no começo e no fim (respiração, olhar para o celular) que deve sair.
```

### Arquivo 17 · `_ferramentas/perfil/estilo-som.md`

```markdown
# Estilo de som — perfil principal

Origem: entrevista de 2026-11-20. Tendência, não regra; ajusta pelo feedback.

- **Densidade alta, mas variada:** som em toda entrada de tela cheia e em todo punch visível; pop ou clique nas pílulas e itens de lista; nada nos jump cuts sem mudança de plano.
- **Peso:** um impacto grande só no ápice do gancho. No resto, sons médios e leves.
- **Sons preferidos:** whoosh curto e brilhante, impacto com grave nas telas cheias, glitch e interface digital para temas de tecnologia, pop suave nas etiquetas laranja.
- **Música:** escolhida pela pessoa; abaixada sob a voz (ducking) com fade de entrada e saída.
- **Níveis (ponto de partida):** voz ~-14 LUFS integrado para Reels, efeitos 12–18 dB abaixo do pico da voz, música ~-24 a -28 LUFS sob a fala.
- Biblioteca: `_ferramentas\sons\` (só sons de licença clara para uso comercial).
```
