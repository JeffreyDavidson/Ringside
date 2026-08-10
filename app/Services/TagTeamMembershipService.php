<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\TagTeams\TagTeamMembershipData;
use App\Models\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

class TagTeamMembershipService
{
    public function establishMembership(TagTeam $tagTeam, TagTeamMembershipData $members, Carbon $date): void
    {
        $this->addMembersToRelationship(
            $tagTeam->wrestlers(),
            $members->wrestlers,
            $date,
            'joined_at',
            'left_at',
        );
        $this->addMembersToRelationship(
            $tagTeam->managers(),
            $members->managers,
            $date,
            'hired_at',
            'fired_at',
        );
    }

    public function updateMembership(TagTeam $tagTeam, TagTeamMembershipData $members, Carbon $date): void
    {
        $this->synchronizeRelationship(
            $tagTeam->wrestlers(),
            $tagTeam->currentWrestlers,
            $members->wrestlers,
            $date,
            'joined_at',
            'left_at',
        );
        $this->synchronizeRelationship(
            $tagTeam->managers(),
            $tagTeam->currentManagers,
            $members->managers,
            $date,
            'hired_at',
            'fired_at',
        );
    }

    /**
     * @template TRelatedModel of Model
     * @template TPivotModel of Pivot
     *
     * @param  BelongsToMany<TRelatedModel, TagTeam, TPivotModel>  $relationship
     * @param  Collection<int, TRelatedModel>|null  $members
     */
    private function addMembersToRelationship(
        BelongsToMany $relationship,
        ?Collection $members,
        Carbon $date,
        string $startedAtColumn,
        string $endedAtColumn,
    ): void {
        if ($members === null || $members->isEmpty()) {
            return;
        }

        $relationship->attach($members->modelKeys(), [
            $startedAtColumn => $date,
            $endedAtColumn => null,
        ]);
    }

    /**
     * @template TRelatedModel of Model
     * @template TPivotModel of Pivot
     *
     * @param  BelongsToMany<TRelatedModel, TagTeam, TPivotModel>  $relationship
     * @param  Collection<int, TRelatedModel>  $currentMembers
     * @param  Collection<int, TRelatedModel>|null  $desiredMembers
     */
    private function synchronizeRelationship(
        BelongsToMany $relationship,
        Collection $currentMembers,
        ?Collection $desiredMembers,
        Carbon $date,
        string $startedAtColumn,
        string $endedAtColumn,
    ): void {
        if ($desiredMembers === null) {
            return;
        }

        foreach ($currentMembers->diff($desiredMembers) as $member) {
            $relationship->updateExistingPivot($member->getKey(), [
                $endedAtColumn => $date,
            ]);
        }

        $this->addMembersToRelationship(
            $relationship,
            $desiredMembers->diff($currentMembers),
            $date,
            $startedAtColumn,
            $endedAtColumn,
        );
    }
}
