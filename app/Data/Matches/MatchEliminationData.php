<?php

declare(strict_types=1);

namespace App\Data\Matches;

use App\Models\Matches\MatchCompetitor;

readonly class MatchEliminationData
{
    public function __construct(
        public MatchCompetitor $competitor,
        public int $order,
        public ?MatchCompetitor $eliminatedBy = null,
    ) {}
}
