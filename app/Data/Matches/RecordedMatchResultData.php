<?php

declare(strict_types=1);

namespace App\Data\Matches;

use App\Models\Matches\EventMatch;
use App\Models\Titles\Title;
use Illuminate\Support\Collection;

readonly class RecordedMatchResultData
{
    /**
     * @param  Collection<int, Title>  $titlesLeftUnchanged  Titles that did not change hands because the winner had left before the show started.
     */
    public function __construct(
        public EventMatch $match,
        public Collection $titlesLeftUnchanged,
    ) {}
}
