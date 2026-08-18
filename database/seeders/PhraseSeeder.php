<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Phrase;

class PhraseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $phrases = [
            // MORNING
            ['type' => 'morning', 'content' => 'Bom dia flor do dia! O café já tá pronto ou vai enrolar mais?'],
            ['type' => 'morning', 'content' => 'Tava dormindo até tarde, né? Vamo acordar que o boleto não se paga sozinho!'],
            ['type' => 'morning', 'content' => 'Bom dia! O sol já nasceu lá na fazendinha, bora trabalhar!'],
            ['type' => 'morning', 'content' => 'Vamo que vamo! Mais um dia pra gente brilhar (ou tentar sobreviver).'],
            ['type' => 'morning', 'content' => 'Bom dia pra quem acordou com a energia lá em cima! Pra você que não, bom dia também.'],
            ['type' => 'morning', 'content' => 'Acorda menina! Já bebeu sua água e pegou seu café hoje?'],
            ['type' => 'morning', 'content' => 'Bom dia! Já separou aquele sorriso falso pra reunião de hoje?'],
            ['type' => 'morning', 'content' => 'Dormiu bem? Espero que sim, porque o dia de hoje promete! Bora!'],
            ['type' => 'morning', 'content' => 'Olha quem acordou! Seja bem-vindo ao maravilhoso mundo corporativo.'],
            ['type' => 'morning', 'content' => 'Bom dia! Respira fundo, toma um café duplo e foca nas entregas.'],
            
            // EVENING
            ['type' => 'evening', 'content' => 'Trabalhando até agora? Vai chover!'],
            ['type' => 'evening', 'content' => 'Já deu a hora, fecha esse notebook e vai viver um pouco!'],
            ['type' => 'evening', 'content' => 'Se você continuar trabalhando a essa hora, eu vou começar a cobrar hora extra.'],
            ['type' => 'evening', 'content' => 'Opa, horário nobre! A empresa agradece sua dedicação, mas sua cama tá com saudades.'],
            ['type' => 'evening', 'content' => 'Ninguém merece trabalhar depois das 18h. Bora descansar que amanhã tem mais.'],
            ['type' => 'evening', 'content' => 'O expediente acabou, chefe! Vai ver uma série e esquecer dos problemas.'],
            ['type' => 'evening', 'content' => 'Tá pagando promessa? Desliga isso aí e vai jantar!'],
            ['type' => 'evening', 'content' => 'Nessa hora da noite a produtividade já foi pro espaço. Vai descansar!'],
            ['type' => 'evening', 'content' => 'Chega de tela por hoje! Amanhã com a mente fresca as coisas fluem melhor.'],
            ['type' => 'evening', 'content' => 'Você é dono da empresa? Se não for, desliga o PC agora!'],

            // DAILY
            ['type' => 'daily', 'content' => 'Não deixe para amanhã o café que você pode tomar hoje.'],
            ['type' => 'daily', 'content' => 'A única maneira de fazer um ótimo trabalho é amar o que você faz (e ser bem pago por isso).'],
            ['type' => 'daily', 'content' => 'Acredite no seu potencial. Ou pelo menos finja até conseguir.'],
            ['type' => 'daily', 'content' => 'Se o plano A não funcionar, o alfabeto tem mais 25 letras. Fica a dica.'],
            ['type' => 'daily', 'content' => 'Foco, força e fésse café não esfriar!'],
            ['type' => 'daily', 'content' => 'Organize suas tarefas antes que elas organizem a sua sanidade.'],
            ['type' => 'daily', 'content' => 'Sabe aquela tarefa que você tá procrastinando? É, vai ter que fazer ela agora.'],
            ['type' => 'daily', 'content' => 'Reunião que poderia ser um email: a gente vê por aqui.'],
            ['type' => 'daily', 'content' => 'Seja a pessoa que você precisava ter no seu time.'],
            ['type' => 'daily', 'content' => 'Não sabendo que era impossível, foi lá e descobriu que era mesmo. Brincadeira, você consegue!'],
            ['type' => 'daily', 'content' => 'O sucesso é a soma de pequenos esforços repetidos dia sim, dia também.'],
            ['type' => 'daily', 'content' => 'Respire fundo. Feche abas desnecessárias (inclusive as da mente).'],
            ['type' => 'daily', 'content' => 'Produtividade não é fazer muito, é fazer o que realmente importa.'],
            ['type' => 'daily', 'content' => 'Que o seu Wi-Fi seja forte e o seu código sem bugs.'],
            ['type' => 'daily', 'content' => 'Lembre-se: O CTRL+Z é o melhor amigo do homem moderno.'],
            ['type' => 'daily', 'content' => 'Se tudo estiver dando errado, lembre-se que pelo menos você não é um bug em produção.'],
            ['type' => 'daily', 'content' => 'Aproveite as pequenas vitórias do dia, tipo conseguir centralizar uma div de primeira.'],
            ['type' => 'daily', 'content' => 'Sua melhor versão é aquela que já tomou o café da tarde.'],
            ['type' => 'daily', 'content' => 'Transforme o estresse em foco. E se não der, transforme em intervalo.'],
            ['type' => 'daily', 'content' => 'Mais um dia lindo para ignorar os problemas e focar na entrega urgente!'],
        ];

        foreach ($phrases as $phrase) {
            Phrase::create($phrase);
        }
    }
}
