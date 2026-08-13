<?php

declare(strict_types=1);

use App\Actions\Matches\DeleteAction;
use App\Models\Matches\EventMatch;

test('it deletes a match', function () {
    $eventMatch = EventMatch::factory()->create();

    resolve(DeleteAction::class)->handle($eventMatch);

    $this->assertModelMissing($eventMatch);
});
