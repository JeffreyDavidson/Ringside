<?php

use App\Actions\Events\UpdateAction;
use App\Data\EventData;
use App\Http\Controllers\Events\EventsController;
use App\Http\Requests\Events\UpdateRequest;
use App\Models\Event;

beforeEach(function () {
    $this->event = Event::factory()->create();
    $this->data = UpdateRequest::factory()->create();
    $this->request = UpdateRequest::create(action([EventsController::class, 'update'], $this->event), 'PATCH', $this->data);
});

test('update calls update action and redirects', function () {
    $this->actingAs(administrator())
        ->from(action([EventsController::class, 'edit'], $this->event))
        ->patch(action([EventsController::class, 'update'], $this->event), $this->data)
        ->assertValid()
        ->assertRedirect(action([EventsController::class, 'index']));

    UpdateAction::shouldRun()->with($this->event, EventData::fromUpdateRequest($this->request));
});

test('a basic user cannot update an event', function () {
    $this->actingAs(basicUser())
        ->patch(action([EventsController::class, 'update'], $this->event), $this->data)
        ->assertForbidden();
});

test('a guest cannot update an event', function () {
    $this->patch(action([EventsController::class, 'update'], $this->event), $this->data)
        ->assertRedirect(route('login'));
});
