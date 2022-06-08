<?php

use App\Http\Controllers\Events\EventsController;
use App\Http\Controllers\Events\RestoreController;
use App\Models\Event;

beforeEach(function () {
    $this->event = Event::factory()->trashed()->create();
});

test('invoke restores a deleted event and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RestoreController::class], $this->event))
        ->assertRedirect(action([EventsController::class, 'index']));

    $this->assertNull($this->event->fresh()->deleted_at);
});

test('a basic user cannot restore a deleted event', function () {
    $this->actingAs(basicUser())
        ->patch(action([RestoreController::class], $this->event))
        ->assertForbidden();
});

test('a guest cannot restore a deleted event', function () {
    $this->patch(action([RestoreController::class], $this->event))
        ->assertRedirect(route('login'));
});
