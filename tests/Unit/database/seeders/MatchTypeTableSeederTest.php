<?php

declare(strict_types=1);

use function Pest\Laravel\assertDatabaseHas;

test('all needed match decisions are saved in database', function () {
    Artisan::call('db:seed', ['--class' => 'MatchTypesTableSeeder']);

    assertDatabaseHas('match_types', ['name' => 'Singles', 'slug' => 'singles']);
    assertDatabaseHas('match_types', ['name' => 'Tag Team', 'slug' => 'tag-team']);
    assertDatabaseHas('match_types', ['name' => 'Triangle', 'slug' => 'triangle']);
    assertDatabaseHas('match_types', ['name' => 'Triple Threat', 'slug' => 'triple-threat']);
    assertDatabaseHas('match_types', ['name' => 'Fatal 4 Way', 'slug' => 'fatal-4-way']);
    assertDatabaseHas('match_types', ['name' => '6 Man Tag Team', 'slug' => '6-man-tag-team']);
    assertDatabaseHas('match_types', ['name' => '8 Man Tag Team', 'slug' => '8-man-tag-team']);
    assertDatabaseHas('match_types', ['name' => '10 Man Tag Team', 'slug' => '10-man-tag-team']);
    assertDatabaseHas('match_types', ['name' => 'Two On One Handicap', 'slug' => 'two-on-one-handicap']);
    assertDatabaseHas('match_types', ['name' => 'Three On Two Handicap', 'slug' => 'three-on-two-handicap']);
    assertDatabaseHas('match_types', ['name' => 'Three On Two Handicap', 'slug' => 'three-on-two-handicap']);
    assertDatabaseHas('match_types', ['name' => 'Battle Royal', 'slug' => 'battle-royal']);
    assertDatabaseHas('match_types', ['name' => 'Royal Rumble', 'slug' => 'royal-rumble']);
    assertDatabaseHas('match_types', ['name' => 'Tornado Tag Team', 'slug' => 'tornado-tag-team']);
    assertDatabaseHas('match_types', ['name' => 'Gauntlet', 'slug' => 'gauntlet']);
});
