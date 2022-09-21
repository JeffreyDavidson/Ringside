<?php

test('invoke restores a deleted wrestler and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RestoreController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    $this->assertNull($this->wrestler->fresh()->deleted_at);
});
