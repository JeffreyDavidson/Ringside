<?php

test('updates a venue and redirects', function () {
    $this->actingAs(administrator())
        ->from(action([VenuesController::class, 'edit'], $this->venue))
        ->patch(action([VenuesController::class, 'update'], $this->venue), $this->data)
        ->assertValid()
        ->assertRedirect(action([VenuesController::class, 'index']));

    expect($venue->fresh())
        ->name->toBe('New Venue Name')
        ->street_address->toBe('456 1st Avenue')
        ->city->toBe('Laraville')
        ->state->toBe('California')
        ->zip->toBe('67890');
});
