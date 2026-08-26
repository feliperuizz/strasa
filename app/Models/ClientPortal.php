<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Portal de aprovação de um cliente.
 *
 * O `token` identifica o portal na URL e não é secreto por si só — quem tem
 * o link ainda precisa do `access_code` para entrar. O código fica
 * criptografado em repouso, mas continua legível para a equipe porque
 * precisamos copiá-lo na mensagem enviada ao cliente.
 */
class ClientPortal extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'client_id', 'token', 'access_code', 'code_hint',
        'is_active', 'code_updated_at', 'last_accessed_at', 'access_count',
        'notify_enabled', 'notify_user_ids', 'welcome_message',
    ];

    protected function casts(): array
    {
        return [
            'access_code' => 'encrypted',
            'notify_user_ids' => 'array',
            'is_active' => 'boolean',
            'notify_enabled' => 'boolean',
            'code_updated_at' => 'datetime',
            'last_accessed_at' => 'datetime',
            'access_count' => 'integer',
        ];
    }

    protected $hidden = ['access_code'];

    /* Relações ------------------------------------------------------------ */

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /* Fábrica ------------------------------------------------------------- */

    /**
     * Cria o portal do cliente já com token e código gerados.
     */
    public static function createForClient(Client $client): self
    {
        $code = self::generateCode();

        return self::create([
            'company_id' => $client->company_id,
            'client_id' => $client->id,
            'token' => self::generateToken(),
            'access_code' => $code,
            'code_hint' => substr($code, -4),
            'is_active' => true,
            'code_updated_at' => now(),
            'notify_enabled' => true,
            'notify_user_ids' => [],
        ]);
    }

    /**
     * Código curto, legível ao telefone: sem caracteres ambíguos (O/0, I/1).
     * Formato STR-XXXX-XXXX.
     */
    public static function generateCode(): string
    {
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $bloco = fn () => collect(range(1, 4))
            ->map(fn () => $alfabeto[random_int(0, strlen($alfabeto) - 1)])
            ->implode('');

        return 'STR-'.$bloco().'-'.$bloco();
    }

    public static function generateToken(): string
    {
        do {
            $token = Str::random(48);
        } while (self::withoutGlobalScopes()->where('token', $token)->exists());

        return $token;
    }

    /* Ações --------------------------------------------------------------- */

    /** Gera um código novo, invalidando o anterior. */
    public function rotateCode(): string
    {
        $code = self::generateCode();

        $this->forceFill([
            'access_code' => $code,
            'code_hint' => substr($code, -4),
            'code_updated_at' => now(),
            'is_active' => true,
        ])->save();

        return $code;
    }

    public function revoke(): void
    {
        $this->forceFill(['is_active' => false])->save();
    }

    public function reactivate(): void
    {
        $this->forceFill(['is_active' => true])->save();
    }

    public function registerAccess(): void
    {
        $this->forceFill([
            'last_accessed_at' => now(),
            'access_count' => $this->access_count + 1,
        ])->save();
    }

    /**
     * Comparação em tempo constante, para não vazar o código por timing.
     */
    public function codeMatches(string $entrada): bool
    {
        $normalizado = strtoupper(trim($entrada));

        return hash_equals($this->access_code, $normalizado);
    }

    /* Helpers ------------------------------------------------------------- */

    public function getUrlAttribute(): string
    {
        return route('portal.login', $this->token);
    }

    /** Mensagem pronta para a equipe copiar e mandar ao cliente. */
    public function shareMessage(): string
    {
        $cliente = $this->client->name;

        return implode("\n", [
            "Olá, {$cliente}! 👋",
            '',
            'Segue o seu painel de aprovação de artes:',
            $this->url,
            '',
            "Código de acesso: {$this->access_code}",
            '',
            'É só abrir o link, digitar o código e aprovar ou pedir ajuste em cada peça.',
            'Qualquer comentário que você deixar por lá chega direto para a nossa equipe.',
        ]);
    }

    /** Usuários que devem receber o push quando o cliente responder. */
    public function notifiables()
    {
        if (! $this->notify_enabled) {
            return collect();
        }

        $ids = $this->notify_user_ids ?: [];

        if (empty($ids)) {
            return collect();
        }

        return User::whereIn('id', $ids)
            ->where('company_id', $this->company_id)
            ->get();
    }
}
