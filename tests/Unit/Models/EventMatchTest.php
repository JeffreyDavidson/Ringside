<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchType;
use Database\Seeders\MatchTypesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
});

test('an event match belongs to an event', function () {
    $eventMatch = EventMatch::factory()->create();

    expect($eventMatch)->event->toBeInstanceOf(Event::class);
});

test('an event match has a match type', function () {
    $eventMatch = EventMatch::factory()->make();

    expect($eventMatch)->matchType->toBeInstanceOf(MatchType::class);
});

test('an event match referees', function () {
    $eventMatch = EventMatch::factory()->create();

    expect($eventMatch)->referees->toBeCollection();
});

test('an event match titiles', function () {
    $eventMatch = EventMatch::factory()->create();

    expect($eventMatch)->titles->toBeCollection();
});

test('an event match competitors', function () {
    $eventMatch = EventMatch::factory()->create();

    expect($eventMatch)->competitors->toBeCollection();
});

test('an event match wrestlers', function () {
    $eventMatch = EventMatch::factory()->create();

    expect($eventMatch)->wrestlers->toBeCollection();
});

test('an event match tag teams', function () {
    $eventMatch = EventMatch::factory()->create();

    expect($eventMatch)->tagteams->toBeCollection();
});
