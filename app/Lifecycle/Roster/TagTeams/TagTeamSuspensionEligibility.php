<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\TagTeams;

use App\Exceptions\Roster\TagTeams\CannotBeReinstatedException;
use App\Exceptions\Roster\TagTeams\CannotBeSuspendedException;
use App\Lifecycle\Concerns\AnswersEligibility;
use App\Models\Roster\TagTeams\TagTeam;

final class TagTeamSuspensionEligibility
{
    use AnswersEligibility;

    public function canSuspend(TagTeam $tagTeam): bool
    {
        return $this->passes(fn () => $this->ensureCanSuspend($tagTeam), CannotBeSuspendedException::class);
    }

    public function ensureCanSuspend(TagTeam $tagTeam): void
    {
        if (! $tagTeam->hasCurrentEmployment()) {
            throw CannotBeSuspendedException::notEmployed($tagTeam);
        }

        if ($tagTeam->isSuspended()) {
            throw CannotBeSuspendedException::alreadySuspended($tagTeam);
        }
    }

    public function canReinstate(TagTeam $tagTeam): bool
    {
        return $this->passes(fn () => $this->ensureCanReinstate($tagTeam), CannotBeReinstatedException::class);
    }

    public function ensureCanReinstate(TagTeam $tagTeam): void
    {
        if (! $tagTeam->isSuspended()) {
            throw CannotBeReinstatedException::notSuspended($tagTeam);
        }
    }
}
