<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Exceptions\Roster\TagTeams\CannotBeRetiredException;
use App\Exceptions\Roster\TagTeams\CannotBeUnretiredException;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;

final class TagTeamRetirementEligibility
{
    public function canRetire(TagTeam $tagTeam): bool
    {
        try {
            $this->ensureCanRetire($tagTeam);

            return true;
        } catch (CannotBeRetiredException) {
            return false;
        }
    }

    public function ensureCanRetire(TagTeam $tagTeam): void
    {
        if ($tagTeam->isRetired()) {
            throw CannotBeRetiredException::alreadyRetired($tagTeam);
        }

        if (! $tagTeam->isEmployed()) {
            throw CannotBeRetiredException::notEmployed($tagTeam);
        }
    }

    public function canUnretire(TagTeam $tagTeam, bool $requireAvailablePartners = true): bool
    {
        try {
            $this->ensureCanUnretire($tagTeam, $requireAvailablePartners);

            return true;
        } catch (CannotBeUnretiredException) {
            return false;
        }
    }

    public function ensureCanUnretire(TagTeam $tagTeam, bool $requireAvailablePartners = true): void
    {
        if (! $tagTeam->isRetired()) {
            throw CannotBeUnretiredException::notRetired($tagTeam);
        }

        $conflictingTeam = TagTeam::query()
            ->where('name', $tagTeam->name)
            ->whereKeyNot($tagTeam->getKey())
            ->whereHas('employments', fn (Builder $employmentQuery) => $employmentQuery->whereNull('ended_at'))
            ->first();

        if ($conflictingTeam) {
            throw CannotBeUnretiredException::nameConflict($tagTeam, $conflictingTeam->name);
        }

        if (! $requireAvailablePartners) {
            return;
        }

        $currentPartners = $tagTeam->currentWrestlers;

        if ($currentPartners->isEmpty()) {
            throw CannotBeUnretiredException::noAvailablePartners($tagTeam);
        }

        $minimumPartners = TagTeamMembershipRequirements::MINIMUM_CURRENT_WRESTLERS;

        if (! TagTeamMembershipRequirements::hasMinimumCurrentWrestlers($currentPartners)) {
            throw CannotBeUnretiredException::insufficientPartners(
                $tagTeam,
                $currentPartners->count(),
                $minimumPartners,
            );
        }

        $unavailablePartners = $currentPartners->filter(
            fn (Wrestler $wrestler): bool => $wrestler->isInjured(),
        );

        if ($unavailablePartners->isNotEmpty()) {
            throw CannotBeUnretiredException::keyPartnersUnavailable(
                $tagTeam,
                $unavailablePartners->pluck('name')->join(', '),
            );
        }
    }
}
