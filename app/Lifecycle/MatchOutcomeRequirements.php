<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Data\Matches\MatchResultData;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use Illuminate\Database\Eloquent\Collection;

class MatchOutcomeRequirements
{
    public function __construct(
        private readonly MatchWinningSideRequirement $winningSide,
        private readonly MatchEntryOrderRequirement $entryOrder,
        private readonly MatchEliminationRequirement $eliminations,
    ) {}

    /**
     * @param  Collection<int, MatchCompetitor>  $competitors
     */
    public function ensureSatisfied(EventMatch $match, MatchResultData $result, Collection $competitors): void
    {
        $this->winningSide->ensureSatisfied($match, $result);
        $this->entryOrder->ensureSatisfied($match, $competitors);
        $this->eliminations->ensureSatisfied($match, $result, $competitors);
    }
}
