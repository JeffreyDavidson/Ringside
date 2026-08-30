<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\TagTeams;

use App\Exceptions\Roster\TagTeams\CannotBeDeletedException;
use App\Exceptions\Roster\TagTeams\CannotBeRestoredException;
use App\Models\Roster\TagTeams\TagTeam;

final class TagTeamDeletionEligibility
{
    public function canDelete(TagTeam $tagTeam): bool
    {
        try {
            $this->ensureCanDelete($tagTeam);

            return true;
        } catch (CannotBeDeletedException) {
            return false;
        }
    }

    public function ensureCanDelete(TagTeam $tagTeam): void
    {
        if ($tagTeam->trashed()) {
            throw CannotBeDeletedException::alreadyDeleted($tagTeam);
        }

        if ($tagTeam->isRetired()) {
            throw CannotBeDeletedException::stillRetired($tagTeam);
        }

        if ($tagTeam->currentEmployment()->exists()) {
            throw CannotBeDeletedException::stillEmployed($tagTeam);
        }

        if ($tagTeam->isSuspended()) {
            throw CannotBeDeletedException::stillSuspended($tagTeam);
        }
    }

    public function canRestore(TagTeam $tagTeam): bool
    {
        try {
            $this->ensureCanRestore($tagTeam);

            return true;
        } catch (CannotBeRestoredException) {
            return false;
        }
    }

    public function ensureCanRestore(TagTeam $tagTeam): void
    {
        if (! $tagTeam->trashed()) {
            throw CannotBeRestoredException::notDeleted($tagTeam);
        }

        $conflictingTeam = TagTeam::query()
            ->whereName($tagTeam->name)
            ->whereKeyNot($tagTeam->getKey())
            ->whereHas('currentEmployment')
            ->first();

        if ($conflictingTeam) {
            throw CannotBeRestoredException::nameConflict($tagTeam, $conflictingTeam->name);
        }
    }
}
