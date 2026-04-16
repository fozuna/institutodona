<?php
namespace App\Core;

class AvaliacaoQuestionario
{
    public static function pilares(): array
    {
        return [
            'eu' => [
                'Você tem uma saúde ideal e não depende de medicação frequentemente?',
                'Seus relacionamentos são com pessoas que promovem o seu bem estar? Te motivam e são incentivadas do seu sucesso?',
                'Você tem uma vida financeira saudável e sente que está prosperando a cada ano?',
                'Você acredita que sua entrega para o mundo impacta a vida das pessoas de forma verdadeira?',
                'Você está realizado no seu trabalho atual? Ele está alinhado ao que você gostaria de ser?',
                'Você tem realizado seus principais papéis de forma saudável? (pai, mãe, esposo (a), filho (a))',
                'Você tem uma conexão profunda e verdadeira com o que você acredita sobre espiritualidade?',
            ],
            'lideranca' => [
                'Você tem habilidade de inspirar confiança e desenvolver seu time?',
                'Você sabe dar feedbacks construtivos e lida bem com conflitos?',
                'Você está preparando sua equipe para tomar decisões difíceis sem depender de você?',
                'Você sente que o ambiente de trabalho que você cuida é saudável?',
                'Você tem preparado sucessores para o crescimento que você deseja alcançar?',
                'Você participou de algum programa de capacitação profissional nos últimos 12 meses?',
                'Você acredita que as pessoas que trabalham com vocês estão evoluindo?',
            ],
            'processo' => [
                'Os processos da sua empresa estão documentados e padronizados?',
                'Sua equipe já foi treinada e conhecem detalhadamente os processos?',
                'Existe um processo de integração de novos colaboradores?',
                'Existe uma reciclagem frequente dos processos implementados?',
                'Os processos da sua empresa estão garantindo a assertividade no cumprimento das tarefas?',
                'Há indicadores claros para medir se os processos estão sendo seguidos?',
                'As melhorias de processos acontecem continuamente ou só quando temos um problema?',
            ],
            'gestao' => [
                'Sua empresa acompanha indicadores de desempenho?',
                'Existem reuniões produtivas e regulares para avaliar os indicadores?',
                'Os gestores têm clareza sobre as metas que precisam cumprir?',
                'Os problemas operacionais são resolvidos de maneira eficiente uma única vez?',
                'Existe um painel de gestão para acompanhamento do desempenho das áreas?',
                'Você realiza a revisão do planejamento estratégico anualmente?',
                'Você comemora as conquistas junto a sua equipe com frequência?',
            ],
        ];
    }

    public static function totais(): array
    {
        $totais = [];
        foreach (self::pilares() as $chave => $perguntas) {
            $totais[$chave] = count($perguntas);
        }
        return $totais;
    }
}
