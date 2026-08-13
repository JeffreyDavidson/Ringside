<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Exceptions\Roster\TagTeams\CannotBeReinstatedException;
use App\Exceptions\Roster\TagTeams\CannotBeSuspendedException;
use App\Models\TagTeams\TagTeam;

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
        if (! $tagTeam->isEmployed()) {
            throw CannotBeSuspendedException::notEmployed($tagTeam);
        }

        if ($tagTeam->isSuspended()) {
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
        if (! $tagTeam->isSuspended()) {
            throw CannotBeReinstatedException::notSuspended($tagTeam);
        }

        if (! $tagTeam->isEmployed()) {
            throw CannotBeReinstatedException::notEmployed($tagTeam);
        }
    }
}
