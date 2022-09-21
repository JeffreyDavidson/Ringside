<?php

test('deletes a stable and redirects', function () {
    $this->actingAs(administrator())
        ->delete(action([StablesController::class, 'destroy'], $this->stable))
        ->assertRedirect(action([StablesController::class, 'index']));

    $this->assertSoftDeleted($this->stable);
});
