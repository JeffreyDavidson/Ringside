<?php

use App\Actions\Events\DeleteAction;
use App\Http\Controllers\Events\EventsController;
use App\Models\Event;

beforeEach(function () {
    $this->event = Event::factory()->create();
});

test('destroy calls delete action and redirects', function () {
    $this->actingAs(administrator())
        ->delete(action([EventsController::class, 'destroy'], $this->event))
        ->assertRedirect(action([EventsController::class, 'index']));

    DeleteAction::shouldRun()->with($this->event);
});

test('a basic user cannot delete an event', function () {
    $this->actingAs(basicUser())
        ->delete(action([EventsController::class, 'destroy'], $this->event))
        ->assertForbidden();
});

test('a guest cannot delete an event', function () {
    $this->delete(action([EventsController::class, 'destroy'], $this->event))
        ->assertRedirect(route('login'));
});
