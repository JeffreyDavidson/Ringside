<?php

declare(strict_types=1);

use App\Actions\Matches\AddRefereesToMatchAction;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Models\Matches\EventMatch;
use App\Models\Referees\Referee;

test('it rejects match assignment when no referee is available', function () {
    $match = EventMatch::factory()->create();
    $referees = Referee::factory()->retired()->count(2)->create();

    expect(fn () => resolve(AddRefereesToMatchAction::class)->handle($match, $referees))
        ->toThrow(EntityNotAvailableException::class, 'No eligible referees were provided for match assignment.');
});
