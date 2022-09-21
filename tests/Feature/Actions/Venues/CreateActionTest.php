<?php

test('store calls create action and redirects', function () {
    $this->actingAs(administrator())
        ->from(action([VenuesController::class, 'create']))
        ->post(action([VenuesController::class, 'store']), $this->data)
        ->assertValid()
        ->assertRedirect(action([VenuesController::class, 'index']));

    expect(Venue::latest()->first())
        ->name->toBe('Example Venue')
        ->street_address->toBe('123 Main Street')
        ->city->toBe('Laraville')
        ->state->toBe('New York')
        ->zip->toBe('12345');
});
