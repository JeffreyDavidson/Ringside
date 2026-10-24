<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Individuals;

use App\Exceptions\Roster\Individuals\CannotBeDeletedException;
use App\Exceptions\Roster\Individuals\CannotBeRestoredException;
use App\Lifecycle\Concerns\AnswersEligibility;
use App\Lifecycle\Roster\UpcomingBookings;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final readonly class IndividualDeletionEligibility
{
    use AnswersEligibility;

    public function __construct(private UpcomingBookings $upcomingBookings) {}

    public function ensureCanDelete(Wrestler|Manager|Referee $individual): void
    {
        if (! $individual->exists || $individual->trashed()) {
            throw CannotBeDeletedException::alreadyDeleted($individual);
        }

        if (! $individual instanceof Manager && $this->upcomingBookings->exist($individual)) {
            throw CannotBeDeletedException::bookedInUpcomingMatch($individual);
        }
    }

    public function canRestore(Wrestler|Manager|Referee $individual): bool
    {
        return $this->passes(fn () => $this->ensureCanRestore($individual), CannotBeRestoredException::class);
    }

    public function ensureCanRestore(Wrestler|Manager|Referee $individual): void
    {
        if (! $individual->exists || ! $individual->trashed()) {
            throw CannotBeRestoredException::notDeleted($individual);
        }
    }
}
