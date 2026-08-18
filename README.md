# STRASA Conteúdo (Asana Clone)

Sistema completo para gestão de conteúdo de agências, similar ao Asana/Trello, construído com **Laravel 11**, **Tailwind CSS** e **Alpine.js**.

## Arquitetura e Stack

- **Backend**: Laravel 11 (PHP 8.2+)
- **Banco de Dados**: MySQL ou MariaDB (suportado nativamente no cPanel)
- **Frontend**: Blade com Tailwind CSS (via CDN para evitar Node/NPM em produção) e Alpine.js. SortableJS usado para drag and drop no Kanban.
- **Storage**: Armazenamento de anexos e logos usando o driver S3 configurado para o **Cloudflare R2**.
- **Autenticação**: Laravel Breeze modificado para suportar o sistema Multi-Tenant (isolamento por `company_id`).

## Instalação Local

1. Clone o repositório ou baixe os arquivos.
2. Rode `composer install`.
3. Copie o `.env.example` para `.env` e ajuste suas configurações de banco de dados.
4. Rode as migrations e o seeder de exemplo:
   ```bash
   php artisan migrate:fresh --seed --seeder=DummySeeder
   ```
5. Acesse o sistema com o usuário de exemplo criado pelo seeder:
   - **E-mail**: `admin@strasa.com`
   - **Senha**: `password`

## Configuração do Storage (Cloudflare R2 / S3)

O sistema foi configurado para usar o Cloudflare R2 como storage principal, evitando consumo excessivo de disco na hospedagem.

No seu arquivo `.env`, preencha as credenciais:

```env
AWS_ACCESS_KEY_ID=sua_access_key
AWS_SECRET_ACCESS_KEY=sua_secret_key
AWS_DEFAULT_REGION=auto
AWS_BUCKET=nome-do-bucket
AWS_ENDPOINT=https://4eb955b3831cbbe02e2e2aed8513ec0f.r2.cloudflarestorage.com
AWS_URL=https://public-url.r2.dev # Opcional, para arquivos públicos
AWS_USE_PATH_STYLE_ENDPOINT=true
```

## Instruções de Deploy (Hospedagem cPanel)

O deploy em cPanel com Laravel exige atenção a alguns detalhes para segurança e roteamento correto, já que o DocumentRoot do servidor geralmente aponta para a pasta `public_html`.

### Passo 1: Upload dos arquivos
1. Compacte toda a pasta do projeto (exceto a pasta `vendor`).
2. Faça o upload do ZIP para o servidor, na pasta "um nível acima" da public_html (ex.: `/home/usuario_cpanel/strasa`).
3. Extraia o ZIP na pasta `/home/usuario_cpanel/strasa`.

### Passo 2: Apontamento da pasta Public
Por questões de segurança, a pasta `public` do Laravel deve ser a única acessível web.
**Opção A (Recomendada): Alterar o DocumentRoot**
1. No cPanel, vá em "Domínios" (Domains).
2. Localize o domínio que irá acessar o sistema e altere o "Diretório de origem" (Document Root) para `/home/usuario_cpanel/strasa/public`.

**Opção B: Symlink (Se não puder alterar o DocumentRoot)**
1. Apague a pasta `public_html` nativa.
2. Via SSH ou terminal do cPanel, crie um link simbólico apontando para a public do projeto:
   `ln -s /home/usuario_cpanel/strasa/public /home/usuario_cpanel/public_html`

### Passo 3: Dependências e Banco de dados
1. Acesse o terminal do cPanel.
2. Navegue até o projeto: `cd strasa`
3. Instale as dependências rodando `composer install --optimize-autoloader --no-dev`. Se você não tiver Composer no servidor, faça o upload da pasta `vendor` que você compilou localmente.
4. Crie o banco de dados no "Bancos de dados MySQL" e adicione um usuário.
5. Edite o arquivo `.env` com as informações do banco do cPanel.
6. Rode as migrations pelo terminal: `php artisan migrate --force`.

### Passo 4: Configuração de Background Jobs e CRON
O sistema possui tarefas agendadas. Você precisa adicionar o cron job do Laravel no cPanel.
1. No cPanel, vá em **Trabalhos Cron (Cron Jobs)**.
2. Adicione a seguinte regra, configurada para rodar **Uma vez por minuto (* * * * *)**:
   ```bash
   cd /home/usuario_cpanel/strasa && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
   ```
   *(Atenção: verifique se o caminho para o binário do PHP na sua hospedagem é de fato `/usr/local/bin/php` ou simplesmente `php`)*

### Otimizações
Após configurar o `.env`, rode os seguintes comandos para otimizar o sistema para produção:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
