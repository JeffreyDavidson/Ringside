<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacyTables = [
            'wrestlers_retirements' => ['owner_key' => 'wrestler_id', 'owner_type' => 'App\\Models\\Wrestlers\\Wrestler'],
            'managers_retirements' => ['owner_key' => 'manager_id', 'owner_type' => 'App\\Models\\Managers\\Manager'],
            'referees_retirements' => ['owner_key' => 'referee_id', 'owner_type' => 'App\\Models\\Referees\\Referee'],
            'tag_teams_retirements' => ['owner_key' => 'tag_team_id', 'owner_type' => 'App\\Models\\TagTeams\\TagTeam'],
            'stables_retirements' => ['owner_key' => 'stable_id', 'owner_type' => 'App\\Models\\Stables\\Stable'],
            'titles_retirements' => ['owner_key' => 'title_id', 'owner_type' => 'App\\Models\\Titles\\Title'],
        ];

        $expectedCount = 0;

        foreach ($legacyTables as $table => $owner) {
            $records = DB::table($table)->get();
            $expectedCount += $records->count();

            foreach ($records as $record) {
                DB::table('retirements')->insert([
                    'retirable_id' => $record->{$owner['owner_key']},
                    'retirable_type' => $owner['owner_type'],
                    'started_at' => $record->started_at,
                    'ended_at' => $record->ended_at,
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at,
                ]);
            }
        }

        if (DB::table('retirements')->count() !== $expectedCount) {
            throw new RuntimeException('Legacy retirement records were not fully transferred.');
        }
    }
};
