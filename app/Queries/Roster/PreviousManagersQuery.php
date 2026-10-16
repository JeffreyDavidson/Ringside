<?php

declare(strict_types=1);

namespace App\Queries\Roster;

use App\Builders\Roster\ManagerBuilder;
use App\Models\Roster\Managers\Manager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class PreviousManagersQuery extends StableManagerHistoryQuery
{
    /** @return ManagerBuilder<Manager> */
    public function get(int $stableId): ManagerBuilder
    {
        return Manager::query()
            ->where(function (Builder $managerQuery) use ($stableId): void {
                $managerQuery
                    ->whereExists(function (QueryBuilder $associationQuery) use ($stableId): void {
                        $this->previousWrestlerAssociation($associationQuery)
                            ->where('stables_wrestlers.stable_id', $stableId)
                            ->whereColumn('wrestlers_managers.manager_id', 'managers.id');
                    })
                    ->orWhereExists(function (QueryBuilder $associationQuery) use ($stableId): void {
                        $this->previousTagTeamAssociation($associationQuery)
                            ->where('stables_tag_teams.stable_id', $stableId)
                            ->whereColumn('tag_teams_managers.manager_id', 'managers.id');
                    });
            })
            ->whereNotExists(function (QueryBuilder $associationQuery) use ($stableId): void {
                $this->currentWrestlerAssociation($associationQuery)
                    ->where('stables_wrestlers.stable_id', $stableId)
                    ->whereColumn('wrestlers_managers.manager_id', 'managers.id');
            })
            ->whereNotExists(function (QueryBuilder $associationQuery) use ($stableId): void {
                $this->currentTagTeamAssociation($associationQuery)
                    ->where('stables_tag_teams.stable_id', $stableId)
                    ->whereColumn('tag_teams_managers.manager_id', 'managers.id');
            })
            ->orderBy('managers.last_name')
            ->orderBy('managers.first_name');
    }
}
