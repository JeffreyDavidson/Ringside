<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\TagTeams;

use App\Exceptions\Roster\TagTeams\CannotBeEmployedException;
use App\Exceptions\Roster\TagTeams\CannotBeReleasedException;
use App\Lifecycle\Concerns\AnswersEligibility;
use App\Models\Roster\TagTeams\TagTeam;

final class TagTeamEmploymentEligibility
{
    use AnswersEligibility;

    public function canEmploy(TagTeam $tagTeam): bool
    {
        return $this->passes(fn () => $this->ensureCanEmploy($tagTeam), CannotBeEmployedException::class);
    }

    public function ensureCanEmploy(TagTeam $tagTeam): void
    {
        if ($tagTeam->hasCurrentEmployment()) {
            throw CannotBeEmployedException::alreadyEmployed($tagTeam);
        }

        if ($tagTeam->hasFutureEmployment()) {
            throw CannotBeEmployedException::hasFutureEmployment($tagTeam);
        }

        if ($tagTeam->hasCurrentRetirement()) {
            throw CannotBeEmployedException::retired($tagTeam);
        }

        if ($tagTeam->currentWrestlers->isEmpty()) {
            throw CannotBeEmployedException::partnersUnavailable($tagTeam, __('tag-team-lifecycle.errors.employ.no_current_partners'));
        }
    }

    public function canRelease(TagTeam $tagTeam): bool
    {
        return $this->passes(fn () => $this->ensureCanRelease($tagTeam), CannotBeReleasedException::class);
    }

    public function ensureCanRelease(TagTeam $tagTeam): void
    {
        if (! $tagTeam->hasCurrentEmployment()) {
            throw CannotBeReleasedException::notEmployed($tagTeam);
        }
    }
}
