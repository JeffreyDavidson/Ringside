<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

test('all needed match decisions are saved in database', function () {
    Artisan::call('db:seed', ['--class' => 'MatchDecisionsTableSeeder']);

    // Test that all expected match decisions exist
    assertDatabaseHas('match_decisions', ['name' => 'Pinfall', 'slug' => 'pinfall']);
    assertDatabaseHas('match_decisions', ['name' => 'Submission', 'slug' => 'submission']);
    assertDatabaseHas('match_decisions', ['name' => 'Disqualification', 'slug' => 'disqualification']);
    assertDatabaseHas('match_decisions', ['name' => 'Countout', 'slug' => 'countout']);
    assertDatabaseHas('match_decisions', ['name' => 'Knockout', 'slug' => 'knockout']);
    assertDatabaseHas('match_decisions', ['name' => 'Stipulation', 'slug' => 'stipulation']);
    assertDatabaseHas('match_decisions', ['name' => 'Forfeit', 'slug' => 'forfeit']);
    assertDatabaseHas('match_decisions', ['name' => 'Time Limit Draw', 'slug' => 'time-limit-draw']);
    assertDatabaseHas('match_decisions', ['name' => 'No Decision', 'slug' => 'no-decision']);
    assertDatabaseHas('match_decisions', ['name' => 'Reverse Decision', 'slug' => 'reverse-decision']);

    // Test that exactly 10 match decisions were created (no extras)
    assertDatabaseCount('match_decisions', 10);
});
