<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Stables\StableMembershipData;
use App\Models\Stables\Stable;
use Illuminate\Support\Carbon;

/**
 * Service for managing stable membership operations.
 *
 * Centralizes the business logic for adding, removing, and transferring
 * stable members (wrestlers and tag teams) with proper date tracking
 * and business rule validation.
 */
class StableMembershipService
{
    /**
     * Add members to a stable.
     *
     * @param  Stable  $stable  The stable to add members to
     * @param  StableMembershipData  $members  The members to add
     * @param  Carbon  $date  The date they joined
     */
    public function addMembers(Stable $stable, StableMembershipData $members, Carbon $date): void
    {
        // Add wrestlers
        if ($members->wrestlers?->isNotEmpty()) {
            foreach ($members->wrestlers as $wrestler) {
                $stable->wrestlers()->attach($wrestler->id, [
                    'joined_at' => $date,
                    'left_at' => null,
                ]);
            }
        }

        // Add tag teams
        if ($members->tagTeams?->isNotEmpty()) {
            foreach ($members->tagTeams as $tagTeam) {
                $stable->tagTeams()->attach($tagTeam->id, [
                    'joined_at' => $date,
                    'left_at' => null,
                ]);
            }
        }
    }

    /**
     * Remove members from a stable.
     *
     * @param  Stable  $stable  The stable to remove members from
     * @param  StableMembershipData  $members  The members to remove
     * @param  Carbon  $date  The date they left
     */
    public function removeMembers(Stable $stable, StableMembershipData $members, Carbon $date): void
    {
        // Remove wrestlers
        if ($members->wrestlers?->isNotEmpty()) {
            foreach ($members->wrestlers as $wrestler) {
                $stable->wrestlers()->updateExistingPivot($wrestler->id, [
                    'left_at' => $date,
                ]);
            }
        }

        // Remove tag teams
        if ($members->tagTeams?->isNotEmpty()) {
            foreach ($members->tagTeams as $tagTeam) {
                $stable->tagTeams()->updateExistingPivot($tagTeam->id, [
                    'left_at' => $date,
                ]);
            }
        }
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

    /**
     * Update stable membership by comparing current vs desired members.
     *
     * This is used for stable updates where we need to add new members
     * and remove former members based on collection differences.
     *
     * @param  Stable  $stable  The stable to update
     * @param  StableMembershipData  $newMembers  The desired members
     * @param  Carbon  $date  The date of the membership changes
     */
    public function updateMembership(Stable $stable, StableMembershipData $newMembers, Carbon $date): void
    {
        // Update wrestlers
        if ($newMembers->wrestlers !== null) {
            $currentWrestlers = $stable->currentWrestlers;
            $formerWrestlers = $currentWrestlers->diff($newMembers->wrestlers);
            $addedWrestlers = $newMembers->wrestlers->diff($currentWrestlers);

            if ($formerWrestlers->isNotEmpty()) {
                $this->removeMembers($stable, new StableMembershipData(wrestlers: $formerWrestlers), $date);
            }

            if ($addedWrestlers->isNotEmpty()) {
                $this->addMembers($stable, new StableMembershipData(wrestlers: $addedWrestlers), $date);
            }
        }

        // Update tag teams
        if ($newMembers->tagTeams !== null) {
            $currentTagTeams = $stable->currentTagTeams;
            $formerTagTeams = $currentTagTeams->diff($newMembers->tagTeams);
            $addedTagTeams = $newMembers->tagTeams->diff($currentTagTeams);

            if ($formerTagTeams->isNotEmpty()) {
                $this->removeMembers($stable, new StableMembershipData(tagTeams: $formerTagTeams), $date);
            }

            if ($addedTagTeams->isNotEmpty()) {
                $this->addMembers($stable, new StableMembershipData(tagTeams: $addedTagTeams), $date);
            }
        }
    }

    /**
     * End the current activity period for a stable.
     *
     * This is commonly used across multiple actions (disband, delete, retire)
     * to properly close an active stable's activity period.
     *
     * @param  Stable  $stable  The stable to end activity for
     * @param  Carbon  $endDate  The date to end the activity period
     */
    public function endActivityPeriod(Stable $stable, Carbon $endDate): void
    {
        $currentActivityPeriod = $stable->currentActivityPeriod()->first();
        if ($currentActivityPeriod) {
            $currentActivityPeriod->update(['ended_at' => $endDate]);
        }
    }

    /**
     * Retire all members of a stable who are not already retired.
     *
     * Used specifically by RetireAction to cascade retirement to stable members.
     *
     * @param  StableMembershipData  $members  The members to potentially retire
     * @param  Carbon  $retirementDate  The retirement date
     * @param  callable  $wrestlerRetireAction  Callback to retire wrestlers
     * @param  callable  $tagTeamRetireAction  Callback to retire tag teams
     */
    public function retireStableMembers(
        StableMembershipData $members,
        Carbon $retirementDate,
        callable $wrestlerRetireAction,
        callable $tagTeamRetireAction
    ): void {
        $wrestlersToRetire = $members->getWrestlersToRetire();
        $tagTeamsToRetire = $members->getTagTeamsToRetire();

        // Retire wrestlers who are not already retired
        $wrestlersToRetire?->each(fn ($wrestler) => $wrestlerRetireAction($wrestler, $retirementDate));

        // Retire tag teams who are not already retired
        $tagTeamsToRetire?->each(fn ($tagTeam) => $tagTeamRetireAction($tagTeam, $retirementDate));
    }

    /**
     * Unretire all members of a stable who are currently retired.
     *
     * Used specifically by UnretireAction to cascade unretirement to stable members.
     *
     * @param  StableMembershipData  $members  The members to potentially unretire
     * @param  Carbon  $unretirementDate  The unretirement date
     * @param  callable  $wrestlerUnretireAction  Callback to unretire wrestlers
     * @param  callable  $tagTeamUnretireAction  Callback to unretire tag teams
     */
    public function unretireStableMembers(
        StableMembershipData $members,
        Carbon $unretirementDate,
        callable $wrestlerUnretireAction,
        callable $tagTeamUnretireAction
    ): void {
        $wrestlersToUnretire = $members->getWrestlersToUnretire();
        $tagTeamsToUnretire = $members->getTagTeamsToUnretire();

        // Unretire wrestlers who are currently retired
        $wrestlersToUnretire?->each(fn ($wrestler) => $wrestlerUnretireAction($wrestler, $unretirementDate));

        // Unretire tag teams who are currently retired
        $tagTeamsToUnretire?->each(fn ($tagTeam) => $tagTeamUnretireAction($tagTeam, $unretirementDate));
    }
}
