<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Exceptions\Roster\Individuals\CannotBeDeletedException;
use App\Exceptions\Roster\Individuals\CannotBeRestoredException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class IndividualDeletionEligibility
{
    public function canDelete(Wrestler|Manager|Referee $individual): bool
    {
        try {
            $this->ensureCanDelete($individual);

            return true;
        } catch (CannotBeDeletedException) {
            return false;
        }
    }

    public function ensureCanDelete(Wrestler|Manager|Referee $individual): void
    {
        if (! $individual->exists || $individual->trashed()) {
            throw CannotBeDeletedException::alreadyDeleted($individual);
        }
    }

    public function canRestore(Wrestler|Manager|Referee $individual): bool
    {
        try {
            $this->ensureCanRestore($individual);

            return true;
        } catch (CannotBeRestoredException) {
            return false;
        }
    }

    public function ensureCanRestore(Wrestler|Manager|Referee $individual): void
    {
        if (! $individual->exists || ! $individual->trashed()) {
            throw CannotBeRestoredException::notDeleted($individual);
        }
    }
}
