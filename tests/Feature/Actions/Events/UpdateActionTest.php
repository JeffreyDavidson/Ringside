<?php

test('updates a event and redirects', function () {
    $this->actingAs(administrator())
        ->from(action([EventsController::class, 'edit'], $this->event))
        ->patch(action([EventsController::class, 'update'], $this->event), $this->data)
        ->assertValid()
        ->assertRedirect(action([EventsController::class, 'index']));

    expect($event->fresh())
        ->name->toBe('New Event Name')
        ->date->toBeNull()
        ->venue_id->toBeNull()
        ->preview->toBeNull();
});
