<?php

use App\Models\MatchType;
use Illuminate\Database\Seeder;

class MatchTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        MatchType::create(['name' => 'Singles', 'slug' => 'singles']);
        MatchType::create(['name' => 'Tag Team', 'slug' => 'tagteam']);
        MatchType::create(['name' => 'Triple Threat', 'slug' => 'triple']);
        MatchType::create(['name' => 'Triangle', 'slug' => 'triangle']);
        MatchType::create(['name' => 'Fatal 4 Way', 'slug' => 'fatal4way']);
        MatchType::create(['name' => '6 Man Tag Team', 'slug' => '6man']);
        MatchType::create(['name' => '8 Man Tag Team', 'slug' => '8man']);
        MatchType::create(['name' => '10 Man Tag Team', 'slug' => '10man']);
        MatchType::create(['name' => 'Two On One Handicap', 'slug' => '21handicap']);
        MatchType::create(['name' => 'Three On Two Handicap', 'slug' => '32handicap']);
        MatchType::create(['name' => 'Battle Royal', 'slug' => 'battleroyal']);
        MatchType::create(['name' => 'Royal Rumble', 'slug' => 'royalrumble']);
        MatchType::create(['name' => 'Tornado Tag Team', 'slug' => 'tornadotag']);
        MatchType::create(['name' => 'Gauntlet', 'slug' => 'gauntlet']);
    }
}
