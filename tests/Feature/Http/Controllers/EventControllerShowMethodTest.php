<?php

declare(strict_types=1);

use App\Http\Controllers\EventsController;
use App\Models\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->event = Event::factory()->create();
});

test('show returns a view', function () {
    actingAs(administrator())
        ->get(action([EventsController::class, 'show'], $this->event))
        ->assertViewIs('events.show')
        ->assertViewHas('event', $this->event);
});

test('a basic user cannot view an event profile', function () {
    actingAs(basicUser())
        ->get(action([EventsController::class, 'show'], $this->event))
        ->assertForbidden();
});

test('a guest cannot view an event profile', function () {
    get(action([EventsController::class, 'show'], $this->event))
        ->assertRedirect(route('login'));
});
