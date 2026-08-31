<?php

declare(strict_types=1);

namespace App\Lifecycle\Matches;

use App\Collections\MatchCompetitorsCollection;
use App\Data\Matches\MatchResultData;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;

final class MatchOutcomeRequirements
{
    public function __construct(
        private readonly MatchWinningSideRequirement $winningSide,
        private readonly MatchEntryOrderRequirement $entryOrder,
        private readonly MatchEliminationRequirement $eliminations,
    ) {}

    /**
     * @param  MatchCompetitorsCollection<int, MatchCompetitor>  $competitors
     */
    public function ensureSatisfied(EventMatch $match, MatchResultData $result, MatchCompetitorsCollection $competitors): void
    {
        $this->winningSide->ensureSatisfied($match, $result, $competitors);
        $this->entryOrder->ensureSatisfied($match, $competitors);
        $this->eliminations->ensureSatisfied($match, $result, $competitors);
    }
}
