<?php

use App\Http\Controllers\Events\EventsController;
use App\Http\Requests\Events\StoreRequest;
use App\Models\Event;

test('create returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([EventsController::class, 'create']))
        ->assertStatus(200)
        ->assertViewIs('events.create')
        ->assertViewHas('event', new Event);
});

test('a basic user cannot view the form for creating a event', function () {
    $this->actingAs(basicUser())
        ->get(action([EventsController::class, 'create']))
        ->assertForbidden();
});

test('a guest cannot view the form for creating a event', function () {
    $this->get(action([EventsController::class, 'create']))
        ->assertRedirect(route('login'));
});

test('store creates a event and redirects', function () {
    $data = StoreRequest::factory()->create([
        'name' => 'Example Event Name',
        'date' => null,
        'venue_id' => null,
        'preview' => null,
    ]);

    $this->actingAs(administrator())
        ->from(action([EventsController::class, 'create']))
        ->post(action([EventsController::class, 'store']), $data)
        ->assertValid()
        ->assertRedirect(action([EventsController::class, 'index']));

    expect(Event::latest()->first())
        ->name->toBe('Example Event Name')
        ->date->toBeNull()
        ->venue_id->toBeNull()
        ->preview->toBeNull();
});

test('a basic user cannot create a event', function () {
    $data = StoreRequest::factory()->create();

    $this->actingAs(basicUser())
        ->post(action([EventsController::class, 'store']), $data)
        ->assertForbidden();
});

test('a guest cannot create a event', function () {
    $data = StoreRequest::factory()->create();

    $this->post(action([EventsController::class, 'store']), $data)
        ->assertRedirect(route('login'));
});
