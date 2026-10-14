<?php

declare(strict_types=1);

namespace App\Lifecycle\Tournaments;

final class BracketLayout
{
    /** @return list<int> */
    public function seeds(int $capacity): array
    {
        $seeds = [1, 2];
        for ($size = 4; $size <= $capacity; $size *= 2) {
            $seeds = array_merge(...array_map(fn (int $seed): array => [$seed, $size + 1 - $seed], $seeds));
        }

        return $seeds;
    }
}
