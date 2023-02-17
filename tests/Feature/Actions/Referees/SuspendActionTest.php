<?php

use App\Actions\Referees\SuspendAction;
use App\Enums\RefereeStatus;
use App\Exceptions\CannotBeSuspendedException;
use App\Models\Referee;

test('invoke suspends an available referee and redirects', function () {
    $referee = Referee::factory()->bookable()->create();

    SuspendAction::run($referee);

    expect($referee->fresh())
        ->suspensions->toHaveCount(1)
        ->status->toMatchObject(RefereeStatus::SUSPENDED);
});

test('invoke throws exception for suspending a non suspendable referee', function ($factoryState) {
    $this->withoutExceptionHandling();

    $referee = Referee::factory()->{$factoryState}()->create();

    SuspendAction::run($referee);
})->throws(CannotBeSuspendedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'injured',
    'released',
    'retired',
    'suspended',
]);
