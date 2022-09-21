<?php

test('invoke restores a deleted referee and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RestoreController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    $this->assertNull($this->referee->fresh()->deleted_at);
});
