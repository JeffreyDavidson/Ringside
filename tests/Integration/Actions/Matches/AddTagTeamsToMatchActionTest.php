<?php

declare(strict_types=1);

use App\Actions\Matches\AddTagTeamsToMatchAction;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Models\Matches\EventMatch;
use App\Models\TagTeams\TagTeam;

test('it rejects match assignment when no tag team is available', function () {
    $match = EventMatch::factory()->create();
    $tagTeams = TagTeam::factory()->retired()->count(2)->create();

    expect(fn () => resolve(AddTagTeamsToMatchAction::class)->handle($match, $tagTeams, 1))
        ->toThrow(EntityNotAvailableException::class, 'No eligible tag teams were provided for match assignment.');
});
