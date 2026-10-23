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
     * A stable that was disbanded first, or was never active, retired with no operations to end. Retiring ends the
     * open activity period on the retirement date without a Disbanded transition, so a period that ended then and
     * was not a disband means the stable was active. For a retirement dated in the future the operations ended when
     * it was recorded, so the latest ended period is the one to check.
     */
    public function wasActiveWhenRetired(Stable $stable): bool
    {
        $retirement = $stable->currentRetirement()->firstOrFail();

        $operationsEndedAt = $this->isFutureDated($retirement)
            ? $stable->activityPeriods()->whereNotNull('ended_at')->latest('ended_at')->first()?->ended_at
            : $retirement->started_at;

        return $stable->activityPeriods()->where('ended_at', $operationsEndedAt)->exists()
            && ! $stable->lifecycleTransitions()
                ->where('dimension', LifecycleDimension::Activity)
                ->where('transition', LifecycleTransitionType::Disbanded)
                ->where('effective_at', $operationsEndedAt)
                ->exists();
    }

    /**
     * A retirement dated in the future ends the stable's operations (and memberships) when it is recorded.
     */
    private function isFutureDated(Retirement $retirement): bool
    {
        return $retirement->created_at instanceof Carbon && $retirement->started_at->greaterThan($retirement->created_at);
    }
}
