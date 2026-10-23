<?php

declare(strict_types=1);

namespace App\Exceptions\Scheduling;

use App\Enums\Matches\MatchParticipantKind;
use App\Exceptions\BaseBusinessException;

final class EntityNotAvailableException extends BaseBusinessException
{
    public static function forMatchAssignment(MatchParticipantKind $kind): static
    {
        return new self(__('matches.errors.scheduling.not_available_for_match', ['entity_type' => $kind->pluralLabel()]));
    }

    public static function onDate(string $name, string $date): static
    {
        return new self(__('availability.unavailable', ['name' => $name, 'date' => $date]));
    }
}
