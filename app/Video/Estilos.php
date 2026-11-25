<?php

namespace App\Video;

/**
 * Os estilos de edição do estúdio. Cada um tem um guia de ofício próprio
 * (resources/video/estilos/<chave>.md) que o Claude lê antes de decidir, e
 * padrões que o piloto automático usa quando o Claude não está disponível.
 *
 * "dicas_gravacao" aparece para a equipe na hora de enviar: material bom
 * vale mais do que qualquer edição.
 */
final class Estilos
{
    public const LISTA = [
        'espaco' => [
            'nome' => 'Tour do espaço',
            'resumo' => 'Clipes do ambiente viram um vídeo ritmado na batida da música, com a cara da marca.',
            'exemplo' => 'Gravou a barbearia: fachada, cadeiras, detalhes, o barbeiro trabalhando.',
            'icone' => 'espaco',
            'cor' => '#22d3ee',
            'fala' => false,
            'musica' => 'obrigatoria',
            'legendas' => false,
            'papeis' => ['bruto'],
            'duracao' => [12, 30, 20],
            'transicao' => 'corte',
            'dicas_gravacao' => [
                'Grave de 8 a 20 clipes curtos (3 a 8 s cada), celular em pé, parado ou andando devagar.',
                'Varie: fachada, ambiente aberto, detalhes de perto, alguém em ação, o resultado.',
                'Luz natural ou todas as luzes acesas. Limpe a lente antes.',
            ],
        ],
        'legenda' => [
            'nome' => 'Fala com legenda',
            'resumo' => 'Alguém falando para o celular: cortes nas pausas e erros, legenda animada palavra por palavra, zoom nos cortes.',
            'exemplo' => 'O dono gravou uma dica de 40 s olhando para a câmera.',
            'icone' => 'legenda',
            'cor' => '#a78bfa',
            'fala' => true,
            'musica' => 'opcional',
            'legendas' => true,
            'papeis' => ['bruto'],
            'duracao' => [15, 90, 45],
            'transicao' => 'corte',
            'dicas_gravacao' => [
                'Celular em pé, na altura dos olhos, rosto no terço de cima.',
                'Pode errar e repetir a frase: o corte escolhe o melhor take.',
                'Lugar silencioso; microfone de lapela ajuda muito.',
                'Dica de ouro: fale "nota de edição … fim da nota" para deixar um recado para a edição.',
            ],
        ],
        'apresentador' => [
            'nome' => 'Apresentador animado',
            'resumo' => 'Fala com animações em volta: palavras-chave, listas que entram item a item, setas, números, cartões e sons.',
            'exemplo' => 'Vídeo explicando "3 cuidados com a barba", com cada cuidado aparecendo na tela.',
            'icone' => 'apresentador',
            'cor' => '#f472b6',
            'fala' => true,
            'musica' => 'opcional',
            'legendas' => true,
            'papeis' => ['bruto'],
            'duracao' => [20, 90, 50],
            'transicao' => 'corte',
            'dicas_gravacao' => [
                'Mesmas dicas da fala com legenda.',
                'Deixe espaço acima da cabeça: é onde os cartões entram.',
                'Fale listas e números com clareza ("são três passos…"): viram animação.',
            ],
        ],
        'antes_depois' => [
            'nome' => 'Antes e depois',
            'resumo' => 'Transformação com revelação: o antes, a virada na batida, o depois em destaque.',
            'exemplo' => 'Corte de cabelo, limpeza de pele, reforma, polimento de carro.',
            'icone' => 'antes_depois',
            'cor' => '#34d399',
            'fala' => false,
            'musica' => 'obrigatoria',
            'legendas' => false,
            'papeis' => ['antes', 'depois', 'bruto'],
            'duracao' => [8, 25, 15],
            'transicao' => 'corte',
            'dicas_gravacao' => [
                'Grave o antes e o depois do MESMO ângulo e distância. Isso faz a mágica.',
                'Mesmo lugar, mesma luz. Um giro lento da pessoa ou do objeto fica ótimo.',
                'Marque cada arquivo como "antes" ou "depois" ao enviar. Clipes do processo entram como "processo".',
            ],
        ],
        'promo' => [
            'nome' => 'Oferta / vitrine',
            'resumo' => 'Produtos ou serviços com preço, urgência e chamada. Funciona até só com fotos.',
            'exemplo' => 'Promoção da semana: 3 serviços, preço de/por, "só até sábado".',
            'icone' => 'promo',
            'cor' => '#fb923c',
            'fala' => false,
            'musica' => 'obrigatoria',
            'legendas' => false,
            'papeis' => ['bruto'],
            'duracao' => [8, 30, 15],
            'transicao' => 'corte',
            'dicas_gravacao' => [
                'Fotos bem iluminadas ou clipes curtos de cada produto/serviço.',
                'Escreva no briefing os preços, o prazo da oferta e a chamada ("chame no WhatsApp").',
            ],
        ],
        'depoimento' => [
            'nome' => 'Depoimento de cliente',
            'resumo' => 'Um cliente contando a experiência: cortes limpos, legenda discreta, nome, estrelas e a frase-chave em destaque.',
            'exemplo' => 'Cliente saindo da barbearia dizendo o que achou.',
            'icone' => 'depoimento',
            'cor' => '#facc15',
            'fala' => true,
            'musica' => 'opcional',
            'legendas' => true,
            'papeis' => ['bruto'],
            'duracao' => [15, 60, 30],
            'transicao' => 'corte',
            'dicas_gravacao' => [
                'Peça autorização para usar a imagem do cliente (por escrito, até por WhatsApp).',
                'Faça perguntas abertas: "como foi?", "o que mais gostou?". A resposta vira o vídeo.',
                'Coloque o nome e a profissão do cliente no briefing.',
            ],
        ],
    ];

    public static function todos(): array
    {
        return self::LISTA;
    }

    public static function existe(?string $chave): bool
    {
        return $chave !== null && isset(self::LISTA[$chave]);
    }

    public static function get(?string $chave): array
    {
        return (self::LISTA[$chave] ?? self::LISTA['espaco']) + ['chave' => self::existe($chave) ? $chave : 'espaco'];
    }

    public static function guia(string $chave): string
    {
        $arquivo = resource_path("video/estilos/{$chave}.md");

        return is_file($arquivo) ? (string) file_get_contents($arquivo) : '';
    }
}
