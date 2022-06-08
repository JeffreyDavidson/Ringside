<?php

use App\Http\Controllers\Events\EventsController;
use App\Http\Requests\Events\UpdateRequest;
use App\Models\Event;

test('edit returns a view', function () {
    $event = Event::factory()->create();

    $this->actingAs(administrator())
        ->get(action([EventsController::class, 'edit'], $event))
        ->assertStatus(200)
        ->assertViewIs('events.edit')
        ->assertViewHas('event', $event);
});

test('a basic user cannot view the form for editing a event', function () {
    $event = Event::factory()->create();

    $this->actingAs(basicUser())
        ->get(action([EventsController::class, 'edit'], $event))
        ->assertForbidden();
});

test('a guest cannot view the form for editing a event', function () {
    $event = Event::factory()->create();

    $this->get(action([EventsController::class, 'edit'], $event))
        ->assertRedirect(route('login'));
});

test('updates a event and redirects', function () {
    $event = Event::factory()->create([
        'name' => 'Old Event Name',
        'date' => null,
        'venue_id' => null,
        'preview' => null,
    ]);

    $data = UpdateRequest::factory()->create([
        'name' => 'New Event Name',
        'date' => null,
        'venue_id' => null,
        'preview' => null,
    ]);

    $this->actingAs(administrator())
        ->from(action([EventsController::class, 'edit'], $event))
        ->patch(action([EventsController::class, 'update'], $event), $data)
        ->assertValid()
        ->assertRedirect(action([EventsController::class, 'index']));

    expect($event->fresh())
        ->name->toBe('New Event Name')
        ->date->toBeNull()
        ->venue_id->toBeNull()
        ->preview->toBeNull();
});

test('a basic user cannot update a event', function () {
    $event = Event::factory()->create();
    $data = UpdateRequest::factory()->create();

    $this->actingAs(basicUser())
        ->patch(action([EventsController::class, 'update'], $event), $data)
        ->assertForbidden();
});

test('a guest cannot update a event', function () {
    $event = Event::factory()->create();
    $data = UpdateRequest::factory()->create();

    $this->patch(action([EventsController::class, 'update'], $event), $data)
        ->assertRedirect(route('login'));
});
