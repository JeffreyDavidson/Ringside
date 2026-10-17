<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

/** Exact USD input and display; persisted financial amounts are integer cents. */
final class UsdAmount
{
    public const int MAX_CENTS = 999999999;

    public static function parse(string $amount): int
    {
        if (! preg_match('/\A[0-9]{1,7}(?:\.[0-9]{1,2})?\z/', $amount)) {
            throw new InvalidArgumentException('Enter USD with at most two decimal places.');
        }
        $parts = explode('.', $amount);

        return ((int) $parts[0] * 100) + (int) str_pad($parts[1] ?? '', 2, '0');
    }

    public static function decimal(int $cents): string
    {
        return ($cents < 0 ? '-' : '').intdiv(abs($cents), 100).'.'.str_pad((string) (abs($cents) % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function display(?int $cents): string
    {
        if ($cents === null) {
            return __('event-budget.unknown');
        }

        return self::decimal($cents).' USD';
    }
}
