<?php

declare(strict_types=1);

namespace App\Support;

final class ConsecutiveIntegerSequence
{
    /**
     * @param  array<int, int|null>  $values
     */
    public function isValid(array $values): bool
    {
        if ($values === []) {
            return true;
        }

        sort($values);

        return $values === range(1, count($values));
    }
}
