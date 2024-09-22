<?php

declare(strict_types=1);

use App\Policies\VenuePolicy;

test('it authorizes a user can create a venue', function () {
    expect((new VenuePolicy)->create(administrator()))->toBeTruthy();
    expect((new VenuePolicy)->create(basicUser()))->toBeFalsy();
});

test('it authorizes a user can update a venue', function () {
    expect((new VenuePolicy)->update(administrator()))->toBeTruthy();
    expect((new VenuePolicy)->update(basicUser()))->toBeFalsy();
});

test('it authorizes a user can delete a venue', function () {
    expect((new VenuePolicy)->delete(administrator()))->toBeTruthy();
    expect((new VenuePolicy)->delete(basicUser()))->toBeFalsy();
});

test('it authorizes a user can restore a venue', function () {
    expect((new VenuePolicy)->restore(administrator()))->toBeTruthy();
    expect((new VenuePolicy)->restore(basicUser()))->toBeFalsy();
});

test('it authorizes a user can view the listing of venues', function () {
    expect((new VenuePolicy)->viewList(administrator()))->toBeTruthy();
    expect((new VenuePolicy)->viewList(basicUser()))->toBeFalsy();
});
