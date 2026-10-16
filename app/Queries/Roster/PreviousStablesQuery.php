<?php

declare(strict_types=1);

namespace App\Queries\Roster;

use App\Builders\Roster\StableBuilder;
use App\Models\Roster\Stables\Stable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class PreviousStablesQuery extends StableManagerHistoryQuery
{
    /** @return StableBuilder<Stable> */
    public function get(int $managerId): StableBuilder
    {
        return Stable::query()
            ->where(function (Builder $stableQuery) use ($managerId): void {
                $stableQuery
                    ->whereExists(function (QueryBuilder $associationQuery) use ($managerId): void {
                        $this->previousWrestlerAssociation($associationQuery)
                            ->where('wrestlers_managers.manager_id', $managerId)
                            ->whereColumn('stables_wrestlers.stable_id', 'stables.id');
                    })
                    ->orWhereExists(function (QueryBuilder $associationQuery) use ($managerId): void {
                        $this->previousTagTeamAssociation($associationQuery)
                            ->where('tag_teams_managers.manager_id', $managerId)
                            ->whereColumn('stables_tag_teams.stable_id', 'stables.id');
                    });
            })
            ->whereNotExists(function (QueryBuilder $associationQuery) use ($managerId): void {
                $this->currentWrestlerAssociation($associationQuery)
                    ->where('wrestlers_managers.manager_id', $managerId)
                    ->whereColumn('stables_wrestlers.stable_id', 'stables.id');
            })
            ->whereNotExists(function (QueryBuilder $associationQuery) use ($managerId): void {
                $this->currentTagTeamAssociation($associationQuery)
                    ->where('tag_teams_managers.manager_id', $managerId)
                    ->whereColumn('stables_tag_teams.stable_id', 'stables.id');
            })
            ->orderBy('stables.name');
    }
}
