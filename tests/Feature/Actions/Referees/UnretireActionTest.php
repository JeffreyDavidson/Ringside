<?php

use App\Actions\Referees\UnretireAction;
use App\Enums\RefereeStatus;
use App\Exceptions\CannotBeUnretiredException;
use App\Models\Referee;

test('invoke calls unretire action and redirects', function () {
    $referee = Referee::factory()->retired()->create();

    UnretireAction::run($referee);

    expect($referee->fresh())
        ->retirements->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(RefereeStatus::BOOKABLE);
});

test('invoke throws exception for unretiring a non unretirable referee', function ($factoryState) {
    $this->withoutExceptionHandling();

    $referee = Referee::factory()->{$factoryState}()->create();

    UnretireAction::run($referee);
})->throws(CannotBeUnretiredException::class)->with([
    'bookable',
    'withFutureEmployment',
    'injured',
    'released',
    'suspended',
    'unemployed',
]);
