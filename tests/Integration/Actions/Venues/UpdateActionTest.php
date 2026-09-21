<?php

declare(strict_types=1);

use App\Actions\Venues\UpdateAction;
use App\Data\Events\VenueData;
use App\Models\Events\Venue;

test('it updates a venue while preserving its identity', function (): void {
    $venue = Venue::factory()->create();
    $data = new VenueData('Updated Arena', '100 New Street', 'Austin', 'Texas', '78701');

    $updatedVenue = resolve(UpdateAction::class)->handle($venue, $data);

    expect($updatedVenue)
        ->id->toBe($venue->id)
        ->name->toBe('Updated Arena')
        ->street_address->toBe('100 New Street')
        ->city->toBe('Austin')
        ->state->toBe('Texas')
        ->zipcode->toBe('78701');
});
