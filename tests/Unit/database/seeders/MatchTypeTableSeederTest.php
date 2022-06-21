<?php

use function Pest\Laravel\assertDatabaseHas;

test('all needed match decisions are saved in database', function () {
    Artisan::call('db:seed', ['--class' => 'MatchTypesTableSeeder']);

    assertDatabaseHas('match_types', ['name' => 'Singles', 'slug' => 'singles']);
    assertDatabaseHas('match_types', ['name' => 'Tag Team', 'slug' => 'tagteam']);
    assertDatabaseHas('match_types', ['name' => 'Triangle', 'slug' => 'triangle']);
    assertDatabaseHas('match_types', ['name' => 'Triple Threat', 'slug' => 'triple']);
    assertDatabaseHas('match_types', ['name' => 'Fatal 4 Way', 'slug' => 'fatal4way']);
    assertDatabaseHas('match_types', ['name' => '6 Man Tag Team', 'slug' => '6man']);
    assertDatabaseHas('match_types', ['name' => '8 Man Tag Team', 'slug' => '8man']);
    assertDatabaseHas('match_types', ['name' => '10 Man Tag Team', 'slug' => '10man']);
    assertDatabaseHas('match_types', ['name' => 'Two On One Handicap', 'slug' => '21handicap']);
    assertDatabaseHas('match_types', ['name' => 'Three On Two Handicap', 'slug' => '32handicap']);
    assertDatabaseHas('match_types', ['name' => 'Three On Two Handicap', 'slug' => '32handicap']);
    assertDatabaseHas('match_types', ['name' => 'Battle Royal', 'slug' => 'battleroyal']);
    assertDatabaseHas('match_types', ['name' => 'Royal Rumble', 'slug' => 'royalrumble']);
    assertDatabaseHas('match_types', ['name' => 'Tornado Tag Team', 'slug' => 'tornadotag']);
    assertDatabaseHas('match_types', ['name' => 'Gauntlet', 'slug' => 'gauntlet']);
});
