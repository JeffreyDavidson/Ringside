<?php

declare(strict_types=1);

namespace App\Builders\Concerns;

use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Association checks shared by the manager and stable builders: a manager is associated with a stable when a
 * wrestler or tag team the manager managed was in the stable while the manager managed them (the two periods overlap).
 */
trait ChecksStableManagerAssociations
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
