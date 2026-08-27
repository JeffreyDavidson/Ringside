<?php

declare(strict_types=1);

namespace App\Services\Matches;

use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Collection;

final class MatchCompetitorConflictService
{
    /**
     * @param  Collection<int, int>  $conflictingEventIds
     * @param  Collection<int, Wrestler>|Collection<int, TagTeam>  $competitors
     * @param  class-string<Wrestler|TagTeam>  $competitorType
     */
    public function ensureCanBeAssigned(
        Collection $conflictingEventIds,
        Collection $competitors,
        string $competitorType,
        string $entityType,
    ): void {
        $conflictingCompetitor = MatchCompetitor::query()
            ->forCompetitorIds(
                $competitorType,
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
