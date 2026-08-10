<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Stables\StableMembershipData;
use App\Models\Stables\Stable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

class StableMembershipService
{
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

    /**
     * Transfer specific members from one stable to another.
     *
     * This handles the complete transfer process: removing members from the
     * source stable and adding them to the destination stable on the same date.
     *
     * @param  Stable  $fromStable  The stable to transfer members from
     * @param  Stable  $toStable  The stable to transfer members to
     * @param  StableMembershipData  $members  The members to transfer
     * @param  Carbon  $date  The date of the transfer
     */
    public function transferMembers(Stable $fromStable, Stable $toStable, StableMembershipData $members, Carbon $date): void
    {
        $this->removeMembers($fromStable, $members, $date);
        $this->addMembers($toStable, $members, $date);
    }

    /**
     * Transfer all members from one stable to another.
     *
     * This is typically used for stable merges where all members of one
     * stable are moved to another stable.
     *
     * @param  Stable  $fromStable  The stable to transfer all members from
     * @param  Stable  $toStable  The stable to transfer all members to
     * @param  Carbon  $date  The date of the transfer
     */
    public function transferAllMembers(Stable $fromStable, Stable $toStable, Carbon $date): void
    {
        $allMembers = new StableMembershipData(
            wrestlers: $fromStable->currentWrestlers,
            tagTeams: $fromStable->currentTagTeams
        );

        $this->transferMembers($fromStable, $toStable, $allMembers, $date);
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

        $relationship->attach($members->modelKeys(), [
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
            $relationship->updateExistingPivot($member->getKey(), [
                'left_at' => $date,
            ]);
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
