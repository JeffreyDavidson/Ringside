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

        if (! $individual instanceof Manager && $this->isBookedInLiveMatch($individual)) {
            throw CannotBeDeletedException::bookedInUpcomingMatch($individual);
        }
    }

    /** Wrestlers are booked as competitors and referees through the match referee assignments. */
    private function isBookedInLiveMatch(Wrestler|Referee $individual): bool
    {
        $matches = EventMatch::query()->withoutGlobalScope('promotion_context');

        $bookedMatches = $individual instanceof Wrestler
            ? $matches->forWrestlerId($individual->id)
            : $matches->forRefereeId($individual->id);

        return $bookedMatches
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
