<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacyTables = [
            'stables_activations' => ['owner_key' => 'stable_id', 'owner_type' => 'stable'],
            'titles_activations' => ['owner_key' => 'title_id', 'owner_type' => 'title'],
        ];

        $expectedCount = 0;

        foreach ($legacyTables as $table => $owner) {
            $records = DB::table($table)->get();
            $expectedCount += $records->count();

            foreach ($records as $record) {
                DB::table('activity_periods')->insert([
                    'activeable_id' => $record->{$owner['owner_key']},
                    'activeable_type' => $owner['owner_type'],
                    'started_at' => $record->started_at,
                    'ended_at' => $record->ended_at,
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at,
                ]);
            }
        }

        if (DB::table('activity_periods')->count() !== $expectedCount) {
            throw new RuntimeException('Legacy activity-period records were not fully transferred.');
        }
    }
};
