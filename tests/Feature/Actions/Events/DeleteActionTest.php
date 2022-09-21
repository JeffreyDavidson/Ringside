<?php

test('deletes a event and redirects', function () {
    $this->actingAs(administrator())
        ->delete(action([EventsController::class, 'destroy'], $event))
        ->assertRedirect(action([EventsController::class, 'index']));

    $this->assertSoftDeleted($event);
});
