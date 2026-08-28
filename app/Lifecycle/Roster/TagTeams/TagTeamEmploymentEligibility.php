<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\TagTeams;

use App\Exceptions\Roster\TagTeams\CannotBeEmployedException;
use App\Exceptions\Roster\TagTeams\CannotBeReleasedException;
use App\Models\Roster\TagTeams\TagTeam;

final class TagTeamEmploymentEligibility
{
    public function canEmploy(TagTeam $tagTeam): bool
    {
        try {
            $this->ensureCanEmploy($tagTeam);

            return true;
        } catch (CannotBeEmployedException) {
            return false;
        }
    }

    public function ensureCanEmploy(TagTeam $tagTeam): void
    {
        if ($tagTeam->isEmployed()) {
            throw CannotBeEmployedException::alreadyEmployed($tagTeam);
        }

        if ($tagTeam->hasFutureEmployment()) {
            throw CannotBeEmployedException::hasFutureEmployment($tagTeam);
        }

        if ($tagTeam->isRetired()) {
            throw CannotBeEmployedException::retired($tagTeam);
        }

        if ($tagTeam->currentWrestlers->isEmpty()) {
            throw CannotBeEmployedException::partnersUnavailable($tagTeam, 'No current partners available');
        }
    }

    public function canRelease(TagTeam $tagTeam): bool
    {
        try {
            $this->ensureCanRelease($tagTeam);

            return true;
        } catch (CannotBeReleasedException) {
            return false;
        }
    }

    public function ensureCanRelease(TagTeam $tagTeam): void
    {
        if (! $tagTeam->isEmployed()) {
            throw CannotBeReleasedException::notEmployed($tagTeam);
        }
    }
}
