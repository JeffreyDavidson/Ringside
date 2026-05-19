<?php

declare(strict_types=1);

namespace App\Data\Titles;

use Illuminate\Support\Carbon;

/**
 * Data object for representing the longest reigning champion of a title.
 */
class LongestReigningChampionSummary
{
    public function __construct(
        public readonly string $championName,
        public readonly int $reignLengthInDays,
        public readonly Carbon $wonAt,
        public readonly ?Carbon $lostAt,
    ) {}
}
