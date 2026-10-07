<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\FiltersByInactiveActivity;
use App\Builders\Concerns\FiltersByName;
use App\Builders\Concerns\FiltersByNameInPromotion;
use App\Builders\Concerns\FiltersByRetirementStatus;
use App\Builders\Concerns\LoadsFirstActivityPeriod;
use App\Builders\Concerns\ProjectsActivityStatus;
use App\Enums\Stables\StableStatus;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableTagTeam;
use App\Models\Roster\Stables\StableWrestler;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Stable
 *
 * @extends Builder<TModel>
 */
class StableBuilder extends Builder
{
    use FiltersByInactiveActivity;
    use FiltersByName;
    use FiltersByNameInPromotion;
    use FiltersByRetirementStatus;
    use LoadsFirstActivityPeriod;
    use ProjectsActivityStatus;

    public function whereStatus(StableStatus $status): static
    {
        return match ($status) {
            StableStatus::Unformed => $this->unestablished(),
            StableStatus::PendingEstablishment => $this->withFutureEstablishment(),
            StableStatus::Active => $this->established(),
            StableStatus::Inactive => $this->disbanded(),
            StableStatus::Retired => $this->retired(),
        };
    }

    /**
     * Restrict to the other active, unretired stables of the stable's promotion that it could merge with.
     */
    public function mergeCandidatesFor(Stable $stable): static
    {
        return $this->where('promotion_id', $stable->promotion_id)
            ->whereKeyNot($stable->getKey())
            ->established()
            ->whereDoesntHave('currentRetirement');
    }

    public function previousForTagTeamId(int $tagTeamId): static
    {
        return $this->previousForMember(new StableTagTeam, 'tag_team_id', $tagTeamId);
    }

    public function previousForWrestlerId(int $wrestlerId): static
    {
        return $this->previousForMember(new StableWrestler, 'wrestler_id', $wrestlerId);
    }

    /**
     * Apply the shared historical-membership projection for a stable member.
     */
    private function previousForMember(
        StableTagTeam|StableWrestler $membership,
        string $memberColumn,
        int $memberId,
    ): static {
        $this->join($membership->getTable(), 'stables.id', '=', $membership->qualifyColumn('stable_id'))
            ->where($membership->qualifyColumn($memberColumn), $memberId)
            ->whereNotNull($membership->qualifyColumn('left_at'))
            ->select(
                'stables.*',
                $membership->qualifyColumn('joined_at').' as joined_at',
                $membership->qualifyColumn('left_at').' as left_at',
            )
            ->orderByDesc($membership->qualifyColumn('joined_at'));

        return $this;
    }

    public function unestablished(): static
    {
        return $this->whereDoesntHave('activityPeriods');
    }

    public function established(): static
    {
        return $this->whereHas('currentActivityPeriod');
    }

    public function disbanded(): static
    {
        return $this->whereInactiveActivity();
    }

    public function withFutureEstablishment(): static
    {
        return $this->whereHas('futureActivityPeriod');
    }
}
