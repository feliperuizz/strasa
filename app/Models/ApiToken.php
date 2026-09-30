<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Chave da API de postagem automática (sistema parceiro que publica os posts).
 *
 * A chave em texto aberto só existe no momento da criação: aqui fica apenas o
 * hash SHA-256 e o prefixo, para o painel mostrar qual é qual.
 */
class ApiToken extends Model
{
    use BelongsToCompany;

    public const PREFIXO = 'str_';

    protected $fillable = [
        'company_id', 'name', 'token_prefix', 'token_hash', 'all_clients', 'auto_complete',
        'allowed_ips', 'expires_at', 'revoked_at', 'last_used_at', 'last_used_ip', 'created_by',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'all_clients' => 'boolean',
            'auto_complete' => 'boolean',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    /* --------------------------------------------------------------------- */
    /* Relações                                                              */
    /* --------------------------------------------------------------------- */

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'api_token_client');
    }

    public function publications(): HasMany
    {
        return $this->hasMany(TaskPublication::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* --------------------------------------------------------------------- */
    /* Segredo                                                               */
    /* --------------------------------------------------------------------- */

    /** Gera um segredo novo, grava só o hash e devolve o texto aberto (mostrado uma vez). */
    public function gerarSegredo(): string
    {
        $segredo = self::PREFIXO.Str::random(44);

        $this->forceFill([
            'token_prefix' => substr($segredo, 0, 12),
            'token_hash' => self::hashDe($segredo),
        ]);

        return $segredo;
    }

    public static function hashDe(string $segredo): string
    {
        return hash('sha256', $segredo);
    }

    /** Acha a chave pelo segredo recebido no header. Ignora escopo de empresa (a API não tem usuário). */
    public static function peloSegredo(string $segredo): ?self
    {
        if (! str_starts_with($segredo, self::PREFIXO) || strlen($segredo) < 20) {
            return null;
        }

        return self::withoutGlobalScopes()->where('token_hash', self::hashDe($segredo))->first();
    }

    /* --------------------------------------------------------------------- */
    /* Estado                                                                */
    /* --------------------------------------------------------------------- */

    public function estaRevogada(): bool
    {
        return $this->revoked_at !== null;
    }

    public function estaExpirada(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function estaAtiva(): bool
    {
        return ! $this->estaRevogada() && ! $this->estaExpirada();
    }

    public function situacao(): string
    {
        return match (true) {
            $this->estaRevogada() => 'revogada',
            $this->estaExpirada() => 'expirada',
            default => 'ativa',
        };
    }

    /** Lista de IPs/faixas permitidos; vazia = qualquer IP. */
    public function ipsPermitidos(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\s,;]+/', (string) $this->allowed_ips) ?: [])));
    }

    public function aceitaIp(?string $ip): bool
    {
        $lista = $this->ipsPermitidos();

        return $lista === [] || ($ip !== null && IpUtils::checkIp($ip, $lista));
    }

    /* --------------------------------------------------------------------- */
    /* Escopo                                                                */
    /* --------------------------------------------------------------------- */

    /** Clientes que esta chave enxerga: da mesma empresa, não arquivados, e da lista se não for "todos". */
    public function clientesPermitidos(): Builder
    {
        return Client::withoutGlobalScopes()
            ->where('clients.company_id', $this->company_id)
            ->whereNull('clients.archived_at')
            ->when(! $this->all_clients, fn ($q) => $q->whereIn('clients.id', $this->clients()->pluck('clients.id')));
    }

    public function permiteCliente(int $clientId): bool
    {
        return $this->clientesPermitidos()->where('clients.id', $clientId)->exists();
    }
}
