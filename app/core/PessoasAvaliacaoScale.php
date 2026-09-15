<?php
namespace App\Core;

/**
 * Ponto único de definição da escala de notas (1 a 5) e das faixas de
 * classificação textual do resultado de uma Avaliação de Desempenho
 * (Pilar de Pessoas). Nenhuma View/Controller/Model deve hardcodar esses
 * textos/faixas - sempre passar por esta classe, para que uma eventual
 * evolução futura (escala configurável) tenha um único lugar para mudar.
 */
final class PessoasAvaliacaoScale
{
    public const NOTA_MIN = 1;
    public const NOTA_MAX = 5;

    private const LABELS = [
        1 => 'Muito abaixo do esperado',
        2 => 'Abaixo do esperado',
        3 => 'Dentro do esperado',
        4 => 'Acima do esperado',
        5 => 'Excelente',
    ];

    /** Faixas de classificação do resultado (média ponderada 1.00-5.00), em ordem crescente. */
    private const BANDS = [
        ['min' => 1.00, 'max' => 1.99, 'label' => 'Muito abaixo do esperado'],
        ['min' => 2.00, 'max' => 2.99, 'label' => 'Abaixo do esperado'],
        ['min' => 3.00, 'max' => 3.99, 'label' => 'Dentro do esperado'],
        ['min' => 4.00, 'max' => 4.49, 'label' => 'Acima do esperado'],
        ['min' => 4.50, 'max' => 5.00, 'label' => 'Excelente'],
    ];

    public static function isValidNota(int $nota): bool
    {
        return $nota >= self::NOTA_MIN && $nota <= self::NOTA_MAX;
    }

    public static function notaLabel(int $nota): string
    {
        return self::LABELS[$nota] ?? '';
    }

    /** @return array<int,string> nota => label, para montar a legenda da escala na tela. */
    public static function scaleOptions(): array
    {
        return self::LABELS;
    }

    /**
     * Classificação textual do resultado geral/por grupo de uma avaliação.
     * $resultado deve já estar na faixa 1.00-5.00 (média ponderada).
     */
    public static function classification(float $resultado): string
    {
        foreach (self::BANDS as $band) {
            if ($resultado >= $band['min'] && $resultado <= $band['max']) {
                return $band['label'];
            }
        }
        if ($resultado < self::NOTA_MIN) {
            return self::BANDS[0]['label'];
        }
        return self::BANDS[count(self::BANDS) - 1]['label'];
    }

    /** @return array<int,array{min:float,max:float,label:string}> para exibir a legenda completa. */
    public static function classificationBands(): array
    {
        return self::BANDS;
    }

    /**
     * Média ponderada Σ(resposta × peso) / Σ(pesos), arredondada para 2
     * casas decimais (arredondamento matemático padrão, não truncamento).
     * $items: lista de ['resposta' => int, 'peso' => float].
     * Retorna null se não houver nenhum peso positivo (nada para calcular).
     */
    public static function weightedAverage(array $items): ?float
    {
        $sumWeighted = 0.0;
        $sumWeights = 0.0;
        foreach ($items as $item) {
            $peso = (float)($item['peso'] ?? 0);
            $resposta = $item['resposta'] ?? null;
            if ($resposta === null || $peso <= 0) {
                continue;
            }
            $sumWeighted += (float)$resposta * $peso;
            $sumWeights += $peso;
        }
        if ($sumWeights <= 0) {
            return null;
        }
        return round($sumWeighted / $sumWeights, 2);
    }
}
