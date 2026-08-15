<?php

declare(strict_types=1);

namespace App\Data\Matches;

use App\Enums\MatchFinish;
use App\Models\Matches\MatchSide;
use Illuminate\Support\Collection;

readonly class MatchResultData
{
    /**
     * @param  Collection<int, MatchEliminationData>  $eliminations
     */
    public function __construct(
        public MatchFinish $finish,
        public ?MatchSide $winningSide,
        public Collection $eliminations,
    ) {}
}
