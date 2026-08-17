<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Stables\StableMembershipData;
use App\Models\Roster\Stables\Stable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class StableMembershipService
{
    public function currentMembers(Stable $stable): StableMembershipData
    {
        return new StableMembershipData(
            wrestlers: $stable->currentWrestlers,
            tagTeams: $stable->currentTagTeams,
        );
    }

    public function addMembers(Stable $stable, StableMembershipData $members, Carbon $date): void
    {
        $this->addMembersToRelationship($stable->wrestlers(), $members->wrestlers, $date);
        $this->addMembersToRelationship($stable->tagTeams(), $members->tagTeams, $date);
    }

    public function removeMembers(Stable $stable, StableMembershipData $members, Carbon $date): void
    {
        $this->removeMembersFromRelationship($stable->wrestlers(), $members->wrestlers, $date);
        $this->removeMembersFromRelationship($stable->tagTeams(), $members->tagTeams, $date);
    }

    public function updateMembership(Stable $stable, StableMembershipData $newMembers, Carbon $date): void
    {
        $this->synchronizeRelationship(
            $stable->wrestlers(),
            $stable->currentWrestlers,
            $newMembers->wrestlers,
            $date,
        );
        $this->synchronizeRelationship(
            $stable->tagTeams(),
            $stable->currentTagTeams,
            $newMembers->tagTeams,
            $date,
        );
    }

    /**
     * @template TRelatedModel of Model
     * @template TPivotModel of Pivot
     *
     * @param  BelongsToMany<TRelatedModel, Stable, TPivotModel>  $relationship
     * @param  Collection<int, TRelatedModel>|null  $members
     */
    private function addMembersToRelationship(BelongsToMany $relationship, ?Collection $members, Carbon $date): void
    {
        if ($members === null || $members->isEmpty()) {
            return;
        }

        $relationship->attach($members->map(
            fn (Model $member): int|string => $member->getKey(),
        )->all(), [
            'joined_at' => $date,
            'left_at' => null,
        ]);
    }

    /**
     * @template TRelatedModel of Model
     * @template TPivotModel of Pivot
     *
     * @param  BelongsToMany<TRelatedModel, Stable, TPivotModel>  $relationship
     * @param  Collection<int, TRelatedModel>|null  $members
     */
    private function removeMembersFromRelationship(BelongsToMany $relationship, ?Collection $members, Carbon $date): void
    {
        if ($members === null || $members->isEmpty()) {
            return;
        }

        foreach ($members as $member) {
            $relationship->newPivotStatementForId($member->getKey())
                ->whereNull('left_at')
                ->update(['left_at' => $date]);
        }
    }

    /**
     * @template TRelatedModel of Model
     * @template TPivotModel of Pivot
     *
     * @param  BelongsToMany<TRelatedModel, Stable, TPivotModel>  $relationship
     * @param  Collection<int, TRelatedModel>  $currentMembers
     * @param  Collection<int, TRelatedModel>|null  $desiredMembers
     */
    private function synchronizeRelationship(
        BelongsToMany $relationship,
        Collection $currentMembers,
        ?Collection $desiredMembers,
        Carbon $date,
    ): void {
        if ($desiredMembers === null) {
            return;
        }

        $this->removeMembersFromRelationship($relationship, $currentMembers->diff($desiredMembers), $date);
        $this->addMembersToRelationship($relationship, $desiredMembers->diff($currentMembers), $date);
    }
}
