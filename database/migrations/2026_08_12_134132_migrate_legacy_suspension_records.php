<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacyTables = [
            'wrestlers_suspensions' => ['owner_key' => 'wrestler_id', 'owner_type' => 'App\\Models\\Wrestlers\\Wrestler'],
            'managers_suspensions' => ['owner_key' => 'manager_id', 'owner_type' => 'App\\Models\\Managers\\Manager'],
            'referees_suspensions' => ['owner_key' => 'referee_id', 'owner_type' => 'App\\Models\\Referees\\Referee'],
            'tag_teams_suspensions' => ['owner_key' => 'tag_team_id', 'owner_type' => 'App\\Models\\TagTeams\\TagTeam'],
        ];

        $expectedCount = 0;

        foreach ($legacyTables as $table => $owner) {
            $records = DB::table($table)->get();
            $expectedCount += $records->count();

            foreach ($records as $record) {
                DB::table('suspensions')->insert([
                    'suspendable_id' => $record->{$owner['owner_key']},
                    'suspendable_type' => $owner['owner_type'],
                    'started_at' => $record->started_at,
                    'ended_at' => $record->ended_at,
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at,
                ]);
            }
        }

        if (DB::table('suspensions')->count() !== $expectedCount) {
            throw new RuntimeException('Legacy suspension records were not fully transferred.');
        }
    }
};
