<?php

declare(strict_types=1);

namespace App\Queries\Roster;

use Illuminate\Database\Query\Builder as QueryBuilder;

/** Shared association checks for the previous stables of a manager and the previous managers of a stable. */
abstract class StableManagerHistoryQuery
{
    protected function previousWrestlerAssociation(QueryBuilder $query): QueryBuilder
    {
        return $this->wrestlerAssociation($query)
            ->where(function (QueryBuilder $endedAssociationQuery): void {
                $endedAssociationQuery
                    ->whereNotNull('wrestlers_managers.fired_at')
                    ->orWhereNotNull('stables_wrestlers.left_at');
            });
    }

    protected function currentWrestlerAssociation(QueryBuilder $query): QueryBuilder
    {
        return $this->wrestlerAssociation($query)
            ->whereNull('wrestlers_managers.fired_at')
            ->whereNull('stables_wrestlers.left_at');
    }

    protected function wrestlerAssociation(QueryBuilder $query): QueryBuilder
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

        return $this->whereAssociationPeriodsOverlap(
            $query,
            'stables_wrestlers.joined_at',
            'stables_wrestlers.left_at',
            'wrestlers_managers.hired_at',
            'wrestlers_managers.fired_at',
        );
    }

    protected function previousTagTeamAssociation(QueryBuilder $query): QueryBuilder
    {
        return $this->tagTeamAssociation($query)
            ->where(function (QueryBuilder $endedAssociationQuery): void {
                $endedAssociationQuery
                    ->whereNotNull('tag_teams_managers.fired_at')
                    ->orWhereNotNull('stables_tag_teams.left_at');
            });
    }

    protected function currentTagTeamAssociation(QueryBuilder $query): QueryBuilder
    {
        return $this->tagTeamAssociation($query)
            ->whereNull('tag_teams_managers.fired_at')
            ->whereNull('stables_tag_teams.left_at');
    }

    protected function tagTeamAssociation(QueryBuilder $query): QueryBuilder
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

        return $this->whereAssociationPeriodsOverlap(
            $query,
            'stables_tag_teams.joined_at',
            'stables_tag_teams.left_at',
            'tag_teams_managers.hired_at',
            'tag_teams_managers.fired_at',
        );
    }

    protected function whereAssociationPeriodsOverlap(
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
