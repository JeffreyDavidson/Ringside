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
        MatchDecision::query()->firstOrCreate(['name' => 'Pinfall', 'slug' => 'pinfall']);
        MatchDecision::query()->firstOrCreate(['name' => 'Submission', 'slug' => 'submission']);
        MatchDecision::query()->firstOrCreate(['name' => 'Disqualification', 'slug' => 'dq']);
        MatchDecision::query()->firstOrCreate(['name' => 'Countout', 'slug' => 'countout']);
        MatchDecision::query()->firstOrCreate(['name' => 'Knockout', 'slug' => 'knockout']);
        MatchDecision::query()->firstOrCreate(['name' => 'Stipulation', 'slug' => 'stipulation']);
        MatchDecision::query()->firstOrCreate(['name' => 'Forfeit', 'slug' => 'forfeit']);
        MatchDecision::query()->firstOrCreate(['name' => 'Time Limit Draw', 'slug' => 'draw']);
        MatchDecision::query()->firstOrCreate(['name' => 'No Decision', 'slug' => 'nodecision']);
        MatchDecision::query()->firstOrCreate(['name' => 'Reverse Decision', 'slug' => 'revdecision']);
    }
}
