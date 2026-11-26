# Motion design — os componentes premium

Além dos elementos simples (`titulo`, `palavra`, `lista`…), você tem um **motor de motion** com componentes de nível de agência: cartões escuros com sombra e brilho na cor da marca, entradas com mola e overshoot, digitação, contagem, ícones e logos reais. **Eles são o padrão desta edição: use-os.** Os simples ficam para ênfases rápidas.

Eles entram no mesmo array `elementos` do plano (mesmos campos `id`, `tipo`, `em`, `duracao`, `posicao`, `som`).

## A regra de ouro: a tela reage à fala

O que viraliza nos vídeos editados com Claude é isto: **cada coisa que a pessoa diz aparece na tela no instante em que ela diz**. Fala "sem CapCut, sem Premiere" → os dois apps aparecem e são riscados. Fala "três passos" → o cartão de passos abre e cada passo entra na palavra dele. Fala "comenta EDIÇÃO" → a caixa de comentário digita EDIÇÃO.

1. **Palavra-gatilho com tempo exato.** `em: {"arquivo": "f1", "t": <início da palavra>}` (pegue de transcricao.txt). Sub-itens (`itens[].em`, `linhas[].em`, `em_acao`) também: cada um na sua palavra.
2. **Densidade.** Depois do gancho, algo novo na tela a cada **2–4 s**: componente, ênfase, punch de câmera ou corte. Nunca mais de ~4 s com a tela parada. Um vídeo de 40 s costuma ter **8–14 componentes** + punches.
3. **Uma protagonista por vez.** Dois cartões nunca dividem o mesmo lugar ao mesmo tempo. O próximo entra quando o anterior sai (a saída leva 0,3 s). `rec` e `pilula` podem conviver com um cartão.
4. **Zona.** Cartões em `topo` (padrão): a legenda fica embaixo e o rosto no meio. `contador` é tela cheia (escurece o fundo, a legenda some). `comentario` fica embaixo (a legenda some enquanto ele está na tela).
5. **Duração.** Segure até ~0,8 s depois da última palavra relacionada. Típico 2,5–5 s. Cartão com itens: até o último item + 1 s.
6. **Variedade.** Não repita o mesmo componente seguido. Alterne cartão grande, ênfase (`palavra`/`pilula`), tela cheia (`contador`) e respiro (só o rosto, 1–2 s).
7. **Verdade.** Só o que foi dito ou está no briefing/identidade. Nada de inventar número, preço, depoimento ou notificação de cliente que não existe. Notificação/gráfico/contador ilustram o que a pessoa FALOU.

## Catálogo

Textos curtos (cabem numa linha). Posição: `topo` (padrão), `centro`, `baixo`, ou `[x, y]` (0–1).

### `janela` — terminal/app digitando (bastidor, "como eu faço", IA, tecnologia)
```json
{"id": "mo01", "tipo": "janela", "em": {"arquivo": "f1", "t": 0.4}, "duracao": 4.5,
 "titulo": "claude — ~/videos/reel",
 "linhas": [{"tipo": "comando", "texto": "edita esse vídeo"}, {"tipo": "progresso", "texto": "editando…"}, {"tipo": "ok", "texto": "pronto"}]}
```
`linhas[].tipo`: `comando` (digita com `>`), `progresso` (barra enchendo), `ok` (✓), `texto`. Cada linha pode ter `em` próprio; sem `em`, entra depois da anterior.

### `contador` — número gigante contando, com brilho (o "100%")
```json
{"tipo": "contador", "em": {"arquivo": "f1", "t": 5.2}, "duracao": 2.8, "de": 0, "ate": 100, "sufixo": "%", "texto": "automático"}
```
Também: `prefixo` ("R$", "+"), `decimais`, `fundo`: `escuro` (padrão, tela cheia) ou `nenhum`. Para o número que é o ápice do vídeo (resultado, desconto, quantidade de clientes).

### `rec` — moldura de câmera (cantos, ● REC piscando, timecode)
```json
{"tipo": "rec", "em": "inicio", "duracao": 3.0}
```
Abre o vídeo com cara de "gravando agora", ou marca o "antes". Combine com uma `pilula` por cima.

### `pilula` — etiqueta com ponto ("● ESTE VÍDEO", "● NOVIDADE", "● PASSO 1")
```json
{"tipo": "pilula", "texto": "este vídeo", "em": {"arquivo": "f1", "t": 1.1}, "duracao": 2.2, "posicao": "topo"}
```

### `apps` — ícones de apps/marcas (riscados, com check ou só aparecendo)
```json
{"tipo": "apps", "em": {"arquivo": "f1", "t": 3.0}, "duracao": 3.2, "acao": "riscar", "em_acao": {"arquivo": "f1", "t": 4.1},
 "itens": [{"logo": "CapCut", "nome": "CapCut", "em": {"arquivo": "f1", "t": 3.0}}, {"logo": "Adobe Premiere Pro", "nome": "Premiere", "em": {"arquivo": "f1", "t": 3.6}}]}
```
`acao`: `riscar` (✗ "sem isso"), `check` (✓ "com isso"), `nenhum`. Até 4 itens.

### `comentario` — "● COMENTE" + caixa digitando a palavra + botão enviar
```json
{"tipo": "comentario", "texto": "edição", "rotulo": "comente", "em": {"arquivo": "f1", "t": 38.2}, "duracao": 3.5}
```
Para a chamada "comenta X que eu te mando". Fica embaixo (a legenda some).

### `passos` — cartão "PASSO A PASSO" com itens numerados (+ bloco de código opcional)
```json
{"tipo": "passos", "texto": "passo a passo", "em": {"arquivo": "f1", "t": 12.0}, "duracao": 6.0,
 "itens": [{"texto": "gravar", "em": {"arquivo": "f1", "t": 12.4}}, {"texto": "mandar pro Claude", "em": {"arquivo": "f1", "t": 13.5}}, {"texto": "pedir a edição", "em": {"arquivo": "f1", "t": 15.0}}],
 "codigo": ["/edit-cortes", "/edit-legendas"]}
```
Até 5 itens. `codigo` (até 3 linhas) só se fizer sentido (comandos, links, cupom).

### `cards` — fileira de cartões com ícone; o destaque passa de um para o outro
```json
{"tipo": "cards", "em": {"arquivo": "f1", "t": 20.1}, "duracao": 4.5,
 "itens": [{"icone": "scissors", "texto": "vídeo de corte", "em": {"arquivo": "f1", "t": 20.3}},
           {"icone": "antes-depois", "texto": "antes e depois", "em": {"arquivo": "f1", "t": 21.4}},
           {"icone": "laptop", "texto": "bastidor", "em": {"arquivo": "f1", "t": 22.6}}]}
```
`icone`: nome Lucide (veja icones.txt) ou o especial `antes-depois`; ou `logo` de marca no lugar. O card cujo `em` chegou acende. `destaque: 1` fixa um.

### `notificacao` — notificação de celular descendo
```json
{"tipo": "notificacao", "app": "WhatsApp", "logo": "WhatsApp", "texto": "Novo agendamento!", "subtexto": "Quero marcar pra sábado às 10h", "em": {"arquivo": "f1", "t": 9.0}, "duracao": 3.0}
```
Sem `logo`, usa a logo do cliente. Só para ilustrar algo que a pessoa contou (não invente cliente).

### `grafico` — barras crescendo com o valor contando; a maior acende
```json
{"tipo": "grafico", "texto": "Alcance por formato", "sufixo": "k", "em": {"arquivo": "f1", "t": 25.0}, "duracao": 4.0,
 "itens": [{"rotulo": "Foto", "valor": 12}, {"rotulo": "Carrossel", "valor": 31}, {"rotulo": "Reels", "valor": 87}]}
```

### `comparacao` — errado × certo, antes × depois
```json
{"tipo": "comparacao", "em": {"arquivo": "f1", "t": 28.0}, "duracao": 4.5,
 "esquerda": {"titulo": "antes", "itens": ["5 horas editando", "legenda na mão"]},
 "direita": {"titulo": "com Claude", "itens": [{"texto": "10 minutos", "em": {"arquivo": "f1", "t": 29.2}}, "legenda automática"]}}
```

### `seguir` — chamada final: perfil + botão Seguir com cursor clicando e corações
```json
{"tipo": "seguir", "texto": "@barbeariadoze", "subtexto": "dicas de corte toda semana", "em": {"arquivo": "f1", "t": 41.0}, "duracao": 3.2}
```
Use o @ da identidade do cliente.

### `busca` — barra de busca digitando + resultados
```json
{"tipo": "busca", "texto": "como editar reels com IA", "em": {"arquivo": "f1", "t": 2.0}, "duracao": 3.5}
```
`tema`: `escuro` (padrão) ou `claro` (cara de Google).

### `marca` — logo grande de uma marca/app com brilho
```json
{"tipo": "marca", "logo": "Claude", "texto": "Claude", "em": {"arquivo": "f1", "t": 7.3}, "duracao": 2.4}
```

## Logos e ícones

- **`logo` = o nome da marca/app como se escreve**: `"CapCut"`, `"Adobe Premiere Pro"`, `"Photoshop"`, `"Canva"`, `"ChatGPT"`, `"Nubank"`, `"Havaianas"`. O sistema acha o logo oficial sozinho, nesta ordem: logo enviado pela agência → ~3.400 logos vetoriais no servidor (Simple Icons) → **ícone oficial na App Store** → Wikipédia → site da empresa. Você não precisa (nem consegue) baixar nada.
- Use o **nome do app como ele aparece na loja** quando for diferente da empresa (`"Magalu"`, não `"Magazine Luiza"`; `"Mercado Livre"`). Para empresa/loja que não tem app, mande também o domínio: `"logo": "Padaria Pão Quente", "site": "paoquente.com.br"`.
- Se nada for achado, o motor desenha um bloco com a sigla (nunca quebra o vídeo). Para forçar cores: `"sigla": "Ab", "fundo": "#123456", "cor": "#FFFFFF"` no lugar de `logo`.
- **Ícones genéricos** (`icone`, nos `cards`): Lucide, ~2.400 (scissors, laptop, camera, rocket, heart, star, map-pin, calendar, dollar-sign, trending-up, users, clock, message-circle, shopping-bag…). Confira o nome em `guias/icones.txt` (`lucide: scissors`). Nome que não existe = ícone vazio.
- Logos vetoriais já disponíveis estão em `guias/icones.txt` (`marca: whatsapp — WhatsApp`): para essas marcas o resultado é o mais nítido.

## Roteiro típico de um vídeo falado de 40 s (densidade de referência)

| tempo | fala | tela |
|---|---|---|
| 0,0 | (gancho) "edita esse vídeo" | `janela` digitando → `contador` 100% |
| 4,5 | "este vídeo aqui…" | `rec` + `pilula` "este vídeo" |
| 7,0 | "…sem CapCut, sem Premiere" | `apps` riscando cada um na sua palavra |
| 10,5 | frase forte | `palavra` ou punch de câmera |
| 12,0 | "é só: gravar, mandar, pedir" | `passos`, cada item na palavra |
| 18,0 | respiro | só o rosto + punch |
| 20,0 | "serve pra corte, antes e depois, bastidor" | `cards` acendendo um a um |
| 26,0 | resultado | `grafico` ou `comparacao` |
| 31,0 | prova | `notificacao` (se a pessoa contou) |
| 36,0 | "comenta EDIÇÃO" | `comentario` |
| 40,0 | fechamento | `seguir` ou cartão final |

Isso é a régua, não um molde: o conteúdo decide. Mas se o seu plano tiver só 3–4 elementos em 40 s de fala, está pobre — volte e encaixe mais.
