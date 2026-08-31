<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma leitura das métricas de uma rede social do cliente numa data.
 *
 * Guardamos números absolutos (o total de seguidores naquele dia); o ganho
 * é derivado comparando lançamentos consecutivos.
 */
class ClientMetric extends Model
{
    use BelongsToCompany;

    /** Redes aceitas, com rótulo e cor usados nos gráficos. */
    public const NETWORKS = [
        'instagram' => ['label' => 'Instagram', 'color' => '#E1306C'],
        'facebook' => ['label' => 'Facebook', 'color' => '#1877F2'],
        'tiktok' => ['label' => 'TikTok', 'color' => '#00F2EA'],
        'youtube' => ['label' => 'YouTube', 'color' => '#FF0000'],
        'linkedin' => ['label' => 'LinkedIn', 'color' => '#0A66C2'],
        'x' => ['label' => 'X / Twitter', 'color' => '#94A3B8'],
        'pinterest' => ['label' => 'Pinterest', 'color' => '#BD081C'],
        'kwai' => ['label' => 'Kwai', 'color' => '#FF7A00'],
        'google' => ['label' => 'Google Meu Negócio', 'color' => '#34A853'],
    ];

    /**
     * Campos numéricos, com rótulo — usados no formulário e nos cards.
     *
     * As três médias são POR PUBLICAÇÃO. O engajamento total não é digitado:
     * sai da soma delas (ver engagementPerPost).
     */
    public const FIELDS = [
        'followers' => 'Seguidores (total)',
        'avg_likes' => 'Média de curtidas',
        'avg_comments' => 'Média de comentários',
        'avg_shares' => 'Média de compartilhamentos',
        'views' => 'Visualizações',
        'profile_visits' => 'Visitas ao perfil',
        'link_clicks' => 'Cliques no link',
        'posts_count' => 'Publicações',
    ];

    protected $fillable = [
        'company_id', 'client_id', 'network', 'reference_date',
        'followers', 'avg_likes', 'avg_comments', 'avg_shares', 'views',
        'profile_visits', 'link_clicks', 'posts_count',
        'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'reference_date' => 'date',
            'followers' => 'integer',
            'avg_likes' => 'integer',
            'avg_comments' => 'integer',
            'avg_shares' => 'integer',
            'views' => 'integer',
            'profile_visits' => 'integer',
            'link_clicks' => 'integer',
            'posts_count' => 'integer',
        ];
    }

    /* Relações ------------------------------------------------------------ */

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* Scopes -------------------------------------------------------------- */

    public function scopeNetwork(Builder $query, ?string $network): Builder
    {
        return $network ? $query->where('network', $network) : $query;
    }

    public function scopeBetween(Builder $query, ?string $de, ?string $ate): Builder
    {
        return $query
            ->when($de, fn ($q) => $q->whereDate('reference_date', '>=', $de))
            ->when($ate, fn ($q) => $q->whereDate('reference_date', '<=', $ate));
    }

    /* Helpers ------------------------------------------------------------- */

    public function networkLabel(): string
    {
        return self::NETWORKS[$this->network]['label'] ?? ucfirst($this->network);
    }

    public function networkColor(): string
    {
        return self::NETWORKS[$this->network]['color'] ?? '#64748B';
    }

    /**
     * Interações médias por publicação = curtidas + comentários +
     * compartilhamentos. Null quando nenhuma das três foi informada.
     */
    public function engagementPerPost(): ?int
    {
        $partes = [$this->avg_likes, $this->avg_comments, $this->avg_shares];

        if (count(array_filter($partes, fn ($v) => $v !== null)) === 0) {
            return null;
        }

        return (int) array_sum($partes);
    }

    /**
     * Taxa de engajamento: interações médias por publicação sobre o total de
     * seguidores. É a fórmula que o mercado usa para comparar perfis de
     * tamanhos diferentes.
     */
    public function engagementRate(): ?float
    {
        $interacoes = $this->engagementPerPost();

        if (! $interacoes || ! $this->followers) {
            return null;
        }

        return round($interacoes / $this->followers * 100, 2);
    }
}
