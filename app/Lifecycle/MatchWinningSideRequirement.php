<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Data\Matches\MatchResultData;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Models\Matches\EventMatch;

final class MatchWinningSideRequirement
{
    public function ensureSatisfied(EventMatch $match, MatchResultData $result): void
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

        if (! $result->winningSide->competitors()->exists()) {
            throw InvalidMatchOutcomeException::winningSideWithoutCompetitors();
        }
    }
}
