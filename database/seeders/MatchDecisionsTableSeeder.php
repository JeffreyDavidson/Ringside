<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MatchDecision;
use Illuminate\Database\Seeder;

class MatchDecisionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MatchDecision::query()->create(['name' => 'Pinfall', 'slug' => 'pinfall']);
        MatchDecision::query()->create(['name' => 'Submission', 'slug' => 'submission']);
        MatchDecision::query()->create(['name' => 'Disqualification', 'slug' => 'dq']);
        MatchDecision::query()->create(['name' => 'Countout', 'slug' => 'countout']);
        MatchDecision::query()->create(['name' => 'Knockout', 'slug' => 'knockout']);
        MatchDecision::query()->create(['name' => 'Stipulation', 'slug' => 'stipulation']);
        MatchDecision::query()->create(['name' => 'Forfeit', 'slug' => 'forfeit']);
        MatchDecision::query()->create(['name' => 'Time Limit Draw', 'slug' => 'draw']);
        MatchDecision::query()->create(['name' => 'No Decision', 'slug' => 'nodecision']);
        MatchDecision::query()->create(['name' => 'Reverse Decision', 'slug' => 'revdecision']);
    }
}
