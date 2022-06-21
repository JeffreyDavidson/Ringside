<?php

use Illuminate\Support\Facades\Artisan;
use function Pest\Laravel\assertDatabaseHas;

test('all needed match decisions are saved in database', function () {
    Artisan::call('db:seed', ['--class' => 'MatchDecisionsTableSeeder']);

    assertDatabaseHas('match_decisions', ['name' => 'Pinfall', 'slug' => 'pinfall']);
    assertDatabaseHas('match_decisions', ['name' => 'Submission', 'slug' => 'submission']);
    assertDatabaseHas('match_decisions', ['name' => 'Disqualification', 'slug' => 'dq']);
    assertDatabaseHas('match_decisions', ['name' => 'Countout', 'slug' => 'countout']);
    assertDatabaseHas('match_decisions', ['name' => 'Knockout', 'slug' => 'knockout']);
    assertDatabaseHas('match_decisions', ['name' => 'Stipulation', 'slug' => 'stipulation']);
    assertDatabaseHas('match_decisions', ['name' => 'Forfeit', 'slug' => 'forfeit']);
    assertDatabaseHas('match_decisions', ['name' => 'Time Limit Draw', 'slug' => 'draw']);
    assertDatabaseHas('match_decisions', ['name' => 'Forfeit', 'slug' => 'forfeit']);
    assertDatabaseHas('match_decisions', ['name' => 'No Decision', 'slug' => 'nodecision']);
    assertDatabaseHas('match_decisions', ['name' => 'Reverse Decision', 'slug' => 'revdecision']);
});
