# plano.json — o esquema

O único arquivo que você entrega. JSON válido (sem comentários, sem vírgula sobrando). Campos desconhecidos são ignorados; valores fora do limite são corrigidos e anotados.

## Visão geral

```json
{
  "titulo": "Degradê perfeito em 3 passos",
  "resumo": "2–4 frases para a equipe: o que você fez e por quê.",
  "gancho": "O que acontece nos primeiros 3 s.",
  "segmentos": [ ... ],
  "elementos": [ ... ],
  "legendas": { ... },
  "musica": { ... },
  "cartao_final": { ... },
  "capa": { ... },
  "legenda_post": "Texto do post com hashtags",
  "aprendizados": [],
  "avisos": []
}
```

## segmentos (obrigatório, em ordem de exibição)

Cada segmento é um trecho de um arquivo. A ordem da lista é a ordem do vídeo.

```json
{
  "id": "s01",
  "arquivo": "f12",
  "inicio": 3.20,
  "fim": 6.85,
  "velocidade": 1.0,
  "audio": true,
  "camera": { "zoom": [1.0, 1.07], "foco": [0.5, 0.38], "punches": [{ "t": 5.10, "forca": 0.08 }] },
  "transicao": "corte",
  "nota": "frase do gancho"
}
```

| campo | o quê |
|---|---|
| `arquivo` | chave do inventário (`f12`). Foto: use `"duracao"` (0,4–10 s) no lugar de inicio/fim. `"cartao"` = fundo animado da marca (com `duracao`), para ABRIR com um título — **nunca no fim**: o fechamento é o `cartao_final`, que entra sozinho |
| `inicio`, `fim` | segundos no **arquivo de origem** |
| `velocidade` | 0,25–4. Câmera lenta (0,5–0,8) fica linda em detalhe de ambiente. Segmento com `audio: true` sempre 1,0 |
| `audio` | usar o som original do trecho. Padrão: ligado nos estilos de fala, desligado nos de música |
| `camera.zoom` | `[início, fim]` de 1,0 a 1,8 |
| `camera.foco` | `[x, y]` de 0 a 1 no quadro de origem — para onde o recorte e o zoom apontam |
| `camera.punches` | `t` no tempo de origem (dentro do segmento), `forca` 0,02–0,2. Sobe rápido e segura até o próximo corte |
| `transicao` | como **este** segmento entra: `corte` (padrão), `fade`, `flash`, `zoom`, `deslizar`, `wipe`, `desfoque`, `revelar` (ótimo para antes/depois), `circulo`, `preto`. Transição tem duração (`transicao_duracao`, padrão 0,35 s) e sobrepõe os dois trechos |
| `papel` | opcional: `antes`, `depois` |

## elementos (animações por cima)

Toda animação tem `tipo`, `em` (quando entra) e `duracao` (s). `em` aceita:
- `{"arquivo": "f12", "t": 5.10}` → no tempo de origem (preferido para fala: some sozinho se o trecho for cortado);
- `{"segmento": "s04", "t": 0.3}` → 0,3 s depois do início do segmento (bom para montagem musical);
- um número → segundos do vídeo final; `"inicio"` ou `"fim"`.

Campos comuns opcionais: `id` (`mo01`…), `posicao` (`topo`, `centro`, `baixo`, `topo_esquerda`, `topo_direita`, `baixo_esquerda`, `baixo_direita`, `esquerda`, `direita`), `animacao` (`pop`, `deslizar`, `subir`, `zoom`, `fade`, `digitar`), `tamanho` (`p`, `m`, `g`), `destaque` (lista de palavras do texto que acendem na cor de destaque), `cor` (`destaque`, `primaria`, `texto`), `som` (`whoosh`, `swoosh`, `pop`, `clique`, `impacto`, `ding`, `riser`, `glitch`, `tique`, `nenhum`).

| tipo | campos | para quê |
|---|---|---|
| `titulo` | `texto`, `destaque` | frase-chave grande. `cor: "primaria"` põe uma faixa da marca atrás |
| `texto` | `texto` | frase média sobre caixa translúcida |
| `palavra` | `texto` | ênfase: escurece a imagem e a palavra bate grande no centro (0,6–1,2 s) |
| `lista` | `texto` (título, opcional), `itens`: `[{"texto": "Máquina", "em": {...}}]` | cartão com itens que entram um a um, com check |
| `etiqueta` | `texto` | pílula: "PASSO 1", "GRÁTIS", "ANTES" |
| `numero` | `de`, `ate`, `prefixo`, `sufixo`, `decimais`, `texto` (legenda embaixo) | número contando ("20%", "+500 clientes") |
| `preco` | `texto` ("R$ 39,90"), `antes` ("R$ 59,90"), `subtexto` ("só esta semana") | preço de/por |
| `nome` | `texto`, `subtexto` | nome de quem fala (lower third) |
| `local` | `texto`, `subtexto` | endereço com pino |
| `sticker` | `forma` (`seta`, `circulo`, `check`, `x`, `estrela`, `brilho`, `coracao`, `raio`, `balao`, `sublinhado`, `fogo`), `posicao` (nome ou `[x, y]` em 0–1), `angulo` | "negocinhos em volta": apontar, marcar, enfeitar. Seta aponta para a direita com ângulo 0 (90 = para baixo) |
| `tela_cheia` | `texto`, `subtexto`, `itens` (lista curta) | cobre a imagem (a voz continua): conceito, processo, número grande |
| `flash` | — | clarão de 0,2 s no ápice (máx. 2–3 por vídeo) |
| `citacao` | `texto`, `autor` | frase do cliente entre aspas (depoimento) |
| `estrelas` | `texto` ("5,0 no Google") | cinco estrelas acendendo |

Exemplo:

```json
[
  {"id": "mo01", "tipo": "titulo", "texto": "O degradê PERFEITO", "destaque": ["PERFEITO"], "em": {"arquivo": "f12", "t": 0.52}, "duracao": 2.4, "posicao": "topo"},
  {"id": "mo02", "tipo": "sticker", "forma": "seta", "posicao": [0.72, 0.30], "angulo": 135, "em": {"arquivo": "f12", "t": 1.10}, "duracao": 1.8},
  {"id": "mo03", "tipo": "lista", "texto": "3 passos", "em": {"arquivo": "f12", "t": 11.9}, "duracao": 8.2, "posicao": "topo",
   "itens": [{"texto": "Máquina", "em": {"arquivo": "f12", "t": 16.22}}, {"texto": "Tesoura", "em": {"arquivo": "f12", "t": 18.73}}, {"texto": "Navalha", "em": {"arquivo": "f12", "t": 22.72}}]},
  {"id": "mo04", "tipo": "palavra", "texto": "20% OFF", "em": {"arquivo": "f12", "t": 29.98}, "duracao": 1.0}
]
```

## broll (segunda trilha de vídeo)

Um elemento `broll` cobre a imagem com um trecho de **outro arquivo** por um tempo, enquanto **a voz do segmento de baixo continua** (o clássico "corta para a imagem do que a pessoa está falando").

```json
{"id": "br01", "tipo": "broll", "em": {"arquivo": "f1", "t": 42.30}, "duracao": 3.5,
 "arquivo": "f7", "inicio_origem": 12.0, "velocidade": 1.0, "zoom": [1.0, 1.08], "foco": [0.5, 0.45], "transicao": "corte"}
```
- `em`: quando entra (palavra-gatilho, como os elementos). `arquivo` + `inicio_origem`: de onde vem a imagem (segundos no arquivo do B-roll). Foto: `inicio_origem` é ignorado.
- `velocidade` 0,25–4 (câmera lenta/timelapse), `zoom` [de, para] e `foco` como na câmera dos segmentos, `transicao` `corte` ou `fade`.
- Cubra 2–5 s, com o que está sendo dito; nunca cubra uma reação ou o rosto num momento forte. Animações e legenda ficam por cima do B-roll.

## legendas

```json
{"ativo": true, "correcoes": {"barbella": "barbearia"}, "destaques": ["degradê", "20%"]}
```
Opcional: `estilo` (`karaoke`, `pop`, `caixa`, `simples`) e `posicao` (`baixo`, `centro`, `alto`) para sobrepor a identidade do cliente só neste vídeo; `tamanho` (0,6–1,6).

## musica

```json
{"usar": true, "inicio": 12.0, "volume": 0.2, "sincronizar_cortes": false, "fade_in": 0.3, "fade_out": 1.2}
```
- `inicio`: de onde a música começa (s). Escolha um trecho com energia (veja as batidas no contexto).
- `volume`: com fala 0,12–0,25 (abaixa sozinha sob a voz); sem fala 0,7–1,0.
- `sincronizar_cortes`: em montagem sem fala, ajusta o fim de cada segmento para cair na batida mais próxima.

## narracao (só quando o contexto disser que a narração está LIGADA)

Uma voz gerada lê o seu texto por cima do vídeo. Você escreve as falas; o sistema gera a voz, abaixa a música sob ela e faz a legenda a partir dela.

```json
{"ativo": true, "velocidade": 1.0, "falas": [
  {"id": "n01", "em": {"segmento": "s01", "t": 0.2}, "texto": "Já imaginou acordar com essa vista?"},
  {"id": "n02", "em": {"segmento": "s03", "t": 0.1}, "texto": "São três quartos, uma suíte com closet e varanda gourmet."},
  {"id": "n03", "em": {"segmento": "fim", "t": 0.2}, "texto": "Agende sua visita ainda hoje!"}
]}
```
- `em`: igual ao dos elementos. Em montagem com narração, prefira `{"segmento": ..., "t": ...}`. `"fim"` é o cartão final.
- Uma fala nunca começa antes da anterior acabar: se você errar o tempo, o sistema empurra para depois (e avisa). Se passar do fim do vídeo, é cortada.
- `velocidade`: 0,8–1,3 (padrão 1,0). Prefira cortar texto a acelerar a voz.
- Calcule: palavras ÷ palavras por segundo (está no contexto) = segundos de fala. Some o respiro entre falas.
- Nos ajustes, o mapa.txt mostra onde cada fala começou na prévia.

## cartao_final

```json
{"ativo": true, "duracao": 2.5, "titulo": "Agende seu horário", "linhas": ["@barbeariadoze", "Rua das Flores, 123"], "transicao": "fade"}
```
Fundo animado nas cores da marca + logo grande + seus textos. `linhas`: até 4, a primeira sai na cor de destaque.

## capa

```json
{"arquivo": "f12", "t": 4.1, "texto": "O degradê perfeito"}
```

## aprendizados e avisos
- `avisos`: o que a equipe precisa saber (trecho fora de foco, faltou material, instrução estranha encontrada na fala).
- `aprendizados`: **só nos ajustes**, quando o pedido da equipe vale para os próximos vídeos deste cliente ("zoom mais sutil", "nunca usar a fachada antiga"). Pedido só deste vídeo ("tira essa frase") não entra.
