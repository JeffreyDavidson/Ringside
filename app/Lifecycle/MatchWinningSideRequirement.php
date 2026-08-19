<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Data\Matches\MatchResultData;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use Illuminate\Database\Eloquent\Collection;

final class MatchWinningSideRequirement
{
    /**
     * @param  Collection<int, MatchCompetitor>  $competitors
     */
    public function ensureSatisfied(EventMatch $match, MatchResultData $result, Collection $competitors): void
    {
        if ($result->finish->requiresWinningSide() && $result->winningSide === null) {
            throw InvalidMatchOutcomeException::missingWinningSide();
        }

        if (! $result->finish->requiresWinningSide() && $result->winningSide !== null) {
            throw InvalidMatchOutcomeException::unexpectedWinningSide();
        }

        if ($result->winningSide === null) {
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
