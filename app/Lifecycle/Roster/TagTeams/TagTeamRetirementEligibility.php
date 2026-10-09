<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\TagTeams;

use App\Exceptions\Roster\TagTeams\CannotBeRetiredException;
use App\Exceptions\Roster\TagTeams\CannotBeUnretiredException;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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
        if ($tagTeam->hasCurrentRetirement()) {
            throw CannotBeRetiredException::alreadyRetired($tagTeam);
        }

        if (! $tagTeam->hasCurrentEmployment()) {
            throw CannotBeRetiredException::notEmployed($tagTeam);
        }
    }

    public function canUnretire(TagTeam $tagTeam): bool
    {
        try {
            $this->ensureCanUnretire($tagTeam);

            return true;
        } catch (CannotBeUnretiredException) {
            return false;
        }
    }

    public function ensureCanUnretire(TagTeam $tagTeam): void
    {
        if (! $tagTeam->hasCurrentRetirement()) {
            throw CannotBeUnretiredException::notRetired($tagTeam);
        }

        $conflictingTeam = TagTeam::query()
            ->whereNameConflictsWith($tagTeam)
            ->whereHas('currentEmployment')
            ->first();

        if ($conflictingTeam) {
            throw CannotBeUnretiredException::nameConflict($tagTeam, $conflictingTeam->name);
        }

        $partners = $this->membersAtRetirement($tagTeam);

        if ($partners->isEmpty()) {
            throw CannotBeUnretiredException::noAvailablePartners($tagTeam);
        }

        $minimumPartners = TagTeamMembershipRequirements::MINIMUM_CURRENT_WRESTLERS;

        if (! TagTeamMembershipRequirements::hasMinimumCurrentWrestlers($partners)) {
            throw CannotBeUnretiredException::insufficientPartners(
                $tagTeam,
                $partners->count(),
                $minimumPartners,
            );
        }

        foreach ($partners as $partner) {
            if ($partner->trashed()) {
                throw CannotBeUnretiredException::partnerDeleted($tagTeam, $partner->name);
            }

            $otherMembership = TagTeamWrestler::query()
                ->current()
                ->forWrestlerId($partner->id)
                ->excludingTagTeamId($tagTeam->id)
                ->first();

            if ($otherMembership) {
                throw CannotBeUnretiredException::partnerOnAnotherTagTeam(
                    $tagTeam,
                    $partner->name,
                    TagTeam::query()->withTrashed()->findOrFail($otherMembership->tag_team_id)->name,
                );
            }
        }

        $unavailablePartners = $partners->filter(
            fn (Wrestler $wrestler): bool => $wrestler->currentInjury()->exists(),
        );

        if ($unavailablePartners->isNotEmpty()) {
            $unavailablePartnerNames = $unavailablePartners
                ->map(fn (Wrestler $wrestler): string => $wrestler->name)
                ->implode(', ');

            throw CannotBeUnretiredException::keyPartnersUnavailable(
                $tagTeam,
                $unavailablePartnerNames,
            );
        }
    }

    /**
     * The wrestlers whose membership was open when the tag team's current retirement started: still on the
     * team, or ended on or after that date because they came back alone. Includes soft-deleted wrestlers.
     *
     * @return Collection<int, Wrestler>
     */
    public function membersAtRetirement(TagTeam $tagTeam): Collection
    {
        $retiredAt = $tagTeam->currentRetirement()->value('started_at');

        return Wrestler::query()
            ->withTrashed()
            ->whereIn('id', TagTeamWrestler::query()
                ->forTagTeamId($tagTeam->id)
                ->where('joined_at', '<=', $retiredAt)
                ->where(fn (Builder $query) => $query->whereNull('left_at')->orWhere('left_at', '>=', $retiredAt))
                ->select('wrestler_id'))
            ->inLockOrder()
            ->get();
    }
}
