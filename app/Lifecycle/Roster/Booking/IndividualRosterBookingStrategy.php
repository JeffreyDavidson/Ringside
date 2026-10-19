<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Booking;

use App\Builders\Lifecycle\LifecyclePeriodBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

final readonly class IndividualRosterBookingStrategy implements RosterBookingStrategy
{
    public function __construct(private Wrestler|Referee $individual) {}

    public function allows(?Carbon $on = null): bool
    {
        if ($this->individual->isSuspended() || $this->individual->isInjured()) {
            return false;
        }

        if (! $on instanceof Carbon) {
            return $this->individual->status === EmploymentStatus::Employed;
        }

        return $this->individual->status !== EmploymentStatus::Retired
            && $this->wasEmployedOn($on);
    }

    private function wasEmployedOn(Carbon $on): bool
    {
        $employments = $this->individual->employments()->getQuery();
        LifecyclePeriodBuilder::constrainToActiveOn($employments, $on->copy()->utc());

        return $employments->exists();
    }
}
