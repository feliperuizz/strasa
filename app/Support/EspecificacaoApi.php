<?php

namespace App\Support;

use App\Http\Resources\Api\MediaResource;
use App\Models\TaskPublication;

/**
 * Especificação OpenAPI 3.1 da API de postagem automática.
 *
 * Escrita à mão (e não gerada) para ter descrições e exemplos que a IA/equipe
 * do parceiro consiga seguir sem perguntar nada. O endereço do servidor é o
 * domínio pelo qual a documentação foi aberta (DocumentacaoApiController::raiz),
 * não o APP_URL.
 */
class EspecificacaoApi
{
    public static function openapi(string $raiz): array
    {
        $base = rtrim($raiz, '/').'/api/v1';
        $redes = array_keys(TaskPublication::REDES);
        $status = array_keys(TaskPublication::STATUS);
        $dias = MediaResource::VALIDADE_EM_DIAS;

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'STRASA — API de Postagem Automática',
                'version' => '1.0.0',
                'description' => implode("\n", [
                    'API para um sistema de postagem automática buscar no STRASA os posts prontos (legenda, data/hora e mídias) e informar de volta se a postagem foi realizada.',
                    '',
                    '**Autenticação:** envie a chave em todas as chamadas: `Authorization: Bearer <chave>`. A chave é criada pelo administrador da agência no STRASA (menu Integrações) e mostrada uma única vez.',
                    '',
                    '**Fluxo recomendado:** `GET /me` para validar → `GET /clients` para ligar cada cliente às contas de rede social → a cada 5 min `GET /posts?updated_since=...` → antes de publicar, `GET /posts/{id}` para confirmar e pegar links de mídia novos → publicar → `POST /posts/{id}/publications` com o resultado.',
                    '',
                    '**Fuso horário:** `scheduled_at` vem em ISO 8601 com o fuso de Brasília (America/Sao_Paulo, -03:00). Demais datas em UTC.',
                    '',
                    'Guia completo em texto: '.rtrim($raiz, '/').'/api/docs.md',
                ]),
                'contact' => ['name' => 'STRASA — agência', 'url' => rtrim($raiz, '/')],
            ],
            'servers' => [['url' => $base, 'description' => 'Produção']],
            'security' => [['chave' => []]],
            'tags' => [
                ['name' => 'Conta', 'description' => 'Conferir a chave e o que ela enxerga.'],
                ['name' => 'Clientes', 'description' => 'Marcas da agência. Ligue o `id` às contas de rede social do seu sistema.'],
                ['name' => 'Posts', 'description' => 'O que publicar: legenda, data/hora e mídias na ordem do carrossel.'],
                ['name' => 'Retorno', 'description' => 'Webhook de retorno: informe ao STRASA como ficou a postagem em cada rede.'],
                ['name' => 'Mídia', 'description' => 'Arquivos originais por link assinado (vêm prontos dentro de cada post).'],
            ],
            'paths' => [
                '/me' => ['get' => [
                    'tags' => ['Conta'],
                    'operationId' => 'getMe',
                    'summary' => 'Conferir a chave',
                    'description' => 'Use para testar a conexão. Mostra o nome da chave, os clientes que ela pode ver, o limite de requisições e a hora do servidor.',
                    'responses' => [
                        '200' => ['description' => 'Chave válida.', 'content' => ['application/json' => [
                            'schema' => ['type' => 'object', 'properties' => ['data' => ['$ref' => '#/components/schemas/Me']]],
                        ]]],
                        '401' => ['$ref' => '#/components/responses/NaoAutenticado'],
                        '403' => ['$ref' => '#/components/responses/Proibido'],
                        '429' => ['$ref' => '#/components/responses/LimiteExcedido'],
                    ],
                ]],
                '/clients' => ['get' => [
                    'tags' => ['Clientes'],
                    'operationId' => 'listClients',
                    'summary' => 'Listar clientes da chave',
                    'responses' => [
                        '200' => ['description' => 'Clientes que a chave pode ver.', 'content' => ['application/json' => [
                            'schema' => ['type' => 'object', 'properties' => ['data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Client']]]],
                        ]]],
                        '401' => ['$ref' => '#/components/responses/NaoAutenticado'],
                        '429' => ['$ref' => '#/components/responses/LimiteExcedido'],
                    ],
                ]],
                '/posts' => ['get' => [
                    'tags' => ['Posts'],
                    'operationId' => 'listPosts',
                    'summary' => 'Listar posts',
                    'description' => 'Por padrão (`status=ready`) só vem o que está pronto para ir ao ar: card na coluna de fila de postagem, com data e hora, ainda não publicado. Ordenado por data/hora de publicação.',
                    'parameters' => [
                        ['name' => 'status', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['ready', 'pending', 'published', 'all'], 'default' => 'ready'],
                            'description' => '`ready`: pronto para publicar (padrão, o único que deve ser publicado). `pending`: ainda não publicado, em qualquer coluna (só para pré-visualizar a agenda). `published`: já publicado. `all`: tudo.'],
                        ['name' => 'client_id', 'in' => 'query', 'schema' => ['type' => 'integer'], 'description' => 'Só os posts deste cliente.'],
                        ['name' => 'from', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date'], 'description' => 'Data de publicação a partir de (AAAA-MM-DD).'],
                        ['name' => 'to', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date'], 'description' => 'Data de publicação até (AAAA-MM-DD).'],
                        ['name' => 'updated_since', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date-time'],
                            'description' => 'Só posts alterados depois deste instante (ISO 8601). Guarde o `meta.server_time` de cada consulta e envie aqui na próxima.'],
                        ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50]],
                        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1, 'default' => 1]],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Página de posts.', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/PostList']]]],
                        '401' => ['$ref' => '#/components/responses/NaoAutenticado'],
                        '403' => ['$ref' => '#/components/responses/Proibido'],
                        '422' => ['$ref' => '#/components/responses/Invalido'],
                        '429' => ['$ref' => '#/components/responses/LimiteExcedido'],
                    ],
                ]],
                '/posts/{id}' => ['get' => [
                    'tags' => ['Posts'],
                    'operationId' => 'getPost',
                    'summary' => 'Ver um post',
                    'description' => 'Chame logo antes de publicar: confirma que o post continua pronto (`ready_to_publish`) e traz links de mídia recém-gerados. Se vier 404 ou `ready_to_publish: false`, não publique e informe `status: cancelled`.',
                    'parameters' => [['$ref' => '#/components/parameters/PostId']],
                    'responses' => [
                        '200' => ['description' => 'O post.', 'content' => ['application/json' => [
                            'schema' => ['type' => 'object', 'properties' => ['data' => ['$ref' => '#/components/schemas/Post']]],
                        ]]],
                        '401' => ['$ref' => '#/components/responses/NaoAutenticado'],
                        '404' => ['$ref' => '#/components/responses/NaoEncontrado'],
                        '429' => ['$ref' => '#/components/responses/LimiteExcedido'],
                    ],
                ]],
                '/posts/{id}/publications' => ['post' => [
                    'tags' => ['Retorno'],
                    'operationId' => 'reportPublication',
                    'summary' => 'Webhook de retorno: informar a postagem',
                    'description' => implode("\n", [
                        'Informe como ficou a postagem do post numa rede. Chame a cada mudança: `scheduled` ao agendar, `published` ao ir ao ar (com `permalink`), `failed` se der erro (com `error_message`), `cancelled` se desistir.',
                        '',
                        'Idempotente: o mesmo post + rede é atualizado, nunca duplicado — pode reenviar à vontade. O STRASA mostra a situação no card, no histórico e no painel da agência. Com `published`, o card é marcado como publicado automaticamente (se a agência deixou essa opção ligada na chave).',
                    ]),
                    'parameters' => [['$ref' => '#/components/parameters/PostId']],
                    'requestBody' => ['required' => true, 'content' => ['application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/PublicationInput'],
                        'examples' => [
                            'publicado' => ['summary' => 'Foi ao ar', 'value' => [
                                'network' => 'instagram', 'status' => 'published', 'external_id' => '17895695668004550',
                                'permalink' => 'https://www.instagram.com/p/C9xYzAbCdEf/', 'published_at' => '2026-10-01T18:00:04-03:00',
                            ]],
                            'agendado' => ['summary' => 'Agendado', 'value' => ['network' => 'facebook', 'status' => 'scheduled', 'scheduled_for' => '2026-10-01T18:00:00-03:00']],
                            'falhou' => ['summary' => 'Falhou', 'value' => ['network' => 'instagram', 'status' => 'failed', 'error_message' => 'A imagem 3 do carrossel tem proporção fora do permitido (4:5 a 1.91:1).']],
                        ],
                    ]]],
                    'responses' => [
                        '200' => ['description' => 'Registrado.', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/PublicationReceipt']]]],
                        '401' => ['$ref' => '#/components/responses/NaoAutenticado'],
                        '404' => ['$ref' => '#/components/responses/NaoEncontrado'],
                        '422' => ['$ref' => '#/components/responses/Invalido'],
                        '429' => ['$ref' => '#/components/responses/LimiteExcedido'],
                    ],
                ]],
                '/media/{id}' => ['get' => [
                    'tags' => ['Mídia'],
                    'operationId' => 'downloadMedia',
                    'summary' => 'Baixar mídia (link assinado)',
                    'description' => "Não monte esta URL: use exatamente o `media[].url` que vem no post. O link é público (não precisa de cabeçalho — pode ser repassado à API do Instagram/Facebook), expira em {$dias} dias e para de funcionar se a chave for revogada. Aceita `Range` (vídeos).",
                    'security' => [],
                    'parameters' => [
                        ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                        ['name' => 'expires', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'integer']],
                        ['name' => 'k', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'integer']],
                        ['name' => 'signature', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'string']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'O arquivo original (imagem ou vídeo).', 'content' => [
                            'image/*' => ['schema' => ['type' => 'string', 'format' => 'binary']],
                            'video/*' => ['schema' => ['type' => 'string', 'format' => 'binary']],
                        ]],
                        '206' => ['description' => 'Parte do arquivo (requisição com Range).'],
                        '401' => ['$ref' => '#/components/responses/NaoAutenticado'],
                        '403' => ['$ref' => '#/components/responses/Proibido'],
                        '404' => ['$ref' => '#/components/responses/NaoEncontrado'],
                    ],
                ]],
            ],
            'components' => [
                'securitySchemes' => [
                    'chave' => ['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 'str_...',
                        'description' => 'Chave criada no STRASA (Integrações). Formato: `str_` + 44 caracteres. Guarde só no servidor, nunca no navegador ou no app.'],
                ],
                'parameters' => [
                    'PostId' => ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer'], 'description' => 'ID do post no STRASA.'],
                ],
                'responses' => [
                    'NaoAutenticado' => ['description' => 'Chave ausente, inválida, revogada ou expirada. Códigos: `unauthenticated`, `invalid_token`, `token_revoked`, `token_expired`.',
                        'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Error'],
                            'example' => ['error' => ['code' => 'invalid_token', 'message' => 'Chave de API inválida.']]]]],
                    'Proibido' => ['description' => 'Sem permissão. Códigos: `ip_not_allowed`, `client_not_in_scope`, `invalid_or_expired_link`.',
                        'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Error'],
                            'example' => ['error' => ['code' => 'client_not_in_scope', 'message' => 'Esta chave não tem acesso ao cliente 7.']]]]],
                    'NaoEncontrado' => ['description' => 'Não existe, foi excluído ou não pertence a um cliente da chave. Códigos: `post_not_found`, `media_not_found`, `not_found`.',
                        'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Error'],
                            'example' => ['error' => ['code' => 'post_not_found', 'message' => 'Post não encontrado (não existe, foi excluído ou não pertence a um cliente desta chave).']]]]],
                    'Invalido' => ['description' => 'Parâmetros ou corpo inválidos. Código: `validation_failed`, com `details` por campo.',
                        'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Error'],
                            'example' => ['error' => ['code' => 'validation_failed', 'message' => 'Dados inválidos. Veja "details".', 'details' => ['status' => ['The selected status is invalid.']]]]]]],
                    'LimiteExcedido' => ['description' => 'Limite de requisições por minuto excedido. Código: `rate_limited`. Espere os segundos do cabeçalho `Retry-After`.',
                        'headers' => ['Retry-After' => ['schema' => ['type' => 'integer'], 'description' => 'Segundos até poder tentar de novo.']],
                        'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Error']]]],
                ],
                'schemas' => [
                    'Error' => ['type' => 'object', 'required' => ['error'], 'properties' => ['error' => ['type' => 'object', 'required' => ['code', 'message'], 'properties' => [
                        'code' => ['type' => 'string', 'description' => 'Código estável, para o seu sistema decidir o que fazer.'],
                        'message' => ['type' => 'string', 'description' => 'Explicação em português.'],
                        'details' => ['type' => 'object', 'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']], 'description' => 'Só em validation_failed: erros por campo.'],
                    ]]]],
                    'Client' => ['type' => 'object', 'properties' => [
                        'id' => ['type' => 'integer', 'example' => 5],
                        'name' => ['type' => 'string', 'example' => 'Construtora Horizonte'],
                        'slug' => ['type' => ['string', 'null'], 'example' => 'construtora-horizonte'],
                        'segment' => ['type' => ['string', 'null'], 'example' => 'Construção civil'],
                        'color' => ['type' => ['string', 'null'], 'example' => '#0f766e'],
                        'logo_url' => ['type' => ['string', 'null'], 'format' => 'uri'],
                        'networks' => ['type' => 'array', 'items' => ['type' => 'string'], 'example' => ['instagram', 'facebook'], 'description' => 'Redes informadas no cadastro do STRASA (pode vir vazio). A escolha de onde publicar é do seu sistema.'],
                    ]],
                    'Media' => ['type' => 'object', 'properties' => [
                        'id' => ['type' => 'integer', 'example' => 91],
                        'position' => ['type' => 'integer', 'example' => 1, 'description' => 'Ordem no carrossel (1 = primeira). Respeite esta ordem.'],
                        'type' => ['type' => 'string', 'enum' => ['image', 'video']],
                        'mime_type' => ['type' => 'string', 'example' => 'image/png', 'description' => 'O arquivo é o original enviado pela agência. O Instagram só aceita JPEG em imagens: converta PNG/WEBP antes.'],
                        'file_name' => ['type' => 'string', 'example' => 'slide-1.png'],
                        'size_bytes' => ['type' => 'integer', 'example' => 834221],
                        'url' => ['type' => 'string', 'format' => 'uri', 'description' => "Link assinado, público, válido por {$dias} dias. Não precisa de cabeçalho."],
                        'url_expires_at' => ['type' => 'string', 'format' => 'date-time'],
                    ]],
                    'Publication' => ['type' => 'object', 'properties' => [
                        'network' => ['type' => 'string', 'enum' => $redes],
                        'status' => ['type' => 'string', 'enum' => $status],
                        'external_id' => ['type' => ['string', 'null'], 'description' => 'ID do post na rede.'],
                        'permalink' => ['type' => ['string', 'null'], 'format' => 'uri', 'description' => 'Link público do post publicado.'],
                        'error_message' => ['type' => ['string', 'null']],
                        'scheduled_for' => ['type' => ['string', 'null'], 'format' => 'date-time'],
                        'published_at' => ['type' => ['string', 'null'], 'format' => 'date-time'],
                        'reported_at' => ['type' => ['string', 'null'], 'format' => 'date-time', 'description' => 'Quando o STRASA recebeu o último aviso.'],
                    ]],
                    'PublicationInput' => ['type' => 'object', 'required' => ['network', 'status'], 'properties' => [
                        'network' => ['type' => 'string', 'enum' => $redes, 'description' => 'Rede onde publicou.'],
                        'status' => ['type' => 'string', 'enum' => $status, 'description' => '`scheduled` agendado · `publishing` enviando · `published` foi ao ar · `failed` falhou · `cancelled` cancelado.'],
                        'external_id' => ['type' => 'string', 'maxLength' => 191],
                        'permalink' => ['type' => 'string', 'format' => 'uri', 'maxLength' => 2048, 'description' => 'Envie junto com `published`.'],
                        'error_message' => ['type' => 'string', 'maxLength' => 2000, 'description' => 'Envie junto com `failed`, em linguagem que a agência entenda.'],
                        'scheduled_for' => ['type' => 'string', 'format' => 'date-time', 'description' => 'Com fuso (ex.: 2026-10-01T18:00:00-03:00). Sem fuso = horário de Brasília.'],
                        'published_at' => ['type' => 'string', 'format' => 'date-time', 'description' => 'Quando foi ao ar. Se omitido com `published`, vale a hora do aviso.'],
                    ]],
                    'PublicationReceipt' => ['type' => 'object', 'properties' => [
                        'data' => ['$ref' => '#/components/schemas/Publication'],
                        'post' => ['type' => 'object', 'properties' => [
                            'id' => ['type' => 'integer'],
                            'is_published' => ['type' => 'boolean'],
                            'marked_published_now' => ['type' => 'boolean', 'description' => 'true se este aviso concluiu o card agora.'],
                            'column' => ['type' => ['string', 'null'], 'description' => 'Coluna do card no quadro depois do aviso.'],
                        ]],
                    ]],
                    'Post' => ['type' => 'object', 'properties' => [
                        'id' => ['type' => 'integer', 'example' => 123],
                        'title' => ['type' => 'string', 'example' => 'Carrossel: 7 motivos para investir em imóveis na planta', 'description' => 'Nome interno do card. Não é a legenda.'],
                        'caption' => ['type' => ['string', 'null'], 'description' => 'Legenda para publicar, exatamente como aprovada: texto puro, com emojis e quebras de linha (\\n). Não altere.',
                            'example' => "🏡 Morar bem começa pela escolha certa.\n\nAgende sua visita pelo link da bio.\n\n#ImovelNaPlanta"],
                        'content_type' => ['type' => ['string', 'null'], 'enum' => ['feed', 'carousel', 'story', 'reel', 'blog', 'video', null]],
                        'content_type_label' => ['type' => ['string', 'null'], 'example' => 'Carrossel'],
                        'scheduled_at' => ['type' => ['string', 'null'], 'format' => 'date-time', 'example' => '2026-10-01T18:00:00-03:00', 'description' => 'Quando publicar (fuso de Brasília). null se faltar data ou hora.'],
                        'publish_date' => ['type' => ['string', 'null'], 'format' => 'date', 'example' => '2026-10-01'],
                        'publish_time' => ['type' => ['string', 'null'], 'example' => '18:00'],
                        'timezone' => ['type' => 'string', 'example' => 'America/Sao_Paulo'],
                        'ready_to_publish' => ['type' => 'boolean', 'description' => 'Só publique se true.'],
                        'not_ready_reason' => ['type' => ['string', 'null'], 'enum' => ['already_published', 'not_in_publish_queue', 'missing_publish_date', 'missing_publish_time', null],
                            'description' => 'Por que não está pronto: já publicado · fora da coluna de fila de postagem · sem data · sem hora.'],
                        'is_published' => ['type' => 'boolean'],
                        'published_at' => ['type' => ['string', 'null'], 'format' => 'date-time'],
                        'client' => ['$ref' => '#/components/schemas/Client'],
                        'project' => ['type' => ['object', 'null'], 'properties' => ['id' => ['type' => 'integer'], 'name' => ['type' => 'string']]],
                        'column' => ['type' => ['object', 'null'], 'properties' => [
                            'id' => ['type' => 'integer'], 'name' => ['type' => 'string'],
                            'is_publish_queue' => ['type' => 'boolean', 'description' => 'true = coluna de fila de postagem.'],
                        ]],
                        'tags' => ['type' => 'array', 'items' => ['type' => 'string'], 'example' => ['Programado']],
                        'approval' => ['type' => ['object', 'null'], 'description' => 'Última rodada no painel de aprovação do cliente (informativo).', 'properties' => [
                            'status' => ['type' => 'string', 'enum' => ['pending', 'approved', 'rejected']],
                            'round' => ['type' => 'integer'],
                            'reviewer_name' => ['type' => ['string', 'null']],
                            'responded_at' => ['type' => ['string', 'null'], 'format' => 'date-time'],
                        ]],
                        'media' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Media'], 'description' => 'Imagens e vídeos na ordem do carrossel.'],
                        'publications' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Publication'], 'description' => 'O que a SUA chave já informou deste post.'],
                        'strasa_url' => ['type' => 'string', 'format' => 'uri', 'description' => 'Link do card no STRASA (exige login da agência).'],
                        'created_at' => ['type' => 'string', 'format' => 'date-time'],
                        'updated_at' => ['type' => 'string', 'format' => 'date-time', 'description' => 'Muda quando legenda, data, coluna ou mídias mudam.'],
                    ]],
                    'PostList' => ['type' => 'object', 'properties' => [
                        'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Post']],
                        'meta' => ['type' => 'object', 'properties' => [
                            'current_page' => ['type' => 'integer'], 'per_page' => ['type' => 'integer'],
                            'total' => ['type' => 'integer'], 'last_page' => ['type' => 'integer'],
                            'status_filter' => ['type' => 'string'],
                            'server_time' => ['type' => 'string', 'format' => 'date-time', 'description' => 'Guarde e use como `updated_since` na próxima consulta.'],
                        ]],
                        'links' => ['type' => 'object', 'properties' => [
                            'next' => ['type' => ['string', 'null'], 'format' => 'uri'],
                            'prev' => ['type' => ['string', 'null'], 'format' => 'uri'],
                        ]],
                    ]],
                    'Me' => ['type' => 'object', 'properties' => [
                        'token_name' => ['type' => 'string', 'example' => 'Postador Automático'],
                        'token_prefix' => ['type' => 'string', 'example' => 'str_Ab12Cd34'],
                        'company' => ['type' => ['string', 'null']],
                        'scope' => ['type' => 'string', 'enum' => ['all_clients', 'selected_clients']],
                        'clients' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Client']],
                        'auto_complete_on_published' => ['type' => 'boolean'],
                        'expires_at' => ['type' => ['string', 'null'], 'format' => 'date-time'],
                        'rate_limit_per_minute' => ['type' => 'integer', 'example' => 120],
                        'media_url_ttl_days' => ['type' => 'integer', 'example' => $dias],
                        'timezone' => ['type' => 'string', 'example' => 'America/Sao_Paulo'],
                        'server_time' => ['type' => 'string', 'format' => 'date-time'],
                    ]],
                ],
            ],
        ];
    }
}
