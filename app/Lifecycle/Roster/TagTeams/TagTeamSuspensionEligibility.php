<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\TagTeams;

use App\Exceptions\Roster\TagTeams\CannotBeReinstatedException;
use App\Exceptions\Roster\TagTeams\CannotBeSuspendedException;
use App\Models\Roster\TagTeams\TagTeam;

final class TagTeamSuspensionEligibility
{
    public function canSuspend(TagTeam $tagTeam): bool
    {
        try {
            $this->ensureCanSuspend($tagTeam);

            return true;
        } catch (CannotBeSuspendedException) {
            return false;
        }
    }

    public function ensureCanSuspend(TagTeam $tagTeam): void
    {
        if (! $tagTeam->currentEmployment()->exists()) {
            throw CannotBeSuspendedException::notEmployed($tagTeam);
        }

        if ($tagTeam->currentSuspension()->exists()) {
            throw CannotBeSuspendedException::alreadySuspended($tagTeam);
        }
    }

    public function canReinstate(TagTeam $tagTeam): bool
    {
        try {
            $this->ensureCanReinstate($tagTeam);

            return true;
        } catch (CannotBeReinstatedException) {
            return false;
        }
    }

    public function ensureCanReinstate(TagTeam $tagTeam): void
    {
        if (! $tagTeam->currentSuspension()->exists()) {
            throw CannotBeReinstatedException::notSuspended($tagTeam);
        }

        if (! $tagTeam->currentEmployment()->exists()) {
            throw CannotBeReinstatedException::notEmployed($tagTeam);
        }
    }
}
