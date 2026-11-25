# Estilo: Tour do espaço

Clipes do ambiente do cliente (sem fala) viram um vídeo curto, ritmado na batida da música, que dá vontade de ir até lá.

## Estrutura (15–30 s)
1. **Gancho (0–2 s):** o plano mais bonito ou mais "uau" do material — não a fachada parada. Movimento forte (zoom, câmera lenta, detalhe). Um `titulo` curto já no 1º segundo ("Conheça a Barbearia do Zé", "O lugar mais estiloso da Vila Nova").
2. **Ambiente (3–4 planos):** visão geral → cantos com personalidade. Planos curtos (1–2 s), na batida.
3. **Detalhes (3–5 planos):** texturas, ferramentas, produtos, decoração. Câmera lenta 0,6–0,8 fica ótima aqui.
4. **Ação:** alguém da equipe trabalhando, o resultado (o corte pronto, o prato servido).
5. **Fechamento:** `cartao_final` com chamada + endereço/Instagram. Um `local` com o endereço antes do cartão também funciona.

## Cortes
- Escolha pelos quadros: nítido, bem iluminado, com movimento interessante. Descarte tremido, escuro, desfocado, gente mexendo no celular.
- De cada clipe, pegue o melhor 1–2,5 s (raramente mais de 3 s). Um clipe forte pode aparecer de novo noutro momento, com outro trecho.
- Não use o som original (`audio: false`): a música é a trilha.
- `musica.sincronizar_cortes: true` — o sistema ajusta cada corte para a batida. Planeje durações perto de múltiplos do intervalo entre batidas (no contexto: BPM e batidas).
- Na virada da música (drop, entrada da bateria), um `flash` ou transição `zoom`.

## Câmera
- Todo plano anda: aproximação (`[1.0, 1.12]`) ou afastamento (`[1.15, 1.0]`), alternando. Material horizontal: escolha o `foco` para o assunto ficar na faixa vertical.
- Câmera lenta em detalhes e movimentos de pessoas (velocidade 0,6–0,8).

## Animações
- Poucas e fortes: título de abertura, 1–3 `etiqueta`/`texto` curtos com o que o lugar tem de especial ("Ambiente climatizado", "Cerveja gelada") — só o que o briefing ou a imagem confirmam —, `local` com o endereço, cartão final.
- Stickers `brilho`/`estrela` pontuais num detalhe bonito.

## Música
- Volume 0,8–1,0. Comece num trecho com energia (`musica.inicio`), não na introdução lenta.
