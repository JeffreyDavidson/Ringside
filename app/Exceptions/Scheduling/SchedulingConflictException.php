<?php

declare(strict_types=1);

namespace App\Exceptions\Scheduling;

use App\Exceptions\BaseBusinessException;

final class SchedulingConflictException extends BaseBusinessException
{
    public static function competitorAlreadyBooked(string $competitorType, string $competitorName): static
    {
        return new self(__('matches.errors.scheduling.competitor_already_booked', ['type' => $competitorType, 'name' => $competitorName]));
    }

    public static function refereeAlreadyAssigned(string $refereeName): static
    {
        return new self(__('matches.errors.scheduling.referee_already_assigned', ['name' => $refereeName]));
    }

    public static function titleAlreadyAssigned(string $titleName): static
    {
        return new self(__('matches.errors.scheduling.title_already_assigned', ['name' => $titleName]));
    }

    public static function venueAlreadyBooked(string $venueName, string $venueLocalDate): static
    {
        return new self(__('matches.errors.scheduling.venue_already_booked', ['name' => $venueName, 'date' => $venueLocalDate]));
    }
}
