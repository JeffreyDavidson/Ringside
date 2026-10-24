<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\FiltersByEmploymentStatus;
use App\Builders\Concerns\FiltersByName;
use App\Builders\Concerns\FiltersByNameInPromotion;
use App\Builders\Concerns\FiltersByRetirementStatus;
use App\Builders\Concerns\LoadsFirstEmployment;
use App\Builders\Concerns\OrdersByKeyForLocking;
use App\Builders\Lifecycle\LifecyclePeriodBuilder;
use App\Lifecycle\Roster\TagTeams\TagTeamMembershipRequirements;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * @template TModel of TagTeam
 *
 * @extends Builder<TModel>
 */
class TagTeamBuilder extends Builder
{
    use FiltersByEmploymentStatus;
    use FiltersByName;
    use FiltersByNameInPromotion;
    use FiltersByRetirementStatus;
    use LoadsFirstEmployment;
    use OrdersByKeyForLocking;

    /** Relationship existence projections read by isSuspended(). */
    public const array AVAILABILITY_STATE = ['currentSuspension as availability_current_suspension_exists'];

    /** Employment and availability projections that roster status and booking checks read. */
    public const array ROSTER_STATE = [...self::EMPLOYMENT_STATUS_STATE, ...self::AVAILABILITY_STATE];

    /**
     * Restrict to tag teams that can be added to a stable: employed, not retired or suspended, and not a current
     * member of another stable.
     */
    public function joinableToStable(): static
    {
        return $this->whereHas('currentEmployment')
            ->whereDoesntHave('currentRetirement')
            ->whereDoesntHave('currentSuspension')
            ->whereDoesntHave('currentStable');
    }

    /**
     * Restrict to tag teams RosterBookingEligibility would allow: the team is
     * currently employed, not retired and not suspended, has the minimum number
     * of current wrestlers and every current wrestler is individually bookable.
     */
    public function bookable(): static
    {
        return $this->whereHas('currentEmployment')
            ->withBookableMembers();
    }

    /**
     * Compose calendar restrictions with lifecycle eligibility for one promotion-local day. With
     * `$at` (the event's date) the team and every current wrestler must have been employed at that
     * moment; retirement, suspension and injury still count as they stand now.
     */
    public function bookableOn(?string $date, ?Carbon $at = null): static
    {
        if ($at instanceof Carbon) {
            $this->whereHas('employments', fn (Builder $employments) => LifecyclePeriodBuilder::constrainToActiveOn($employments, $at->copy()->utc()))
                ->withBookableMembers($at);
        } else {
            $this->bookable();
        }

        if ($date === null) {
            return $this;
        }

        return $this->whereDoesntHave('currentWrestlers', fn (WrestlerBuilder $members): WrestlerBuilder => $members
            ->whereHas('unavailabilities', fn (UnavailabilityBuilder $entries): UnavailabilityBuilder => $entries
                ->covering($date)->whereColumn('roster_unavailabilities.promotion_id', 'wrestlers.promotion_id')));
    }

    /**
     * The clauses bookable() and bookableOn() share after the employment check: not retired or
     * suspended, the minimum number of current wrestlers, and every current wrestler bookable
     * (employed at `$at` when given, otherwise now).
     */
    private function withBookableMembers(?Carbon $at = null): static
    {
        return $this->whereDoesntHave('currentRetirement')
            ->whereDoesntHave('currentSuspension')
            ->has('currentWrestlers', '>=', TagTeamMembershipRequirements::MINIMUM_CURRENT_WRESTLERS)
            ->whereDoesntHave(
                'currentWrestlers',
                fn (WrestlerBuilder $wrestlers): WrestlerBuilder => $wrestlers->whereNot(
                    fn (WrestlerBuilder $wrestler): WrestlerBuilder => $wrestler->bookableOn(null, $at),
                ),
            );
    }
}
