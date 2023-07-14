<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MatchType;
use Illuminate\Database\Seeder;

class MatchTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MatchType::query()->create(['name' => 'Singles', 'slug' => 'singles', 'number_of_sides' => 2]);
        MatchType::query()->create(['name' => 'Tag Team', 'slug' => 'tagteam', 'number_of_sides' => 2]);
        MatchType::query()->create(['name' => 'Triple Threat', 'slug' => 'triple', 'number_of_sides' => 3]);
        MatchType::query()->create(['name' => 'Triangle', 'slug' => 'triangle', 'number_of_sides' => 3]);
        MatchType::query()->create(['name' => 'Fatal 4 Way', 'slug' => 'fatal4way', 'number_of_sides' => 4]);
        MatchType::query()->create(['name' => '6 Man Tag Team', 'slug' => '6man', 'number_of_sides' => 2]);
        MatchType::query()->create(['name' => '8 Man Tag Team', 'slug' => '8man', 'number_of_sides' => 2]);
        MatchType::query()->create(['name' => '10 Man Tag Team', 'slug' => '10man', 'number_of_sides' => 2]);
        MatchType::query()->create(['name' => 'Two On One Handicap', 'slug' => '21handicap', 'number_of_sides' => 2]);
        MatchType::query()->create(['name' => 'Three On Two Handicap', 'slug' => '32handicap', 'number_of_sides' => 2]);
        MatchType::query()->create(['name' => 'Battle Royal', 'slug' => 'battleroyal', 'number_of_sides' => null]);
        MatchType::query()->create(['name' => 'Royal Rumble', 'slug' => 'royalrumble', 'number_of_sides' => null]);
        MatchType::query()->create(['name' => 'Tornado Tag Team', 'slug' => 'tornadotag', 'number_of_sides' => 2]);
        MatchType::query()->create(['name' => 'Gauntlet', 'slug' => 'gauntlet', 'number_of_sides' => 2]);
    }
}
