<?php
namespace App\Core;

class FaturamentoFaixas
{
    public static function opcoes(): array
    {
        return [
            1 => 'Até R$ 100.000,00',
            2 => 'De R$ 100.001,00 a R$ 250.000,00',
            3 => 'De R$ 250.001,00 a R$ 500.000,00',
            4 => 'De R$ 500.001,00 a R$ 750.000,00',
            5 => 'De R$ 750.001,00 a R$ 1.000.000,00',
            6 => 'Acima de R$ 1.000.000,00',
        ];
    }

    public static function ids(): array
    {
        return array_keys(self::opcoes());
    }

    public static function isValidId($id): bool
    {
        return in_array((int)$id, self::ids(), true);
    }

    public static function descricao($id, $legacyAmount = null): string
    {
        $id = self::normalizeId($id);
        if ($id !== null) {
            return self::opcoes()[$id];
        }
        $legacy = self::normalizeAmount($legacyAmount);
        if ($legacy !== null) {
            $inferred = self::inferIdFromAmount($legacy);
            if ($inferred !== null) {
                return self::opcoes()[$inferred];
            }
        }
        return '—';
    }

    public static function normalizeId($id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }
        $id = (int)$id;
        return self::isValidId($id) ? $id : null;
    }

    public static function normalizeAmount($amount): ?int
    {
        if ($amount === null || $amount === '') {
            return null;
        }
        if (!is_numeric($amount)) {
            return null;
        }
        $amount = (int)$amount;
        return $amount >= 0 ? $amount : null;
    }

    public static function inferIdFromAmount($amount): ?int
    {
        $amount = self::normalizeAmount($amount);
        if ($amount === null) {
            return null;
        }
        return match (true) {
            $amount <= 100000 => 1,
            $amount <= 250000 => 2,
            $amount <= 500000 => 3,
            $amount <= 750000 => 4,
            $amount <= 1000000 => 5,
            default => 6,
        };
    }

    public static function representativeAmountForId($id): int
    {
        return match ((int)$id) {
            1 => 100000,
            2 => 250000,
            3 => 500000,
            4 => 750000,
            5 => 1000000,
            6 => 1000001,
            default => 0,
        };
    }
}
