<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MatchDecision;
use Illuminate\Database\Seeder;

class MatchDecisionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        MatchDecision::create(['name' => 'Pinfall', 'slug' => 'pinfall']);
        MatchDecision::create(['name' => 'Submission', 'slug' => 'submission']);
        MatchDecision::create(['name' => 'Disqualification', 'slug' => 'dq']);
        MatchDecision::create(['name' => 'Countout', 'slug' => 'countout']);
        MatchDecision::create(['name' => 'Knockout', 'slug' => 'knockout']);
        MatchDecision::create(['name' => 'Stipulation', 'slug' => 'stipulation']);
        MatchDecision::create(['name' => 'Forfeit', 'slug' => 'forfeit']);
        MatchDecision::create(['name' => 'Time Limit Draw', 'slug' => 'draw']);
        MatchDecision::create(['name' => 'No Decision', 'slug' => 'nodecision']);
        MatchDecision::create(['name' => 'Reverse Decision', 'slug' => 'revdecision']);
    }
}
