<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        collect([
            ['table' => 'employments', 'column' => 'employable_type'],
            ['table' => 'suspensions', 'column' => 'suspendable_type'],
            ['table' => 'retirements', 'column' => 'retirable_type'],
            ['table' => 'lifecycle_transitions', 'column' => 'subject_type'],
            ['table' => 'titles_championships', 'column' => 'champion_type'],
            ['table' => 'events_matches_competitors', 'column' => 'competitor_type'],
        ])->each(function (array $morphColumn): void {
            DB::table($morphColumn['table'])
                ->where($morphColumn['column'], 'tagTeam')
                ->update([$morphColumn['column'] => 'tag_team']);
        });
    }
};
