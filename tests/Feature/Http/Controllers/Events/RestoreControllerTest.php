<?php

use App\Actions\Events\RestoreAction;
use App\Http\Controllers\Events\DeletedEventsController;
use App\Http\Controllers\Events\EventsController;
use App\Http\Controllers\Events\RestoreController;
use App\Models\Event;

beforeEach(function () {
    $this->event = Event::factory()->trashed()->create();
});

test('invoke calls restore action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RestoreController::class], $this->event))
        ->assertRedirect(action([EventsController::class, 'show'], $this->event));

    RestoreAction::shouldRun()->with($this->event);
});

test('invoke calls restore action and has exception thrown', function () {
    RestoreAction::shouldRun()->with($this->event)->andThrow(Exception::class);

    $this->actingAs(administrator())
        ->from(action([DeletedEventsController::class, 'index']))
        ->patch(action([RestoreController::class], $this->event))
        ->assertRedirect(action([DeletedEventsController::class, 'index']))
        ->assertSessionHas('error');
});

test('a basic user cannot restore an event', function () {
    $this->actingAs(basicUser())
        ->patch(action([RestoreController::class], $this->event))
        ->assertForbidden();
});

test('a guest cannot restore an event', function () {
    $this->patch(action([RestoreController::class], $this->event))
        ->assertRedirect(route('login'));
});
