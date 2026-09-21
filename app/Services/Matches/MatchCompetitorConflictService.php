<?php

declare(strict_types=1);

namespace App\Services\Matches;

use App\Builders\Matches\MatchCompetitorBuilder;
use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\TagTeams\TagTeam;
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
