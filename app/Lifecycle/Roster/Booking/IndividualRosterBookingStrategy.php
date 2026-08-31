<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Booking;

use App\Enums\Shared\EmploymentStatus;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class IndividualRosterBookingStrategy implements RosterBookingStrategy
{
    public function __construct(private readonly Wrestler|Referee $individual) {}

    public function allows(): bool
    {
        return $this->individual->status === EmploymentStatus::Employed
            && ! $this->individual->currentSuspension()->exists()
            && ! $this->individual->currentInjury()->exists();
    }
}
