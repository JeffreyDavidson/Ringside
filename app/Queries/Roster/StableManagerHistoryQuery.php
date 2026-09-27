<?php

declare(strict_types=1);

namespace App\Queries\Roster;

use App\Builders\Roster\ManagerBuilder;
use App\Builders\Roster\StableBuilder;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class StableManagerHistoryQuery
{
    /** @return StableBuilder<Stable> */
    public static function previousStablesForManagerId(int $managerId): StableBuilder
    {
        return Stable::query()
            ->where(function (Builder $stableQuery) use ($managerId): void {
                $stableQuery
                    ->whereExists(function (QueryBuilder $associationQuery) use ($managerId): void {
                        self::previousWrestlerAssociation($associationQuery)
                            ->where('wrestlers_managers.manager_id', $managerId)
                            ->whereColumn('stables_wrestlers.stable_id', 'stables.id');
                    })
                    ->orWhereExists(function (QueryBuilder $associationQuery) use ($managerId): void {
                        self::previousTagTeamAssociation($associationQuery)
                            ->where('tag_teams_managers.manager_id', $managerId)
                            ->whereColumn('stables_tag_teams.stable_id', 'stables.id');
                    });
            })
            ->whereNotExists(function (QueryBuilder $associationQuery) use ($managerId): void {
                self::currentWrestlerAssociation($associationQuery)
                    ->where('wrestlers_managers.manager_id', $managerId)
                    ->whereColumn('stables_wrestlers.stable_id', 'stables.id');
            })
            ->whereNotExists(function (QueryBuilder $associationQuery) use ($managerId): void {
                self::currentTagTeamAssociation($associationQuery)
                    ->where('tag_teams_managers.manager_id', $managerId)
                    ->whereColumn('stables_tag_teams.stable_id', 'stables.id');
            })
            ->orderBy('stables.name');
    }

    /** @return ManagerBuilder<Manager> */
    public static function previousManagersForStableId(int $stableId): ManagerBuilder
    {
        return Manager::query()
            ->where(function (Builder $managerQuery) use ($stableId): void {
                $managerQuery
                    ->whereExists(function (QueryBuilder $associationQuery) use ($stableId): void {
                        self::previousWrestlerAssociation($associationQuery)
                            ->where('stables_wrestlers.stable_id', $stableId)
                            ->whereColumn('wrestlers_managers.manager_id', 'managers.id');
                    })
                    ->orWhereExists(function (QueryBuilder $associationQuery) use ($stableId): void {
                        self::previousTagTeamAssociation($associationQuery)
                            ->where('stables_tag_teams.stable_id', $stableId)
                            ->whereColumn('tag_teams_managers.manager_id', 'managers.id');
                    });
            })
            ->whereNotExists(function (QueryBuilder $associationQuery) use ($stableId): void {
                self::currentWrestlerAssociation($associationQuery)
                    ->where('stables_wrestlers.stable_id', $stableId)
                    ->whereColumn('wrestlers_managers.manager_id', 'managers.id');
            })
            ->whereNotExists(function (QueryBuilder $associationQuery) use ($stableId): void {
                self::currentTagTeamAssociation($associationQuery)
                    ->where('stables_tag_teams.stable_id', $stableId)
                    ->whereColumn('tag_teams_managers.manager_id', 'managers.id');
            })
            ->orderBy('managers.last_name')
            ->orderBy('managers.first_name');
    }

    private static function previousWrestlerAssociation(QueryBuilder $query): QueryBuilder
    {
        return self::wrestlerAssociation($query)
            ->where(function (QueryBuilder $endedAssociationQuery): void {
                $endedAssociationQuery
                    ->whereNotNull('wrestlers_managers.fired_at')
                    ->orWhereNotNull('stables_wrestlers.left_at');
            });
    }

    private static function currentWrestlerAssociation(QueryBuilder $query): QueryBuilder
    {
        return self::wrestlerAssociation($query)
            ->whereNull('wrestlers_managers.fired_at')
            ->whereNull('stables_wrestlers.left_at');
    }

    private static function wrestlerAssociation(QueryBuilder $query): QueryBuilder
    {
        $query
            ->selectRaw('1')
            ->from('wrestlers_managers')
            ->join(
                'stables_wrestlers',
                'stables_wrestlers.wrestler_id',
                '=',
                'wrestlers_managers.wrestler_id',
            );

        return self::whereAssociationPeriodsOverlap(
            $query,
            'stables_wrestlers.joined_at',
            'stables_wrestlers.left_at',
            'wrestlers_managers.hired_at',
            'wrestlers_managers.fired_at',
        );
    }

    private static function previousTagTeamAssociation(QueryBuilder $query): QueryBuilder
    {
        return self::tagTeamAssociation($query)
            ->where(function (QueryBuilder $endedAssociationQuery): void {
                $endedAssociationQuery
                    ->whereNotNull('tag_teams_managers.fired_at')
                    ->orWhereNotNull('stables_tag_teams.left_at');
            });
    }

    private static function currentTagTeamAssociation(QueryBuilder $query): QueryBuilder
    {
        return self::tagTeamAssociation($query)
            ->whereNull('tag_teams_managers.fired_at')
            ->whereNull('stables_tag_teams.left_at');
    }

    private static function tagTeamAssociation(QueryBuilder $query): QueryBuilder
    {
        $query
            ->selectRaw('1')
            ->from('tag_teams_managers')
            ->join(
                'stables_tag_teams',
                'stables_tag_teams.tag_team_id',
                '=',
                'tag_teams_managers.tag_team_id',
            );

        return self::whereAssociationPeriodsOverlap(
            $query,
            'stables_tag_teams.joined_at',
            'stables_tag_teams.left_at',
            'tag_teams_managers.hired_at',
            'tag_teams_managers.fired_at',
        );
    }

    private static function whereAssociationPeriodsOverlap(
        QueryBuilder $query,
        string $membershipStart,
        string $membershipEnd,
        string $assignmentStart,
        string $assignmentEnd,
    ): QueryBuilder {
        return $query
            ->where(function (QueryBuilder $membershipEndQuery) use ($membershipEnd, $assignmentStart): void {
                $membershipEndQuery
                    ->whereNull($membershipEnd)
                    ->orWhereColumn($assignmentStart, '<=', $membershipEnd);
            })
            ->where(function (QueryBuilder $assignmentEndQuery) use ($membershipStart, $assignmentEnd): void {
                $assignmentEndQuery
                    ->whereNull($assignmentEnd)
                    ->orWhereColumn($membershipStart, '<=', $assignmentEnd);
            });
    }
}
