<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Enums\MatchFinish;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchSide;

class MatchOutcomeRequirements
{
    public function ensureSatisfied(EventMatch $match, MatchFinish $finish, ?MatchSide $winningSide): void
    {
        if ($finish->requiresWinningSide() && $winningSide === null) {
            throw InvalidMatchConfigurationException::missingWinningSide();
        }

        if (! $finish->requiresWinningSide() && $winningSide !== null) {
            throw InvalidMatchConfigurationException::unexpectedWinningSide();
        }

        if ($winningSide === null) {
            return;
        }

        if ($winningSide->match_id !== $match->id) {
            throw InvalidMatchConfigurationException::winningSideFromAnotherMatch();
        }

        if (! $winningSide->competitors()->exists()) {
            throw InvalidMatchConfigurationException::winningSideWithoutCompetitors();
        }
    }
}
