<?php

declare(strict_types=1);

use App\Policies\EventMatchPolicy;

test('it authorizes a user can create a EventMatch', function () {
    expect((new EventMatchPolicy)->create(administrator()))->toBeTruthy();
    expect((new EventMatchPolicy)->create(basicUser()))->toBeFalsy();
});
