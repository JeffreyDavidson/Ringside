<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\FiltersByEmploymentStatus;
use App\Builders\Concerns\FiltersByRetirementStatus;
use App\Builders\Concerns\LoadsFirstEmployment;
use App\Builders\Concerns\OrdersByKeyForLocking;
use App\Builders\Lifecycle\LifecyclePeriodBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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
            ->withoutBlockingState();
    }

    /**
     * Compose calendar restrictions with lifecycle eligibility for one promotion-local day. With
     * `$at` (the event's date) employment is read at that moment instead of now; retirement,
     * suspension and injury still count as they stand now.
     */
    public function bookableOn(?string $date, ?Carbon $at = null): static
    {
        if ($at instanceof Carbon) {
            $this->whereHas('employments', fn (Builder $employments) => LifecyclePeriodBuilder::constrainToActiveOn($employments, $at->copy()->utc()))
                ->withoutBlockingState();
        } else {
            $this->bookable();
        }

        if ($date === null) {
            return $this;
        }

        return $this->whereDoesntHave('unavailabilities', function (Builder $entries) use ($date): void {
            UnavailabilityBuilder::constrainToCovering($entries, $date);
            $entries->whereColumn('roster_unavailabilities.promotion_id', $this->qualifyColumn('promotion_id'));
        });
    }

    /** The clauses bookable() and bookableOn() share: not retired, suspended or injured as things stand now. */
    private function withoutBlockingState(): static
    {
        return $this->whereDoesntHave('currentRetirement')
            ->whereDoesntHave('currentSuspension')
            ->whereDoesntHave('currentInjury');
    }
}
