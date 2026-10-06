<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * O fechamento mensal das métricas de uma rede social do cliente.
 *
 * Um lançamento por rede por MÊS: `reference_date` guarda o dia 1 do mês de
 * referência (o fechamento de setembro fica em 2026-09-01). Lançamentos
 * antigos, de antes do fechamento mensal, podem ter outro dia — valem pelo
 * mês deles, e relançar o mês os consolida num só.
 *
 * Guardamos números absolutos (o total de seguidores no fechamento); o
 * ganho é derivado comparando meses consecutivos.
 */
class ClientMetric extends Model
{
    use Concerns\GuardaEmUtc;

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

    /** Médias por publicação: aceitam casas decimais (2,2 comentários por post). */
    public const DECIMAIS = ['avg_likes', 'avg_comments', 'avg_shares'];

    private const MESES = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

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
            'avg_likes' => 'float',
            'avg_comments' => 'float',
            'avg_shares' => 'float',
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

    /** Lançamentos entre dois meses ("AAAA-MM"), inclusive. */
    public function scopeEntreMeses(Builder $query, ?string $de, ?string $ate): Builder
    {
        return $query
            ->when($de, fn ($q) => $q->whereDate('reference_date', '>=', $de.'-01'))
            ->when($ate, fn ($q) => $q->whereDate('reference_date', '<=', Carbon::createFromFormat('Y-m-d', $ate.'-01')->endOfMonth()->toDateString()));
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

    /** Mês de referência como "AAAA-MM" (o valor dos campos de mês). */
    public function mes(): string
    {
        return $this->reference_date->format('Y-m');
    }

    /** "set/2026" (ou "set/26" com $curto, para eixos de gráfico). */
    public function rotuloDoMes(bool $curto = false): string
    {
        return self::rotuloDe($this->reference_date, $curto);
    }

    public static function rotuloDe(\DateTimeInterface $data, bool $curto = false): string
    {
        return self::MESES[(int) $data->format('n') - 1].'/'.$data->format($curto ? 'y' : 'Y');
    }

    /**
     * Número no padrão brasileiro, com casas decimais só quando existem:
     * 1.250 · 2,2 · 15,75. Null vira "—".
     */
    public static function formatar(int|float|null $valor): string
    {
        if ($valor === null) {
            return '—';
        }

        $texto = number_format((float) $valor, 2, ',', '.');

        return str_ends_with($texto, ',00') ? substr($texto, 0, -3) : rtrim($texto, '0');
    }

    /**
     * Interações médias por publicação = curtidas + comentários +
     * compartilhamentos. Null quando nenhuma das três foi informada.
     */
    public function engagementPerPost(): ?float
    {
        $partes = [$this->avg_likes, $this->avg_comments, $this->avg_shares];

        if (count(array_filter($partes, fn ($v) => $v !== null)) === 0) {
            return null;
        }

        return round((float) array_sum($partes), 2);
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
