<?php

namespace App\Providers;

use App\Database\MariaDbConnection;
use App\Database\MySqlConnection;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // O sistema roda no horário de Brasília e o banco guarda em UTC:
        // as conexões convertem as datas das consultas para UTC.
        Connection::resolverFor('mysql', fn ($pdo, $banco, $prefixo, $config) => new MySqlConnection($pdo, $banco, $prefixo, $config));
        Connection::resolverFor('mariadb', fn ($pdo, $banco, $prefixo, $config) => new MariaDbConnection($pdo, $banco, $prefixo, $config));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Produção é HTTPS: garante que links gerados (inclusive os links
        // assinados de mídia da API) saiam com https mesmo atrás de proxy.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // API de postagem: limite por chave (ou por IP, nos links de mídia).
        RateLimiter::for('api-postagem', function (Request $request) {
            $chave = $request->attributes->get('api_token');

            return Limit::perMinute((int) config('services.api_postagem.limite_por_minuto', 120))
                ->by($chave ? 'chave:'.$chave->id : 'ip:'.$request->ip());
        });
    }
}
