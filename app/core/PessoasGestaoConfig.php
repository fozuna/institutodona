<?php
namespace App\Core;

/**
 * Pilar de Pessoas, Sprint 02 - ponto único de configuração/labels para
 * GAPs, Feedbacks e Ações de Melhoria. Mesma filosofia de
 * PessoasAvaliacaoScale (Sprint 01): nenhuma View/Controller deve
 * hardcodar limites ou textos de status - sempre passar por aqui.
 */
final class PessoasGestaoConfig
{
    /** Nota <= este valor é apresentada como "potencial GAP" no resultado da avaliação. */
    public const POTENCIAL_GAP_LIMITE = 2;

    /** Nota >= este valor é apresentada com atalho de "Feedback Positivo". */
    public const DESTAQUE_POSITIVO_LIMITE = 4;

    public static function isPotencialGap(int $nota): bool
    {
        return $nota <= self::POTENCIAL_GAP_LIMITE;
    }

    public static function isDestaquePositivo(int $nota): bool
    {
        return $nota >= self::DESTAQUE_POSITIVO_LIMITE;
    }

    public static function gapPrioridadeLabels(): array
    {
        return ['baixa' => 'Baixa', 'media' => 'Média', 'alta' => 'Alta'];
    }

    public static function gapStatusLabels(): array
    {
        return ['aberto' => 'Aberto', 'em_tratamento' => 'Em tratamento', 'resolvido' => 'Resolvido'];
    }

    public static function gapStatusClasses(): array
    {
        return [
            'aberto' => 'bg-amber-100 text-amber-800',
            'em_tratamento' => 'bg-blue-100 text-blue-700',
            'resolvido' => 'bg-green-100 text-green-700',
        ];
    }

    public static function feedbackTipoLabels(): array
    {
        return ['positivo' => 'Feedback Positivo', 'melhoria' => 'Feedback de Melhoria'];
    }

    public static function feedbackTipoClasses(): array
    {
        return [
            'positivo' => 'bg-green-100 text-green-700',
            'melhoria' => 'bg-blue-100 text-blue-700',
        ];
    }

    public static function acaoStatusLabels(): array
    {
        return [
            'pendente' => 'Pendente',
            'em_andamento' => 'Em andamento',
            'concluida' => 'Concluída',
            'cancelada' => 'Cancelada',
        ];
    }

    public static function acaoStatusClasses(): array
    {
        return [
            'pendente' => 'bg-gray-100 text-gray-700',
            'em_andamento' => 'bg-amber-100 text-amber-800',
            'concluida' => 'bg-green-100 text-green-700',
            'cancelada' => 'bg-red-100 text-red-700',
        ];
    }

    // ---------------------------------------------------------------
    // Sprint 04 - PDI e visão gerencial
    // ---------------------------------------------------------------

    public static function pdiStatusLabels(): array
    {
        return ['rascunho' => 'Rascunho', 'ativo' => 'Ativo', 'concluido' => 'Concluído', 'cancelado' => 'Cancelado'];
    }

    public static function pdiStatusClasses(): array
    {
        return [
            'rascunho' => 'bg-gray-100 text-gray-700',
            'ativo' => 'bg-blue-100 text-blue-700',
            'concluido' => 'bg-green-100 text-green-700',
            'cancelado' => 'bg-red-100 text-red-700',
        ];
    }

    public static function objetivoStatusLabels(): array
    {
        return ['pendente' => 'Pendente', 'em_andamento' => 'Em andamento', 'concluido' => 'Concluído', 'cancelado' => 'Cancelado'];
    }

    public static function objetivoStatusClasses(): array
    {
        return [
            'pendente' => 'bg-gray-100 text-gray-700',
            'em_andamento' => 'bg-amber-100 text-amber-800',
            'concluido' => 'bg-green-100 text-green-700',
            'cancelado' => 'bg-red-100 text-red-700',
        ];
    }

    /**
     * Progresso do PDI = objetivos concluídos / objetivos válidos x 100.
     * Objetivos cancelados NÃO entram no denominador; sem objetivos válidos = 0.
     * Percentual de OBJETIVOS concluídos - não é desempenho. Não é persistido:
     * sempre derivado (fonte única deste cálculo).
     */
    public static function progressoPdi(int $concluidos, int $validos): float
    {
        if ($validos <= 0) {
            return 0.0;
        }
        return round(($concluidos / $validos) * 100, 2);
    }

    /** Apresentação do progresso (2 casas decimais, vírgula). */
    public static function formatProgresso(float $pct): string
    {
        return number_format($pct, 2, ',', '.') . '%';
    }

    /** Ação vencida = prazo < hoje e ainda ativa (nunca vira status no banco). Fragmento SQL para o alias informado. */
    public static function acaoVencidaSql(string $alias = 'ac'): string
    {
        return "({$alias}.prazo IS NOT NULL AND {$alias}.prazo < CURDATE() AND {$alias}.status IN ('pendente','em_andamento'))";
    }

    // ---------------------------------------------------------------
    // Sprint 05 - Gestão operacional
    // ---------------------------------------------------------------

    /**
     * GAP "com tratamento" = possui ao menos uma Ação de Melhoria vinculada que
     * NÃO esteja cancelada (ação cancelada não trata o GAP). Derivado, nunca
     * persistido; fonte única para listagem, Central e Visão Geral.
     */
    public static function gapComAcaoSql(string $alias = 'g'): string
    {
        return "EXISTS (SELECT 1 FROM pessoas_acoes_melhoria amx WHERE amx.gap_id = {$alias}.id AND amx.status <> 'cancelada')";
    }

    /** GAP aberto sem Ação de Melhoria (ponto de atenção operacional). */
    public static function gapAbertoSemAcaoSql(string $alias = 'g'): string
    {
        return "({$alias}.status = 'aberto' AND NOT " . self::gapComAcaoSql($alias) . ')';
    }

    public static function necessidadeStatusLabels(): array
    {
        return ['pendente' => 'Pendente', 'atendida' => 'Atendida', 'cancelada' => 'Cancelada'];
    }

    public static function necessidadeStatusClasses(): array
    {
        return [
            'pendente' => 'bg-amber-100 text-amber-800',
            'atendida' => 'bg-green-100 text-green-700',
            'cancelada' => 'bg-red-100 text-red-700',
        ];
    }

    /** Versão PHP de acaoVencidaSql() (mesma regra) para linhas já carregadas. */
    public static function isAcaoVencida(?string $prazo, string $status): bool
    {
        return $prazo !== null && $prazo !== '' && substr($prazo, 0, 10) < date('Y-m-d') && in_array($status, ['pendente', 'em_andamento'], true);
    }

    /** Objetivo de PDI vencido = prazo < hoje, não concluído e não cancelado (derivado). */
    public static function objetivoVencidoSql(string $alias = 'o'): string
    {
        return "({$alias}.prazo IS NOT NULL AND {$alias}.prazo < CURDATE() AND {$alias}.status IN ('pendente','em_andamento'))";
    }

    public static function isObjetivoVencido(?string $prazo, string $status): bool
    {
        return $prazo !== null && $prazo !== '' && $prazo < date('Y-m-d') && in_array($status, ['pendente', 'em_andamento'], true);
    }
}
