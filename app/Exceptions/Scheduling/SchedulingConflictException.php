<?php

declare(strict_types=1);

namespace App\Exceptions\Scheduling;

use App\Exceptions\BaseBusinessException;

final class SchedulingConflictException extends BaseBusinessException
{
    public static function competitorAlreadyBooked(string $competitorType, string $competitorName): static
    {
        return new self("{$competitorType} [{$competitorName}] is already booked at this event time.");
    }

    public static function refereeAlreadyAssigned(string $refereeName): static
    {
        return new self("Referee [{$refereeName}] is already assigned to another event at this time.");
    }

    public static function titleAlreadyAssigned(string $titleName): static
    {
        return new self("Title [{$titleName}] is already assigned at this event time.");
    }

    public static function venueAlreadyBooked(string $venueName): static
    {
        return new self("Venue [{$venueName}] is already booked at this event time.");
    }
}
