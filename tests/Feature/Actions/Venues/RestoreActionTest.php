<?php

test('invoke restores a deleted venue and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RestoreController::class], $this->venue))
        ->assertRedirect(action([VenuesController::class, 'index']));

    $this->assertNull($this->venue->fresh()->deleted_at);
});
