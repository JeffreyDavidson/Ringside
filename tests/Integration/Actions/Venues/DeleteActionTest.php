<?php

declare(strict_types=1);

use App\Actions\Venues\DeleteAction;
use App\Models\Events\Venue;

test('it soft deletes a venue through a locked record', function () {
    $venue = Venue::factory()->create();
    $staleVenue = clone $venue;

    $venue->refresh();
    resolve(DeleteAction::class)->handle($staleVenue);

    expect(Venue::query()->find($venue->getKey()))->toBeNull()
        ->and(Venue::withTrashed()->find($venue->getKey()))->not->toBeNull();
});
