<?php
namespace App\Core;

class AvaliacaoQuestionario
{
    public static function pilares(): array
    {
        return [
            'financeiro' => [
                'Planejamento estratégico?',
                'Quadro de indicadores estratégicos?',
                'Indicadores de desempenho por área?',
                'Informações gerenciais de fácil acesso?',
                'Reuniões de alinhamento estratégico?',
                'Gestão à vista?',
                'Registro dos planos de ação?',
            ],
            'mercado' => [
                'Missão, Visão e Valores?',
                'Processo comercial ativo e controlado?',
                'Relacionamento com fornecedores saudável?',
                'Pesquisa de satisfação de clientes?',
                'Canal de sugestões/fale conosco?',
                'Análise de concorrência/mercado?',
                'Práticas ambientais?',
            ],
            'pessoas' => [
                'Pesquisa de clima organizacional?',
                'Seleção adequada com teste de perfil?',
                'Integração com padrinhamento (onboarding)?',
                'Avaliação de gaps e feedback?',
                'Quadro de carreira e organograma?',
                'Desenvolvimento e treinamentos?',
                'PRG atualizado e implementado?',
            ],
            'processo' => [
                'Manual de processos?',
                'Treinamento/reciclagem de equipe nos manuais?',
                'Monitoramento da produção (indicadores)?',
                'Controle de ocorrências e erros?',
                'Garantia da qualidade de terceiros?',
                'Auditoria baseada nos manuais?',
                'Metodologia de incentivo à melhoria?',
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
