<?php

use App\Http\Controllers\Events\EventsController;
use App\Models\Event;

test('index returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([EventsController::class, 'index']))
        ->assertOk()
        ->assertViewIs('events.index')
        ->assertSeeLivewire('events.events-list');
});

test('a basic user cannot view events index page', function () {
    $this->actingAs(basicUser())
        ->get(action([EventsController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view events index page', function () {
    $this->get(action([EventsController::class, 'index']))
        ->assertRedirect(route('login'));
});

test('show returns a view', function () {
    $event = Event::factory()->create();

    $this->actingAs(administrator())
        ->get(action([EventsController::class, 'show'], $event))
        ->assertViewIs('events.show')
        ->assertViewHas('event', $event);
});

test('a basic user can view event show page', function () {
    $event = Event::factory()->create();

    $this->actingAs(basicUser())
        ->get(action([EventsController::class, 'show'], $event))
        ->assertForbidden();
});

test('a guest cannot view a event profile', function () {
    $event = Event::factory()->create();

    $this->get(action([EventsController::class, 'show'], $event))
        ->assertRedirect(route('login'));
});

test('deletes a event and redirects', function () {
    $event = Event::factory()->create();

    $this->actingAs(administrator())
        ->delete(action([EventsController::class, 'destroy'], $event))
        ->assertRedirect(action([EventsController::class, 'index']));

    $this->assertSoftDeleted($event);
});

test('a basic user cannot delete a event', function () {
    $event = Event::factory()->create();

    $this->actingAs(basicUser())
        ->delete(action([EventsController::class, 'destroy'], $event))
        ->assertForbidden();
});

test('a guest cannot delete a event', function () {
    $event = Event::factory()->create();

    $this->delete(action([EventsController::class, 'destroy'], $event))
        ->assertRedirect(route('login'));
});
