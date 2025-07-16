<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Matches\MatchDecision;
use Illuminate\Database\Seeder;

class MatchDecisionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $matchDecisions = [
            'Pinfall',
            'Submission',
            'Disqualification',
            'Countout',
            'Knockout',
            'Stipulation',
            'Forfeit',
            'Time Limit Draw',
            'No Decision',
            'Reverse Decision',
        ];

        foreach ($matchDecisions as $name) {
            MatchDecision::query()->firstOrCreate(['name' => $name]);
        }
    }
}
