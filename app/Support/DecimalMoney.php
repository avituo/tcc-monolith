<?php

namespace App\Support;

final class DecimalMoney
{
    public static function subtract(string $amount, string $discount): string
    {
        $result = max(self::toCents($amount) - self::toCents($discount), 0);

        return sprintf('%d.%02d', intdiv($result, 100), $result % 100);
    }

    private static function toCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }
}
