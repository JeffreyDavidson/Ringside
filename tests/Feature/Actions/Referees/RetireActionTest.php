<?php

use App\Actions\Referees\RetireAction;
use App\Enums\RefereeStatus;
use App\Exceptions\CannotBeRetiredException;
use App\Models\Referee;

test('invoke retires a retirable referee', function ($factoryState) {
    $referee = Referee::factory()->$factoryState()->create();

    RetireAction::run($referee);

    expect($referee->fresh())
        ->retirements->toHaveCount(1)
        ->status->toMatchObject(RefereeStatus::RETIRED);
})->with([
    'bookable',
    'injured',
    'suspended',
    'released',
]);

test('invoke throws exception for retiring a non retirable referee', function ($factoryState) {
    $this->withoutExceptionHandling();

    $referee = Referee::factory()->{$factoryState}()->create();

    RetireAction::run($referee);
})->throws(CannotBeRetiredException::class)->with([
    'retired',
    'withFutureEmployment',
    'unemployed',
]);
