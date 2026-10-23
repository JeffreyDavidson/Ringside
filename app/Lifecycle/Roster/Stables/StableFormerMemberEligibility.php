<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Stables;

use App\Data\Stables\StableMembershipData;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Lifecycle\Retirement;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class StableFormerMemberEligibility
{
    /** @return Collection<int, Wrestler|TagTeam> */
    public function availableFor(Stable $stable): Collection
    {
        $members = $this->availableMembersFor($stable);

        return collect([...$members->wrestlers, ...$members->tagTeams]);
    }

    /**
     * Former members that can return to the stable, once each however often they joined and left: employed, not
     * injured (wrestlers), suspended or retired, and not a current member of another stable.
     */
    public function availableMembersFor(Stable $stable): StableMembershipData
    {
        $wrestlers = $stable->previousWrestlers()
            ->joinableToStable()
            ->get()
            ->unique('id')
            ->values();

        $tagTeams = $stable->previousTagTeams()
            ->joinableToStable()
            ->get()
            ->unique('id')
            ->values();

        return new StableMembershipData($wrestlers, $tagTeams);
    }

    /**
     * Members available to return when a retired stable is unretired: only those whose membership ended when the
     * stable retired count (earlier leavers never come back), and each must be employed, not injured, suspended or
     * retired, and not a current member of another stable. Unavailable ones are skipped.
     */
    public function availableMembersAtRetirementFor(Stable $stable): StableMembershipData
    {
        $retirement = $stable->currentRetirement()->firstOrFail();

        $membersLeftAt = $this->isFutureDated($retirement)
            ? $stable->activityPeriods()->whereNotNull('ended_at')->latest('ended_at')->firstOrFail()->ended_at
            : $retirement->started_at;

        $wrestlers = $stable->previousWrestlers()
            ->wherePivot('left_at', $membersLeftAt)
            ->joinableToStable()
            ->inLockOrder()
            ->get()
            ->unique('id')
            ->values();

        $tagTeams = $stable->previousTagTeams()
            ->wherePivot('left_at', $membersLeftAt)
            ->joinableToStable()
            ->inLockOrder()
            ->get()
            ->unique('id')
            ->values();

        return new StableMembershipData($wrestlers, $tagTeams);
    }

    /**
     * Whether the stable's operations were running when its current retirement began, so retiring is what ended them.
     *
     * Read from the order the transitions were recorded in (their ids), never from dates, because the dates of a
     * closed period can be edited. A stable that has run is active at retirement unless its last activity
     * transition was a disband; a Reunited or Established after the disband makes it active again. Nothing
     * activity-related can be recorded while the stable is retired, so the last one is the one before the
     * retirement. A stable that never ran retired with nothing to end.
     */
    public function wasActiveWhenRetired(Stable $stable): bool
    {
        if (! $stable->activityPeriods()->exists()) {
            return false;
        }

        $lastActivityTransition = $stable->lifecycleTransitions()
            ->where('dimension', LifecycleDimension::Activity)
            ->reorder('id', 'desc') // the relation is ordered by effective date; only the recording order counts here
            ->first();

        return $lastActivityTransition?->transition !== LifecycleTransitionType::Disbanded;
    }

    /**
     * A retirement dated in the future ends the stable's operations (and memberships) when it is recorded.
     */
    private function isFutureDated(Retirement $retirement): bool
    {
        return $retirement->created_at instanceof Carbon && $retirement->started_at->greaterThan($retirement->created_at);
    }
}
