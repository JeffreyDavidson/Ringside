<?php

use App\Http\Controllers\EventMatches\EventMatchesController;
use App\Http\Requests\EventMatches\StoreRequest;
use App\Models\Event;
use App\Models\MatchType;
use App\Models\Referee;
use App\Models\Title;
use App\Models\Wrestler;
use Database\Seeders\MatchTypesTableSeeder;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
    $this->event = Event::factory()->scheduled()->create();
});

test('store creates a non title singles match for an event and redirects', function () {
    $referee = Referee::factory()->bookable()->create();
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->bookable()->count(2)->create();
    $data = StoreRequest::factory()->create([
        'match_type_id' => MatchType::where('slug', '=', 'singles')->first()->id,
        'titles' => [],
        'referees' => [$referee->id],
        'competitors' => [
            [
                [
                    'competitor_id' => $wrestlerA->id,
                    'competitor_type' => 'wrestler',
                ],
            ],
            [
                [
                    'competitor_id' => $wrestlerB->id,
                    'competitor_type' => 'wrestler',
                ],
            ],
        ],
        'preview' => null,
    ]);

    $this
        ->actingAs(administrator())
        ->from(action([EventMatchesController::class, 'create'], $this->event))
        ->post(action([EventMatchesController::class, 'store'], $this->event), $data)
        ->assertRedirect(route('events.matches.index', $this->event));

    expect($this->event->fresh())
        ->matches->toHaveCount(1);

    expect($this->event->fresh()->matches()->first())
        ->match_type_id->toEqual(1)
        ->titles->toBeEmpty()
        ->referees->toHaveCount(1)
        ->competitors->toHaveCount(2);

    expect($this->event->fresh()->matches()->first()->competitors)
        ->toHaveCount(2)
        ->each(fn ($competitor) => $competitor->toBeInstanceOf(Wrestler::class));
});

test('store creates a title match for an event and redirects', function () {
    $title = Title::factory()->active()->create();
    $data = StoreRequest::factory()->create([
        'titles' => [$title->id],
    ]);

    $this
        ->actingAs(administrator())
        ->from(action([EventMatchesController::class, 'create'], $this->event))
        ->post(action([EventMatchesController::class, 'store'], $this->event), $data);

    expect($this->event->fresh())
        ->matches->toHaveCount(1)
        ->first()
        ->titles->toHaveCount(1)->assertCollectionHas($title);
});

test('a basic user cannot create a match for an event', function () {
    $data = StoreRequest::factory()->create();

    $this->actingAs(basicUser())
        ->post(action([EventMatchesController::class, 'store'], $this->event), $data)
        ->assertForbidden();
});

test('a guest cannot create a match for an event', function () {
    $data = StoreRequest::factory()->create();

    $this->post(action([EventMatchesController::class, 'store'], $this->event), $data)
        ->assertRedirect(route('login'));
});
