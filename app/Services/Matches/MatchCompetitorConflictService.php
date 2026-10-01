<?php

declare(strict_types=1);

namespace App\Services\Matches;

use App\Builders\Matches\MatchCompetitorBuilder;
use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use Closure;
use Illuminate\Support\Collection;

final class MatchCompetitorConflictService
{
    /**
     * @param  Collection<int, int>  $conflictingEventIds
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    public function ensureWrestlersCanBeAssigned(Collection $conflictingEventIds, Collection $wrestlers): void
    {
        $this->ensureCompetitorsCanBeAssigned(
            $conflictingEventIds,
            $wrestlers,
            fn (Collection $competitorIds): MatchCompetitorBuilder => MatchCompetitor::query()
                ->forWrestlerIds($competitorIds),
            'Wrestler',
        );

        $this->ensureWrestlersAreNotBookedThroughTheirTagTeam($conflictingEventIds, $wrestlers);
    }

    /**
     * @param  Collection<int, int>  $conflictingEventIds
     * @param  Collection<int, TagTeam>  $tagTeams
     */
    public function ensureTagTeamsCanBeAssigned(Collection $conflictingEventIds, Collection $tagTeams): void
    {
        $this->ensureCompetitorsCanBeAssigned(
            $conflictingEventIds,
            $tagTeams,
            fn (Collection $competitorIds): MatchCompetitorBuilder => MatchCompetitor::query()
                ->forTagTeamIds($competitorIds),
            'Tag team',
        );

        $this->ensureTagTeamMembersAreNotBookedIndividually($conflictingEventIds, $tagTeams);
    }

    /**
     * A wrestler cannot be booked individually while a tag team they currently belong to is entered
     * in a match at the same event or time.
     *
     * @param  Collection<int, int>  $conflictingEventIds
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    private function ensureWrestlersAreNotBookedThroughTheirTagTeam(Collection $conflictingEventIds, Collection $wrestlers): void
    {
        $conflictingMembership = TagTeamWrestler::query()
            ->current()
            ->whereIn('wrestler_id', $wrestlers->pluck('id')->all())
            ->whereIn(
                'tag_team_id',
                MatchCompetitor::query()
                    ->where('competitor_type', (new TagTeam)->getMorphClass())
                    ->forEventIds($conflictingEventIds)
                    ->select('competitor_id'),
            )
            ->first(['wrestler_id']);

        if ($conflictingMembership === null) {
            return;
        }

        $wrestler = $wrestlers->firstWhere('id', $conflictingMembership->wrestler_id);

        throw SchedulingConflictException::competitorAlreadyBooked(
            'Wrestler',
            $wrestler === null ? "ID: {$conflictingMembership->wrestler_id}" : $wrestler->name,
        );
    }

    /**
     * A tag team cannot be booked while one of its current members is booked individually at the same
     * event or time.
     *
     * @param  Collection<int, int>  $conflictingEventIds
     * @param  Collection<int, TagTeam>  $tagTeams
     */
    private function ensureTagTeamMembersAreNotBookedIndividually(Collection $conflictingEventIds, Collection $tagTeams): void
    {
        $memberIds = TagTeamWrestler::query()
            ->current()
            ->whereIn('tag_team_id', $tagTeams->pluck('id')->all())
            ->get()
            ->map(fn (TagTeamWrestler $membership): int => $membership->wrestler_id);

        $conflictingCompetitor = MatchCompetitor::query()
            ->forWrestlerIds($memberIds)
            ->forEventIds($conflictingEventIds)
            ->first(['competitor_id']);

        if ($conflictingCompetitor === null) {
            return;
        }

        throw SchedulingConflictException::competitorAlreadyBooked(
            'Wrestler',
            Wrestler::query()->findOrFail($conflictingCompetitor->competitor_id)->name,
        );
    }

    /**
     * @param  Collection<int, int>  $conflictingEventIds
     * @param  Collection<int, Wrestler>|Collection<int, TagTeam>  $competitors
     * @param  Closure(Collection<int, int>): MatchCompetitorBuilder<MatchCompetitor>  $queryForCompetitors
     */
    private function ensureCompetitorsCanBeAssigned(
        Collection $conflictingEventIds,
        Collection $competitors,
        Closure $queryForCompetitors,
        string $entityType,
    ): void {
        $conflictingCompetitor = $queryForCompetitors(
            $competitors->map(fn (Wrestler|TagTeam $competitor): int => $competitor->id),
        )
            ->forEventIds($conflictingEventIds)
            ->first(['competitor_id']);

        if ($conflictingCompetitor === null) {
            return;
        }

        $conflictingCompetitorId = $conflictingCompetitor->competitor_id;
        $competitor = $competitors->firstWhere('id', $conflictingCompetitorId);

        throw SchedulingConflictException::competitorAlreadyBooked(
            $entityType,
            $competitor === null ? "ID: {$conflictingCompetitorId}" : (string) $competitor->name,
        );
    }
}
