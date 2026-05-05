<?php
namespace App\Core;

final class ValueFormatter
{
    public static function decimal($value, int $scale = 2): string
    {
        $number = is_numeric($value) ? (float)$value : 0.0;
        return number_format(round($number, $scale), $scale, ',', '.');
    }

    public static function byUnit($value, ?array $unit = null, bool $withSymbol = true): string
    {
        $formatted = self::decimal($value, 2);
        if (!$withSymbol || empty($unit['simbolo'])) {
            return $formatted;
        }

        $symbol = trim((string)$unit['simbolo']);
        $type = strtolower(trim((string)($unit['tipo'] ?? '')));

        if ($type === 'monetaria') {
            return $formatted . ' ' . $symbol;
        }

        if ($type === 'percentual') {
            return $formatted . $symbol;
        }

        return $formatted . ' ' . $symbol;
    }

    public static function percent($value): string
    {
        return self::decimal($value, 2) . '%';
    }
}
