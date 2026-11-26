# Regras de ofício — valem para todo estilo

Adaptado do pacote de skills de edição da agência. Onde o guia do estilo falar diferente, vale o guia do estilo. Onde a identidade do cliente ou os aprendizados falarem diferente, valem eles.

## 0. Como você trabalha aqui
- Você não assiste ao vídeo. Você lê: o **inventário** (duração, orientação, HDR, som), a **transcrição por frase** com o tempo de cada palavra, as **trocas de cena**, os **silêncios**, e olha as **folhas de quadros** (miniaturas numeradas com o arquivo e o tempo gravados embaixo de cada quadro).
- Abra as folhas de quadros com a ferramenta Read. Elas são a sua única visão da imagem: use para escolher os melhores trechos, saber onde está o rosto (para o `foco` da câmera) e evitar quadro tremido, escuro ou fora de foco.
- Todo tempo de corte é no **tempo do arquivo de origem** (ex.: `f12`, de 3,20 s a 6,85 s). O sistema converte para o tempo do vídeo final.
- Você entrega **um arquivo: `plano.json`** (esquema em `guias/plano.md`). O sistema valida, corrige o que der, e renderiza. Não existe outra forma de editar.

## 1. Formato e zona segura (Reels/Shorts/TikTok vertical 9:16)
- Saída 1080x1920, 30 fps. Outros formatos (4:5, 1:1, 16:9) seguem as mesmas ideias.
- **Zona segura:** nada importante nos ~8% de cima, ~20% de baixo e ~12% da direita (botões do app). As posições com nome (`topo`, `centro`, `baixo`) já respeitam isso.
- Material horizontal num vídeo vertical: o sistema recorta o centro (ou o `foco` que você der). Escolha o foco com cuidado: o assunto precisa caber na faixa vertical.

## 2. Cortes (edit-cortes)
- **Leia por frase, decida por frase.** Nunca corte no meio de uma frase; corte entre ideias e takes.
- **Takes repetidos:** frases repetidas ou parecidas, recomeços, frases abandonadas, "pera", "de novo". Escolha, nesta ordem: o que a nota de edição pedir; o último take completo; um anterior limpo se o último tropeçou.
- Antes de descartar uma frase como incompleta, procure se o take bom não está logo adiante.
- **Sobras de cada arquivo:** começo e fim de cada clipe costumam ter respiração, olhar para o celular, mão no botão: saem.
- **Hesitações:** "é…", "hum", "tipo" isolados por silêncio saem. Colados na fala, ficam.
- **Notas de edição faladas** (linhas marcadas ⚑NOTA DE EDIÇÃO, de "nota de edição" até "fim da nota"): siga o pedido e tire o trecho inteiro do vídeo.
- **Folga de borda:** comece ~0,06–0,10 s antes da primeira palavra e termine ~0,06–0,10 s depois da última. Não coma sílaba (use o tempo das palavras: o início do segmento ≤ início da 1ª palavra − 0,06; o fim ≥ fim da última palavra + 0,08, sem invadir a palavra seguinte que você quer cortar).
- **Ritmo apertado:** quase sem ar entre frases. Um corte ou mudança de plano a cada ~1,5–3 s, contando os zooms.
- **Ordem:** a gravada. Uma frase de impacto que funcionaria melhor no começo pode ser movida para o gancho — isso é permitido, mas avise em "resumo".
- **Na dúvida, não corte** e anote em "avisos".

## 3. O gancho (os primeiros 3 segundos decidem tudo)
- O vídeo inteiro se comporta como gancho: densidade alta do começo ao fim.
- Nos primeiros 3–5 s: a frase mais forte, um título ou elemento forte já no 1º segundo, movimento de câmera. Nada de "oi, tudo bem, hoje eu vou…" se der para cortar direto para o assunto.
- Antes de fechar o gancho, pergunte-se: o conceito nasce desta fala ou serviria para qualquer vídeo? Estou repetindo a mesma fórmula?
- Descreva o gancho em uma frase no campo `gancho`.

## 4. Câmera (edit-camera)
- **A câmera quase nunca para:** alvo de movimento em ~60–70% do tempo.
- **Alternar a cada corte:** plano aberto (zoom 1,0) e fechado (~1,25–1,35). Um corte que muda menos de 5% de zoom parece erro: ou muda bem, ou não muda.
- **Aproximação lenta** dentro do segmento (+5–10%: `"zoom": [1.0, 1.07]`).
- **Punch** na palavra de ápice: +6 a +12% (`"punches": [{"t": <tempo de origem da palavra>, "forca": 0.08}]`). Cerca de 1 a cada 2 cortes, variando.
- **Foco (rosto):** `"foco": [x, y]` em 0–1 do quadro de origem. Em selfie vertical os olhos ficam a ~35–40% da altura: um foco típico é `[0.5, 0.38]`. Nunca corte o queixo de forma estranha. Olhe as folhas para acertar.
- Foto parada parece erro: o sistema já aplica um Ken Burns leve; você pode definir outro zoom.

## 5. Animações (os `elementos`)
- **O conteúdo decide a animação.** Para cada ideia, a forma visual que explica melhor aquela ideia. Repertório, não obrigação.
- **Onde entra:** passo a passo, comparação, antes e depois, números, listas, preço, a frase-chave, nome de quem fala, endereço. **Não entra** para enfeitar fala que se sustenta sozinha.
- **Componentes premium:** se existir `guias/motion.md`, os componentes de lá (janela, contador, apps, passos, cards, comparação, comentário…) são o padrão; os elementos simples ficam para ênfases rápidas.
- **Ritmo:** depois do gancho, algo novo na tela a cada ~2–4 s (componente, ênfase, punch ou corte), alternando cartão, tela cheia e só ênfase, com um respiro curto (1–2 s, só o rosto) a cada ~8–10 s.
- **Palavra-gatilho:** cada animação entra no tempo exato da palavra (âncora `{"arquivo": "f12", "t": <início da palavra>}`). Listas: cada item entra na palavra dele.
- **Duração:** mínimo ~1,5 s; típico 2–5 s; com texto, segure ~1 s depois da última palavra.
- **Uma protagonista por vez.** Não empilhe duas animações no mesmo lugar ao mesmo tempo. Se a legenda está embaixo, cartões vão em cima (`topo`).
- **Uma cor de destaque só:** a palavra-chave em `destaque`. Se tudo for destaque, nada se destaca.
- **Flash** (tipo `flash`) só nos ápices: no máximo 2–3 por vídeo.
- Textos na tela: curtos (≤ 6 palavras num título), sem ponto final, português correto, sem inventar promessa que o cliente não fez.

## 6. Legendas (edit-legendas)
- Em Reels a maioria assiste sem som: legenda ligada sempre que houver fala (estilos de fala).
- O sistema agrupa 1–3 palavras e acende a palavra falada sozinho. Você controla:
  - `correcoes`: grafia errada da transcrição → certa (`{"barbella": "barbearia", "agradecer": "degradê"}`). **Só grafia**; nunca mude o que foi dito.
  - `destaques`: palavras-chave que acendem mais forte (preço, número, nome do serviço).
- Sob `tela_cheia`, `citacao` e `palavra` a legenda some sozinha.

## 7. Som (edit-sfx)
- O som segue o que se vê. Cada elemento já tem um som padrão (whoosh na entrada de título, pop nos itens, impacto na palavra de ênfase, ding no preço). Use `"som": "nenhum"` para calar um, ou troque.
- Escalada: o maior impacto do vídeo no ápice do começo; nada maior no corpo.
- **Música:** só a escolhida pela equipe. Com fala, fica baixa (o sistema abaixa sozinho quando alguém fala). Sem fala, é a protagonista: corte na batida (`sincronizar_cortes`).

## 8. Fechamento
- Termine com chamada clara (`cartao_final`): o que fazer agora + como (Instagram, WhatsApp, endereço). Use os dados da identidade do cliente; não invente telefone ou endereço.
- `legenda_post`: o texto do post (2–5 linhas, tom do cliente, chamada no fim, 3–8 hashtags relevantes e locais). Sem prometer o que o vídeo não mostra.
- `capa`: o quadro mais forte (rosto expressivo ou o resultado) + um título curto.

## 9. Cuidados
- Pessoas que não são da equipe (clientes do estabelecimento, crianças) em destaque: prefira outro trecho, a não ser que o briefing diga que há autorização.
- Nada de texto ofensivo, promessa de saúde/resultado garantido, ou dado pessoal na tela.
- Se faltar material para o que o briefing pede, faça o melhor possível e explique em `avisos`.
