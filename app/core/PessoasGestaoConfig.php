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
}
