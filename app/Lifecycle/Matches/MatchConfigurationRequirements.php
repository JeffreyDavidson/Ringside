<?php

declare(strict_types=1);

namespace App\Lifecycle\Matches;

use App\Data\Matches\EventMatchData;
use App\Exceptions\Matches\InvalidMatchConfigurationException;

final class MatchConfigurationRequirements
{
    public function ensureComplete(EventMatchData $data): void
    {
        if ($data->sides->isEmpty()) {
            throw InvalidMatchConfigurationException::missingCompetitors();
        }

        if ($data->referees->isEmpty()) {
            throw InvalidMatchConfigurationException::missingReferees();
        }
    }
}
