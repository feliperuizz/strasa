<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Video\Armazenamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Identidade de vídeo do cliente: o "perfil" do pacote de skills
 * (identidade.md + estilo-*.md), só que por cliente e editável na tela.
 * Tudo aqui vira contexto para o Claude e parâmetro para o render.
 */
class VideoProfile extends Model
{
    use BelongsToCompany;
    use Concerns\GuardaEmUtc;

    public const POSICOES_LOGO = [
        'topo_esquerda' => 'Topo à esquerda',
        'topo_direita' => 'Topo à direita',
        'baixo_esquerda' => 'Embaixo à esquerda',
        'baixo_direita' => 'Embaixo à direita',
        'nenhum' => 'Sem logo durante o vídeo',
    ];

    public const ESTILOS_LEGENDA = [
        'karaoke' => ['nome' => 'Karaokê', 'descricao' => 'Grupo de 1–3 palavras, a palavra falada acende na cor de destaque.'],
        'pop' => ['nome' => 'Pop', 'descricao' => 'Uma palavra por vez, grande, entrando com um "pulo".'],
        'caixa' => ['nome' => 'Caixa', 'descricao' => 'Frase curta sobre uma faixa na cor da marca.'],
        'simples' => ['nome' => 'Discreta', 'descricao' => 'Frase inteira, menor, com sombra. Para vídeos mais sóbrios.'],
    ];

    public const GRADES = [
        'natural' => 'Natural (só corrige)',
        'quente' => 'Quente (aconchego, barbearia, café)',
        'frio' => 'Frio (clínica, tecnologia)',
        'vivo' => 'Vivo (cores saturadas, varejo)',
        'cinema' => 'Cinema (contraste, sombras azuladas)',
        'pb' => 'Preto e branco',
    ];

    /** Fontes que o instalador baixa (Google Fonts, licença OFL). */
    public const FONTES = ['Montserrat', 'Poppins', 'Bebas Neue', 'Anton', 'Inter', 'Sora', 'Playfair Display', 'Oswald', 'Archivo Black', 'DM Serif Display'];

    protected $fillable = [
        'company_id', 'client_id', 'cor_primaria', 'cor_destaque', 'cor_texto', 'cor_fundo',
        'fonte_titulo', 'fonte_texto', 'fonte_path', 'fonte_nome', 'logo_path', 'logo_posicao',
        'logo_tamanho', 'logo_opacidade', 'legenda_estilo', 'legenda_posicao', 'legenda_caixa_alta',
        'cor_grade', 'cta_texto', 'instagram', 'whatsapp', 'site', 'endereco', 'vibe', 'instrucoes',
        'musica_padrao_id',
    ];

    protected $attributes = [
        'cor_primaria' => '#111827',
        'cor_destaque' => '#F59E0B',
        'cor_texto' => '#FFFFFF',
        'cor_fundo' => '#0B0B0F',
        'fonte_titulo' => 'Montserrat',
        'fonte_texto' => 'Montserrat',
        'logo_posicao' => 'topo_direita',
        'logo_tamanho' => 16,
        'logo_opacidade' => 90,
        'legenda_estilo' => 'karaoke',
        'legenda_posicao' => 'baixo',
        'legenda_caixa_alta' => true,
        'cor_grade' => 'natural',
    ];

    protected function casts(): array
    {
        return [
            'legenda_caixa_alta' => 'boolean',
            'logo_tamanho' => 'integer',
            'logo_opacidade' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function musicaPadrao(): BelongsTo
    {
        return $this->belongsTo(VideoAsset::class, 'musica_padrao_id');
    }

    /** Perfil salvo do cliente, ou um novo com os padrões (cor do cliente como destaque). */
    public static function doCliente(Client $client): self
    {
        $perfil = self::withoutGlobalScopes()->firstWhere('client_id', $client->id);

        if ($perfil) {
            return $perfil;
        }

        $novo = new self(['client_id' => $client->id, 'company_id' => $client->company_id]);

        if (preg_match('/^#[0-9a-f]{6}$/i', (string) $client->color)) {
            $novo->cor_destaque = strtoupper($client->color);
        }

        return $novo;
    }

    public function fonteTituloEfetiva(): string
    {
        return $this->fonte_path && $this->fonte_nome ? $this->fonte_nome : $this->fonte_titulo;
    }

    public function caminhoLogo(): ?string
    {
        return $this->logo_path ? Armazenamento::biblioteca($this->logo_path) : null;
    }

    public function caminhoFonte(): ?string
    {
        return $this->fonte_path ? Armazenamento::biblioteca($this->fonte_path) : null;
    }

    /** O que vira bloco "Identidade" no contexto do Claude. */
    public function resumoParaClaude(): array
    {
        return array_filter([
            'cores' => [
                'primaria' => $this->cor_primaria,
                'destaque' => $this->cor_destaque,
                'texto' => $this->cor_texto,
                'fundo' => $this->cor_fundo,
            ],
            'fonte_titulos' => $this->fonteTituloEfetiva(),
            'fonte_textos' => $this->fonte_texto,
            'logo' => $this->logo_posicao === 'nenhum' ? 'sem logo durante o vídeo (só no cartão final)' : 'logo fixa em '.(self::POSICOES_LOGO[$this->logo_posicao] ?? $this->logo_posicao),
            'legenda' => (self::ESTILOS_LEGENDA[$this->legenda_estilo]['nome'] ?? $this->legenda_estilo).', posição '.$this->legenda_posicao.($this->legenda_caixa_alta ? ', em caixa alta' : ''),
            'cor_da_imagem' => self::GRADES[$this->cor_grade] ?? $this->cor_grade,
            'chamada_final' => $this->cta_texto,
            'instagram' => $this->instagram,
            'whatsapp' => $this->whatsapp,
            'site' => $this->site,
            'endereco' => $this->endereco,
            'vibe' => $this->vibe,
            'instrucoes_da_agencia' => $this->instrucoes,
        ], fn ($v) => filled($v));
    }
}
