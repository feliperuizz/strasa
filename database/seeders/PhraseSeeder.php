<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Phrase;
use Illuminate\Support\Facades\DB;

class PhraseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpa a tabela antes de popular novamente
        DB::table('phrases')->truncate();

        $phrases = [
            // MORNING (Manhã)
            ['type' => 'morning', 'content' => 'Bom dia! Já checou o TikTok hoje ou vai deixar a concorrência viralizar primeiro?'],
            ['type' => 'morning', 'content' => 'Acorda menina! O café tá quente e o cliente já tá no WhatsApp pedindo "aquela alteraçãozinha".'],
            ['type' => 'morning', 'content' => 'Bom dia flor do dia! Bora roteirizar aquele Reels que vai bater 1 milhão de views?'],
            ['type' => 'morning', 'content' => 'Vamo que vamo! A pauta não se aprova sozinha e o engajamento não cai do céu.'],
            ['type' => 'morning', 'content' => 'Dormiu bem? Espero que sim, porque o dia de hoje tem três criativos atrasados pra salvar!'],
            ['type' => 'morning', 'content' => 'Bom dia! Respira fundo, toma um café duplo e se prepara pra ler "aumenta o logo".'],
            ['type' => 'morning', 'content' => 'Acorda pra cuspir engajamento! Bora fazer esse feed ficar impecável.'],
            ['type' => 'morning', 'content' => 'Bom dia! O sol nasceu para todos, mas o alcance orgânico a gente tem que suar pra conseguir.'],
            ['type' => 'morning', 'content' => 'Bom dia pra quem acordou pronto pra entregar a arte de primeira (sonhar não custa nada).'],
            ['type' => 'morning', 'content' => 'Levanta, sacode a poeira e abre o Canva/Photoshop. Mais um dia salvando a imagem da marca alheia!'],
            
            // EVENING (Fim de Tarde / Noite)
            ['type' => 'evening', 'content' => 'Trabalhando até agora? O algoritmo já foi dormir, vai você também!'],
            ['type' => 'evening', 'content' => 'Exportando vídeo a essa hora? Desliga esse Premiere e vai viver a vida!'],
            ['type' => 'evening', 'content' => 'Já deu a hora! Pode fechar esse Photoshop e ir maratonar uma série.'],
            ['type' => 'evening', 'content' => 'Opa, horário nobre! A agência agradece, mas o alcance do seu cérebro já tá no mínimo.'],
            ['type' => 'evening', 'content' => 'Ninguém merece aprovar arte depois das 18h. Salva tudo e bora pra casa!'],
            ['type' => 'evening', 'content' => 'Se você continuar mexendo nesse layout a essa hora, vai acabar estragando. Vai descansar!'],
            ['type' => 'evening', 'content' => 'O expediente acabou, marqueteiro! Deixa a criatividade recarregar pra amanhã.'],
            ['type' => 'evening', 'content' => 'Tá pagando promessa? O tráfego pago trabalha 24h, mas você não precisa.'],
            ['type' => 'evening', 'content' => 'Chega de telinha por hoje. Vai dar um descanso pros olhos e pra mente.'],
            ['type' => 'evening', 'content' => 'Você não é o Zuckerberg pra ficar online até essa hora. Desliga isso aí!'],

            // DAILY (Diárias - Motivacionais, Zueiras, Rotina de Agência)
            ['type' => 'daily', 'content' => 'Não deixe para amanhã o Reels que você pode gravar e editar hoje.'],
            ['type' => 'daily', 'content' => 'Acredite no seu potencial! Se aquele áudio do "Bora Bill" viralizou, a sua ideia também pode.'],
            ['type' => 'daily', 'content' => '"Aumenta o logo e coloca mais cor." Respire fundo, conte até 10 e sorria.'],
            ['type' => 'daily', 'content' => 'Sabe aquele bloqueio criativo? Ele passa rapidinho com um café forte e um pix caindo na conta.'],
            ['type' => 'daily', 'content' => 'O sucesso é a soma de um Copy matador, uma Arte bonita e um Tráfego bem segmentado.'],
            ['type' => 'daily', 'content' => 'Que o seu Wi-Fi seja forte e suas exportações no Premiere sejam rápidas.'],
            ['type' => 'daily', 'content' => 'Seja o tipo de profissional que o algoritmo do Instagram amaria entregar 100%.'],
            ['type' => 'daily', 'content' => 'Aproveite as pequenas vitórias do dia, tipo: o cliente aprovar a arte de primeira, sem refação.'],
            ['type' => 'daily', 'content' => 'Mais um dia lindo para ignorar os problemas e focar na pauta que tá atrasada!'],
            ['type' => 'daily', 'content' => 'Organize seus criativos antes que eles organizem a sua sanidade mental.'],
            ['type' => 'daily', 'content' => 'O cliente tem sempre razão? Não, mas a gente faz ele achar que sim. Bora produzir!'],
            ['type' => 'daily', 'content' => 'Reunião de briefing que poderia ser um áudio no WhatsApp: a gente vê por aqui.'],
            ['type' => 'daily', 'content' => 'Se tudo estiver dando errado, lembre-se: pelo menos você não mandou a arte com erro de português pro ar.'],
            ['type' => 'daily', 'content' => 'Lembre-se: O CTRL+Z  é o melhor amigo do designer contemporâneo.'],
            ['type' => 'daily', 'content' => 'Copiar é feio. O nome chique para isso é "Fazer Benchmarking". Fica a dica!'],
            ['type' => 'daily', 'content' => 'Nenhuma tempestade dura pra sempre. E nenhuma refação dura mais que 5 rodadas (eu espero).'],
            ['type' => 'daily', 'content' => 'Confie no processo. Mas na dúvida, coloque uma musiquinha em alta no fundo que ajuda.'],
            ['type' => 'daily', 'content' => 'Toda grande marca começou com um post flopado. O segredo é a consistência.'],
            ['type' => 'daily', 'content' => 'Seu melhor trabalho ainda está por vir (provavelmente depois que você tomar mais café).'],
            ['type' => 'daily', 'content' => 'Crie com o coração. Edite com a cabeça. Aprove com o cliente (e não discuta o gosto dele).'],

            // DESMOTIVACIONAIS (Engraçadas)
            ['type' => 'daily', 'content' => 'O "não" você já tem. Agora bora atrás da humilhação com o cliente!'],
            ['type' => 'daily', 'content' => 'Lembre-se: o seu esforço de hoje é a garantia das férias do dono da agência amanhã.'],
            ['type' => 'daily', 'content' => 'Não deixe que nada te desanime. O boleto vence independente do seu bloqueio criativo.'],
            ['type' => 'daily', 'content' => 'Não sabendo que era impossível, foi lá e descobriu por que ninguém faz.'],
            ['type' => 'daily', 'content' => 'Tudo pode piorar. Se ainda não piorou, é porque o cliente ainda não visualizou a arte.'],
            ['type' => 'daily', 'content' => 'Trabalhe com o que você ama e você nunca mais vai amar nada na vida.'],
            ['type' => 'daily', 'content' => 'Nunca desista dos seus sonhos. Vai dormir que amanhã tem mais refação pra entregar.'],
            ['type' => 'daily', 'content' => 'O cansaço passa, mas a vergonha do post flopado fica pra sempre na timeline.'],
            ['type' => 'daily', 'content' => 'Sabe aquela luz no fim do túnel? Pode ser o trem das demandas urgentes chegando.'],
            ['type' => 'daily', 'content' => 'Esforce-se hoje para amanhã ganhar como recompensa mais trabalho ainda!'],
        ];

        foreach ($phrases as $phrase) {
            Phrase::create($phrase);
        }
    }
}
