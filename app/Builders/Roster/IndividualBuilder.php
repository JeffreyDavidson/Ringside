<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\FiltersByEmploymentStatus;
use App\Builders\Concerns\FiltersByRetirementStatus;
use App\Builders\Concerns\LoadsFirstEmployment;
use App\Builders\Concerns\OrdersByKeyForLocking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 */
abstract class IndividualBuilder extends Builder
{
    use FiltersByEmploymentStatus;
    use FiltersByRetirementStatus;
    use LoadsFirstEmployment;
    use OrdersByKeyForLocking;

    /** Relationship existence projections read by isInjured() and isSuspended(). */
    public const array AVAILABILITY_STATE = [
        'currentInjury as availability_current_injury_exists',
        'currentSuspension as availability_current_suspension_exists',
    ];

    /** Employment and availability projections that roster status and booking checks read. */
    public const array ROSTER_STATE = [...self::EMPLOYMENT_STATUS_STATE, ...self::AVAILABILITY_STATE];

    /**
     * Restrict to individuals RosterBookingEligibility would allow: currently
     * employed, not retired, not suspended and not injured.
     */
    public function bookable(): static
    {
        return $this->whereHas('currentEmployment')
            ->whereDoesntHave('currentRetirement')
            ->whereDoesntHave('currentSuspension')
            ->whereDoesntHave('currentInjury');
    }
}
