<?php

test('invoke restores a deleted stable and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RestoreController::class], $this->stable))
        ->assertRedirect(action([StablesController::class, 'index']));

    $this->assertNull($this->stable->fresh()->deleted_at);
});
