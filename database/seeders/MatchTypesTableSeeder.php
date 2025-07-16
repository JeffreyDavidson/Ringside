<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Matches\MatchType;
use Illuminate\Database\Seeder;

class MatchTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $matchTypes = [
            ['name' => 'Singles', 'number_of_sides' => 2],
            ['name' => 'Tag Team', 'number_of_sides' => 2],
            ['name' => 'Triple Threat', 'number_of_sides' => 3],
            ['name' => 'Triangle', 'number_of_sides' => 3],
            ['name' => 'Fatal 4 Way', 'number_of_sides' => 4],
            ['name' => '6 Man Tag Team', 'number_of_sides' => 2],
            ['name' => '8 Man Tag Team', 'number_of_sides' => 2],
            ['name' => '10 Man Tag Team', 'number_of_sides' => 2],
            ['name' => 'Two On One Handicap', 'number_of_sides' => 2],
            ['name' => 'Three On Two Handicap', 'number_of_sides' => 2],
            ['name' => 'Battle Royal', 'number_of_sides' => null],
            ['name' => 'Royal Rumble', 'number_of_sides' => null],
            ['name' => 'Tornado Tag Team', 'number_of_sides' => 2],
            ['name' => 'Gauntlet', 'number_of_sides' => 2],
        ];

        foreach ($matchTypes as $matchType) {
            MatchType::query()->firstOrCreate(['name' => $matchType['name']], $matchType);
        }
    }
}
