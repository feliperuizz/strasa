<?php

namespace App\Support;

use App\Http\Resources\Api\MediaResource;
use App\Models\TaskPublication;

/**
 * Guia da API de postagem em Markdown (servido em /api/docs.md).
 *
 * Feito para quem vai implementar do outro lado — inclusive uma IA: fluxo
 * completo, exemplos reais de requisição e resposta, erros e armadilhas.
 * Fica em PHP (e não Blade) porque o Blade compila "@palavra" e "{{ }}"
 * em qualquer lugar do texto, inclusive dentro dos exemplos de código.
 */
class GuiaApi
{
    public static function markdown(string $raiz): string
    {
        $raiz = rtrim($raiz, '/');
        $base = $raiz.'/api/v1';
        $dias = MediaResource::VALIDADE_EM_DIAS;
        $limite = (int) config('services.api_postagem.limite_por_minuto', 120);
        $redes = implode(', ', array_map(fn ($r) => '`'.$r.'`', array_keys(TaskPublication::REDES)));

        $texto = <<<'MD'
# STRASA — API de Postagem Automática (v1)

Guia de integração para o sistema que publica posts nas redes sociais a partir do STRASA.

- **URL base:** `{{BASE}}`
- **Swagger (interativo):** {{RAIZ}}/api/docs
- **OpenAPI 3.1 (JSON):** {{RAIZ}}/api/openapi.json
- **Este guia:** {{RAIZ}}/api/docs.md
- **Formato:** JSON (UTF-8) · HTTPS obrigatório · fuso de publicação: `America/Sao_Paulo`

> Não é preciso IP: use sempre o domínio acima (o certificado HTTPS é do domínio e o IP do servidor pode mudar).

---

## 1. O que a API faz

O STRASA é o sistema de gestão da agência. Cada **post** é um card no quadro do cliente. Quando a agência arrasta o card para a coluna de **fila de postagem**, com data e hora definidas, o post fica **pronto** e aparece para você nesta API.

Você:

1. **busca** os posts prontos: legenda, data/hora e mídias (na ordem do carrossel);
2. **publica** nas redes, no horário indicado;
3. **informa de volta** (webhook de retorno) se publicou ou falhou, em cada rede.

O STRASA mostra esse retorno no card, no histórico e no painel da agência. Quando você informa `published`, o card é marcado como publicado automaticamente.

**Regra de ouro:** só publique posts com `ready_to_publish: true`, e confirme com `GET /posts/{id}` logo antes de publicar.

---

## 2. Autenticação e segurança

Todas as chamadas (menos os links de mídia) exigem a chave no cabeçalho:

```
Authorization: Bearer str_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

- A chave é criada pela agência no STRASA (menu **Integrações**) e mostrada **uma única vez**. Guarde num cofre de segredos / variável de ambiente, **só no servidor**. Nunca no navegador, em app, em log ou em repositório.
- A chave pode ser limitada a alguns clientes, a alguns IPs e ter validade. Se vazar, a agência revoga e gera outra — a antiga para de funcionar na hora.
- Use sempre HTTPS.
- Limite: **{{LIMITE}} requisições por minuto** por chave. Passou disso, resposta `429` com o cabeçalho `Retry-After` (segundos).

Teste a chave:

```bash
curl -s {{BASE}}/me -H "Authorization: Bearer $STRASA_TOKEN"
```

---

## 3. Conceitos

### Cliente
A marca da agência (ex.: "Construtora Horizonte"). Cada post pertence a um cliente. Use o `client.id` para ligar ao **seu** cadastro de contas de rede social (Instagram, Facebook...). O campo `networks` traz as redes informadas no STRASA, mas pode vir vazio — a escolha de onde publicar é do seu sistema, configurada com a agência.

### Post pronto (`ready_to_publish`)
Um post está pronto quando **todas** estas condições valem:

| Condição | Se faltar, `not_ready_reason` = |
|---|---|
| ainda não foi publicado | `already_published` |
| está na coluna de fila de postagem | `not_in_publish_queue` |
| tem data de publicação | `missing_publish_date` |
| tem horário de publicação | `missing_publish_time` |

Se um post sair da fila (a agência arrastou de volta para ajustes, por exemplo), ele deixa de estar pronto: **não publique**.

### Data e hora
- `scheduled_at`: quando publicar, ISO 8601 com o fuso de Brasília. Ex.: `2026-10-01T18:00:00-03:00`.
- `publish_date` (`AAAA-MM-DD`) e `publish_time` (`HH:MM`) vêm separados também, no fuso `America/Sao_Paulo`.
- As demais datas (`created_at`, `updated_at`...) vêm em UTC.

### Legenda
`caption` é o texto exato para publicar: texto puro, com emojis, hashtags e quebras de linha (`\n`). Não reescreva, não corte, não adicione nada. `title` é só o nome interno do card — **não** é a legenda.

### Mídias
`media` traz imagens e vídeos **na ordem do carrossel** (`position` 1, 2, 3…). Respeite a ordem.

- `url` é um **link assinado**: público (não precisa de cabeçalho), válido por **{{DIAS}} dias**, e deixa de funcionar se a chave for revogada. Pode ser repassado direto à API da Meta, que baixa o arquivo sozinha.
- Links expiram: sempre pegue o post de novo (`GET /posts/{id}`) antes de publicar, para ter links novos.
- É o **arquivo original** da agência (`mime_type` diz o formato). Atenção: o Instagram só aceita **JPEG** em imagens — converta PNG/WEBP antes de enviar. Vídeos costumam vir em MP4.
- Não monte URLs de mídia você mesmo: use exatamente o `url` recebido.

---

## 4. Endpoints

| Método | Rota | Para quê |
|---|---|---|
| GET | `/me` | Conferir a chave e o escopo |
| GET | `/clients` | Clientes que a chave enxerga |
| GET | `/posts` | Listar posts (padrão: só os prontos) |
| GET | `/posts/{id}` | Ver um post (use antes de publicar) |
| POST | `/posts/{id}/publications` | **Webhook de retorno**: informar a postagem |
| GET | `/media/{id}?...` | Baixar a mídia (link assinado, já vem no post) |

### GET /posts

Parâmetros (todos opcionais):

| Parâmetro | Exemplo | Descrição |
|---|---|---|
| `status` | `ready` | `ready` (padrão — o único que deve ser publicado), `pending` (não publicados em qualquer coluna, só para pré-visualizar a agenda), `published`, `all` |
| `client_id` | `5` | Só deste cliente |
| `from` / `to` | `2026-10-01` | Faixa de data de publicação |
| `updated_since` | `2026-10-01T12:00:00-03:00` | Só o que mudou depois deste instante |
| `per_page` | `50` | 1 a 100 (padrão 50) |
| `page` | `2` | Página |

Resposta (resumida):

```json
{
  "data": [
    {
      "id": 123,
      "title": "Carrossel: 7 motivos para investir em imóveis na planta",
      "caption": "🏡 Morar bem começa pela escolha certa.\n\nAgende sua visita pelo link da bio.\n\n#ImovelNaPlanta",
      "content_type": "carousel",
      "content_type_label": "Carrossel",
      "scheduled_at": "2026-10-01T18:00:00-03:00",
      "publish_date": "2026-10-01",
      "publish_time": "18:00",
      "timezone": "America/Sao_Paulo",
      "ready_to_publish": true,
      "not_ready_reason": null,
      "is_published": false,
      "published_at": null,
      "client": { "id": 5, "name": "Construtora Horizonte", "slug": "construtora-horizonte", "networks": ["instagram", "facebook"] },
      "project": { "id": 4, "name": "Lançamento Jardins 2026" },
      "column": { "id": 12, "name": "Programado para publicação", "is_publish_queue": true },
      "tags": ["Programado"],
      "approval": { "status": "approved", "round": 1, "reviewer_name": "Ricardo", "responded_at": "2026-09-29T20:10:00+00:00" },
      "media": [
        { "id": 91, "position": 1, "type": "image", "mime_type": "image/jpeg", "file_name": "slide-1.jpg", "size_bytes": 834221,
          "url": "{{BASE}}/media/91?expires=1759700000&k=3&signature=...", "url_expires_at": "2026-10-08T15:00:00+00:00" },
        { "id": 92, "position": 2, "type": "image", "mime_type": "image/jpeg", "file_name": "slide-2.jpg", "size_bytes": 790113, "url": "...", "url_expires_at": "..." }
      ],
      "publications": [],
      "strasa_url": "{{RAIZ}}/tasks/123",
      "created_at": "2026-09-25T13:02:11+00:00",
      "updated_at": "2026-09-30T18:40:02+00:00"
    }
  ],
  "meta": { "current_page": 1, "per_page": 50, "total": 1, "last_page": 1, "status_filter": "ready", "server_time": "2026-09-30T18:45:00+00:00" },
  "links": { "next": null, "prev": null }
}
```

### POST /posts/{id}/publications — webhook de retorno

Informe o que aconteceu em **cada rede**. Chame a cada mudança; reenviar é seguro (o mesmo post + rede é atualizado, nunca duplicado).

Campos:

| Campo | Obrigatório | Descrição |
|---|---|---|
| `network` | sim | {{REDES}} |
| `status` | sim | `scheduled` (agendou), `publishing` (enviando), `published` (foi ao ar), `failed` (falhou), `cancelled` (desistiu) |
| `permalink` | com `published` | Link público do post |
| `external_id` | não | ID do post na rede |
| `error_message` | com `failed` | O motivo, em linguagem que a agência entenda |
| `scheduled_for` | não | Quando vai ao ar (ISO 8601 com fuso; sem fuso = Brasília) |
| `published_at` | não | Quando foi ao ar (se omitido com `published`, vale a hora do aviso) |

Publicou:

```bash
curl -s -X POST {{BASE}}/posts/123/publications \
  -H "Authorization: Bearer $STRASA_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"network":"instagram","status":"published","external_id":"17895695668004550","permalink":"https://www.instagram.com/p/C9xYzAbCdEf/","published_at":"2026-10-01T18:00:04-03:00"}'
```

Falhou:

```json
{ "network": "instagram", "status": "failed", "error_message": "A imagem 3 do carrossel tem proporção fora do permitido (4:5 a 1.91:1)." }
```

Resposta:

```json
{
  "data": { "network": "instagram", "status": "published", "external_id": "17895695668004550", "permalink": "https://www.instagram.com/p/C9xYzAbCdEf/", "error_message": null, "scheduled_for": null, "published_at": "2026-10-01T21:00:04+00:00", "reported_at": "2026-10-01T21:00:06+00:00" },
  "post": { "id": 123, "is_published": true, "marked_published_now": true, "column": "Postado" }
}
```

---

## 5. Fluxo recomendado (passo a passo)

1. **Validar a chave:** `GET /me`. Guarde `timezone` e `rate_limit_per_minute`.
2. **Mapear clientes:** `GET /clients`. Ligue cada `client.id` às contas de rede social no seu sistema. Clientes sem conta ligada: não publique; registre no seu painel.
3. **Sincronizar a agenda** a cada 5 minutos:
   - primeira vez: `GET /posts` (sem `updated_since`), percorrendo todas as páginas;
   - depois: `GET /posts?updated_since=<meta.server_time da consulta anterior>`;
   - para cada post recebido, crie ou atualize o agendamento no seu sistema (legenda, horário e mídias podem ter mudado).
4. **Informar o agendamento:** ao agendar, `POST /posts/{id}/publications` com `status: "scheduled"` e `scheduled_for`, uma chamada por rede.
5. **Na hora de publicar** (alguns minutos antes de `scheduled_at`):
   - `GET /posts/{id}`;
   - se vier **404** ou `ready_to_publish: false`: **não publique** e informe `status: "cancelled"`;
   - se `scheduled_at` mudou: reagende;
   - baixe as mídias pelos `url` novos, na ordem de `position` (converta imagens para JPEG se a rede exigir) e publique com a `caption` exatamente como veio.
6. **Informar o resultado:** `published` (com `permalink` e `external_id`) ou `failed` (com `error_message`), uma chamada por rede.
7. **Se o retorno falhar** (rede, 5xx, 429): tente de novo com espera crescente (ex.: 10 s, 1 min, 5 min, 30 min). É seguro repetir.

Pseudocódigo:

```python
ultimo = None
while True:
    params = {"updated_since": ultimo} if ultimo else {}
    pagina = 1
    while True:
        resp = get("/posts", {**params, "page": pagina})
        for post in resp["data"]:
            if post["ready_to_publish"]:
                agendar_ou_atualizar(post)          # e informar status "scheduled"
            else:
                cancelar_agendamento_se_existir(post["id"])
        if not resp["links"]["next"]:
            break
        pagina += 1
    ultimo = resp["meta"]["server_time"]
    esperar(5 * 60)

def publicar(post_id):
    post = get(f"/posts/{post_id}")               # 404 => cancelar
    if not post["data"]["ready_to_publish"]:
        return informar(post_id, rede, "cancelled")
    for rede in redes_do_cliente(post["data"]["client"]["id"]):
        try:
            resultado = publicar_na_rede(rede, post["data"]["caption"], post["data"]["media"])
            informar(post_id, rede, "published", permalink=resultado.url, external_id=resultado.id)
        except Exception as erro:
            informar(post_id, rede, "failed", error_message=str(erro))
```

---

## 6. Erros

Todo erro vem neste formato:

```json
{ "error": { "code": "post_not_found", "message": "Post não encontrado (...)" } }
```

| HTTP | `code` | O que fazer |
|---|---|---|
| 401 | `unauthenticated` | Faltou o cabeçalho `Authorization: Bearer ...` |
| 401 | `invalid_token` | Chave errada |
| 401 | `token_revoked` / `token_expired` | Pedir chave nova à agência |
| 403 | `ip_not_allowed` | Chame de um IP autorizado ou peça à agência para liberar |
| 403 | `client_not_in_scope` | A chave não enxerga esse cliente |
| 403 | `invalid_or_expired_link` | Link de mídia expirado/alterado: busque o post de novo |
| 404 | `post_not_found` / `media_not_found` / `not_found` | Não existe, foi excluído ou saiu do escopo: não publique |
| 405 | `method_not_allowed` | Método HTTP errado para a rota |
| 422 | `validation_failed` | Corrija os campos listados em `details` |
| 429 | `rate_limited` | Espere `Retry-After` segundos |
| 500 | `server_error` | Tente de novo mais tarde (com espera crescente) |

---

## 7. Checklist de implementação

- [ ] Chave guardada só no servidor (variável de ambiente / cofre).
- [ ] `GET /me` respondendo 200.
- [ ] Clientes mapeados para as contas de rede social.
- [ ] Sincronização a cada 5 min com `updated_since` e paginação.
- [ ] Só publica com `ready_to_publish: true`, confirmado por `GET /posts/{id}` na hora.
- [ ] Legenda publicada exatamente como veio (`\n` = quebra de linha).
- [ ] Mídias na ordem de `position`, com links novos, imagens em JPEG quando a rede exigir.
- [ ] Retorno `scheduled` / `published` / `failed` / `cancelled` por rede, com nova tentativa em caso de falha.
- [ ] Trata 401/403/404/422/429/500 conforme a tabela acima.

---

## 8. Versão

`v1` — a URL base inclui a versão. Mudanças que quebrem compatibilidade virão numa `v2`; campos novos podem ser adicionados na `v1` a qualquer momento (ignore campos que não conhecer).
MD;

        return strtr($texto, [
            '{{BASE}}' => $base,
            '{{RAIZ}}' => $raiz,
            '{{DIAS}}' => (string) $dias,
            '{{LIMITE}}' => (string) $limite,
            '{{REDES}}' => $redes,
        ]);
    }
}
