<?php

declare(strict_types=1);

use App\Actions\Events\RestoreAction;
use App\Http\Controllers\Events\EventsController;
use App\Http\Controllers\Events\RestoreController;
use App\Models\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->event = Event::factory()->trashed()->create();
});

test('invoke calls restore action and redirects', function () {
    actingAs(administrator())
        ->patch(action([RestoreController::class], $this->event))
        ->assertRedirect(action([EventsController::class, 'show'], $this->event));

    RestoreAction::shouldRun()->with($this->event);
});

test('invoke calls restore action and has exception thrown', function () {
    RestoreAction::shouldRun()->with($this->event)->andThrow(Exception::class);

    actingAs(administrator())
        ->patch(action([RestoreController::class], $this->event))
        ->assertSessionHas('error');
});

test('a basic user cannot restore an event', function () {
    actingAs(basicUser())
        ->patch(action([RestoreController::class], $this->event))
        ->assertForbidden();
});

test('a guest cannot restore an event', function () {
    patch(action([RestoreController::class], $this->event))
        ->assertRedirect(route('login'));
});
