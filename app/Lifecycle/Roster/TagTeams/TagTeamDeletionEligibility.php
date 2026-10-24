<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\TagTeams;

use App\Exceptions\Roster\TagTeams\CannotBeDeletedException;
use App\Exceptions\Roster\TagTeams\CannotBeRestoredException;
use App\Lifecycle\Concerns\AnswersEligibility;
use App\Lifecycle\Roster\UpcomingBookings;
use App\Models\Roster\TagTeams\TagTeam;

final readonly class TagTeamDeletionEligibility
{
    use AnswersEligibility;

    public function __construct(private UpcomingBookings $upcomingBookings) {}

    public function canDelete(TagTeam $tagTeam): bool
    {
        return $this->passes(fn () => $this->ensureCanDelete($tagTeam), CannotBeDeletedException::class);
    }

    public function ensureCanDelete(TagTeam $tagTeam): void
    {
        if ($tagTeam->trashed()) {
            throw CannotBeDeletedException::alreadyDeleted($tagTeam);
        }

        if ($tagTeam->currentRetirement()->exists()) {
            throw CannotBeDeletedException::stillRetired($tagTeam);
        }

        if ($tagTeam->currentEmployment()->exists()) {
            throw CannotBeDeletedException::stillEmployed($tagTeam);
        }

        if ($this->upcomingBookings->exist($tagTeam)) {
            throw CannotBeDeletedException::bookedInUpcomingMatch($tagTeam);
        }
    }

    public function canRestore(TagTeam $tagTeam): bool
    {
        return $this->passes(fn () => $this->ensureCanRestore($tagTeam), CannotBeRestoredException::class);
    }

    public function ensureCanRestore(TagTeam $tagTeam): void
    {
        if (! $tagTeam->trashed()) {
            throw CannotBeRestoredException::notDeleted($tagTeam);
        }

        $conflictingTeam = TagTeam::query()
            ->whereNameConflictsWith($tagTeam)
            ->whereHas('currentEmployment')
            ->first();

        if ($conflictingTeam) {
            throw CannotBeRestoredException::nameConflict($tagTeam, $conflictingTeam->name);
        }
    }
}
