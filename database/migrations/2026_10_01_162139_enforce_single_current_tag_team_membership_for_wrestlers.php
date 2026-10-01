<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection();

        $this->ensureNoWrestlerHasMultipleCurrentMemberships();

        match ($connection->getDriverName()) {
            'sqlite', 'pgsql', 'sqlsrv' => $connection->statement(
                'CREATE UNIQUE INDEX tag_teams_wrestlers_one_current_membership_unique '
                .'ON tag_teams_wrestlers (wrestler_id) WHERE left_at IS NULL'
            ),
            'mysql', 'mariadb' => $connection->statement(
                'ALTER TABLE tag_teams_wrestlers '
                .'ADD COLUMN current_wrestler_id BIGINT UNSIGNED '
                .'GENERATED ALWAYS AS (CASE WHEN left_at IS NULL THEN wrestler_id ELSE NULL END) STORED, '
                .'ADD UNIQUE INDEX tag_teams_wrestlers_one_current_membership_unique (current_wrestler_id)'
            ),
            default => throw new LogicException('The database driver does not support current tag team membership constraints.'),
        };
    }

    /**
     * Abort before any schema change when existing data would violate the new index.
     *
     * Existing memberships are never modified: the operator decides which extra memberships to end.
     */
    private function ensureNoWrestlerHasMultipleCurrentMemberships(): void
    {
        $duplicateWrestlerIds = DB::table('tag_teams_wrestlers')
            ->whereNull('left_at')
            ->groupBy('wrestler_id')
            ->havingRaw('count(*) > 1')
            ->orderBy('wrestler_id')
            ->pluck('wrestler_id');

        if ($duplicateWrestlerIds->isEmpty()) {
            return;
        }

        $conflicts = DB::table('tag_teams_wrestlers')
            ->whereNull('left_at')
            ->whereIn('wrestler_id', $duplicateWrestlerIds->all())
            ->orderBy('wrestler_id')
            ->orderBy('tag_team_id')
            ->get(['wrestler_id', 'tag_team_id'])
            ->groupBy('wrestler_id')
            ->map(fn ($memberships, $wrestlerId): string => sprintf(
                'wrestler %s is a current member of tag teams %s',
                $wrestlerId,
                $memberships->pluck('tag_team_id')->implode(', '),
            ))
            ->implode('; ');

        throw new RuntimeException(
            "Cannot enforce one current tag team per wrestler: {$conflicts}. "
            .'No changes were made. End the extra memberships by setting left_at on the '
            .'tag_teams_wrestlers rows that should no longer be current, then re-run "php artisan migrate".'
        );
    }
};
