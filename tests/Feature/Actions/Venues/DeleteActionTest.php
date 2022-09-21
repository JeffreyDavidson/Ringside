<?php

test('destroy calls delete action and redirects', function () {
    $this->actingAs(administrator())
        ->delete(action([VenuesController::class, 'destroy'], $this->venue))
        ->assertRedirect(action([VenuesController::class, 'index']));

    $this->assertSoftDeleted($this->venue);
});
