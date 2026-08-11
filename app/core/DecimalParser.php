<?php
namespace App\Core;

final class DecimalParser
{
    /**
     * Converte uma entrada de usuario (string ou numero) para float, aceitando
     * tanto o formato pt-BR (1.234,56) quanto o formato com ponto decimal
     * (1234.56 / 97.5), sem misturar as duas leituras na mesma entrada.
     *
     * Regra de desambiguacao entre ponto e virgula:
     * - Virgula presente (no maximo uma): ela e sempre o separador decimal;
     *   pontos que aparecerem antes dela sao tratados como separador de milhar
     *   e removidos (ex.: "1.234,56" -> 1234.56).
     * - Sem virgula, um unico ponto: o ponto e o separador decimal
     *   (ex.: "97.5" -> 97.5, "1234.56" -> 1234.56).
     * - Sem virgula, dois ou mais pontos: todos os pontos sao separador de
     *   milhar, exigindo agrupamento valido de 3 digitos (ex.: "12.345.678"
     *   -> 12345678).
     * - Qualquer outra combinacao (multiplas virgulas, agrupamento de milhar
     *   mal formado, caracteres invalidos) e rejeitada retornando null; nunca
     *   e convertida silenciosamente para zero.
     */
    public static function parse($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_string($value)) {
            if (!is_numeric($value)) {
                return null;
            }
            return round((float)$value, 4);
        }

        $raw = trim($value);
        $raw = str_replace(['R$', ' '], '', $raw);
        if ($raw === '' || preg_match('/[^0-9,.\-]/', $raw)) {
            return null;
        }

        $negative = false;
        if (str_starts_with($raw, '-')) {
            $negative = true;
            $raw = substr($raw, 1);
        }
        if ($raw === '' || str_contains($raw, '-')) {
            return null;
        }

        if (substr_count($raw, ',') > 1) {
            return null;
        }

        if (str_contains($raw, ',')) {
            [$intPart, $decPart] = explode(',', $raw, 2);
            if ($decPart === '' || !preg_match('/^\d+$/', $decPart)) {
                return null;
            }
            if ($intPart === '' || !preg_match('/^\d+(\.\d{3})*$/', $intPart)) {
                return null;
            }
            $normalized = str_replace('.', '', $intPart) . '.' . $decPart;
        } else {
            $dotCount = substr_count($raw, '.');
            if ($dotCount === 0) {
                if (!preg_match('/^\d+$/', $raw)) {
                    return null;
                }
                $normalized = $raw;
            } elseif ($dotCount === 1) {
                if (!preg_match('/^\d+\.\d+$/', $raw)) {
                    return null;
                }
                $normalized = $raw;
            } else {
                if (!preg_match('/^\d{1,3}(\.\d{3})+$/', $raw)) {
                    return null;
                }
                $normalized = str_replace('.', '', $raw);
            }
        }

        if (!is_numeric($normalized)) {
            return null;
        }
        $result = round((float)$normalized, 4);
        return $negative ? -$result : $result;
    }
}
