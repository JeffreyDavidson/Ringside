<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\FiltersByEmploymentStatus;
use App\Builders\Concerns\FiltersByName;
use App\Builders\Concerns\FiltersByNameInPromotion;
use App\Builders\Concerns\FiltersByRetirementStatus;
use App\Builders\Concerns\LoadsFirstEmployment;
use App\Builders\Concerns\OrdersByKeyForLocking;
use App\Lifecycle\Roster\TagTeams\TagTeamMembershipRequirements;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Builder;

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
            ->whereDoesntHave('currentRetirement')
            ->whereDoesntHave('currentSuspension')
            ->has('currentWrestlers', '>=', TagTeamMembershipRequirements::MINIMUM_CURRENT_WRESTLERS)
            ->whereDoesntHave(
                'currentWrestlers',
                fn (WrestlerBuilder $wrestlers): WrestlerBuilder => $wrestlers->whereNot(
                    fn (WrestlerBuilder $wrestler): WrestlerBuilder => $wrestler->bookable(),
                ),
            );
    }
}
