<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Individuals;

use App\Exceptions\Roster\Individuals\CannotBeDeletedException;
use App\Exceptions\Roster\Individuals\CannotBeRestoredException;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class IndividualDeletionEligibility
{
    public function ensureCanDelete(Wrestler|Manager|Referee $individual): void
    {
        if (! $individual->exists || $individual->trashed()) {
            throw CannotBeDeletedException::alreadyDeleted($individual);
        }

        if ($individual instanceof Wrestler && $this->isBookedInLiveMatch($individual)) {
            throw CannotBeDeletedException::bookedInUpcomingMatch($individual);
        }
    }

    private function isBookedInLiveMatch(Wrestler $wrestler): bool
    {
        return EventMatch::query()
            ->withoutGlobalScope('promotion_context')
            ->forWrestlerId($wrestler->id)
            ->upcomingOrUnresulted()
            ->exists();
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
