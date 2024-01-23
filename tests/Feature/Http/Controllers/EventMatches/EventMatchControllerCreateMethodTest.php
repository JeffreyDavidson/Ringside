<?php

declare(strict_types=1);

use App\Http\Controllers\EventMatches\EventMatchesController;
use App\Http\Livewire\Events\Matches\MatchForm;
use App\Models\Event;
use App\Models\EventMatch;
use Database\Seeders\MatchTypesTableSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
    $this->event = Event::factory()->scheduled()->create();
});

test('it loads the correct view', function () {
    actingAs(administrator())
        ->get(action([EventMatchesController::class, 'create'], $this->event))
        ->assertOk()
        ->assertViewIs('matches.create')
        ->assertSeeLivewire(MatchForm::class);
});

test('it passes the correct data to the view', function () {
    actingAs(administrator())
        ->get(action([EventMatchesController::class, 'create'], $this->event))
        ->assertOk()
        ->assertViewHas('event', $this->event)
        ->assertViewHas('match', new EventMatch);
});

test('a basic user cannot create a match for an event', function () {
    actingAs(basicUser())
        ->get(action([EventMatchesController::class, 'create'], $this->event))
        ->assertForbidden();
});

test('a guest cannot create a match for an event', function () {
    get(action([EventMatchesController::class, 'create'], $this->event))
        ->assertRedirect(route('login'));
});
