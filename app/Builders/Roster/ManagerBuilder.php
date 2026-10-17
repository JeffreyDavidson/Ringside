<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\ChecksStableManagerAssociations;
use App\Builders\Concerns\HasNameSearch;
use App\Models\Roster\Managers\Manager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @template TModel of Manager
 *
 * @extends IndividualBuilder<TModel>
 */
class ManagerBuilder extends IndividualBuilder
{
    use ChecksStableManagerAssociations;
    use HasNameSearch;

    /** Managers who managed a wrestler or tag team while it was in the stable, but no longer do. */
    public function whereFormerManagerOfStable(int $stableId): static
    {
        return $this
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
            });
    }
}
