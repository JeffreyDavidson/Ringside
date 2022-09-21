<?php

test('invoke restores a deleted event and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RestoreController::class], $this->event))
        ->assertRedirect(action([EventsController::class, 'index']));

    $this->assertNull($this->event->fresh()->deleted_at);
});
