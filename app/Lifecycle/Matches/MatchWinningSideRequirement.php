<?php

declare(strict_types=1);

namespace App\Lifecycle\Matches;

use App\Collections\MatchCompetitorsCollection;
use App\Data\Matches\MatchResultData;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;

final class MatchWinningSideRequirement
{
    /**
     * @param  MatchCompetitorsCollection<int, MatchCompetitor>  $competitors
     */
    public function ensureSatisfied(EventMatch $match, MatchResultData $result, MatchCompetitorsCollection $competitors): void
    {
        if ($result->finish->requiresWinningSide() && ! $result->winningSide instanceof MatchSide) {
            throw InvalidMatchOutcomeException::missingWinningSide();
        }

        if (! $result->finish->requiresWinningSide() && $result->winningSide instanceof MatchSide) {
            throw InvalidMatchOutcomeException::unexpectedWinningSide();
        }

        if (! $result->winningSide instanceof MatchSide) {
            return;
        }

        if ($result->winningSide->match_id !== $match->id) {
            throw InvalidMatchOutcomeException::winningSideFromAnotherMatch();
        }

        if ($competitors->where('match_side_id', $result->winningSide->id)->isEmpty()) {
            throw InvalidMatchOutcomeException::winningSideWithoutCompetitors();
        }
    }
}
