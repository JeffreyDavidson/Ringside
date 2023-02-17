<?php

use App\Actions\Referees\InjureAction;
use App\Enums\RefereeStatus;
use App\Exceptions\CannotBeInjuredException;
use App\Models\Referee;

test('invoke injures an available referee and redirects', function () {
    $referee = Referee::factory()->bookable()->create();

    InjureAction::run($referee);

    expect($referee->fresh())
        ->injuries->toHaveCount(1)
        ->status->toMatchObject(RefereeStatus::INJURED);
});

test('invoke throws exception for injuring a non injurable referee', function ($factoryState) {
    $this->withoutExceptionHandling();

    $referee = Referee::factory()->{$factoryState}()->create();

    InjureAction::run($referee);
})->throws(CannotBeInjuredException::class)->with([
    'unemployed',
    'suspended',
    'released',
    'withFutureEmployment',
    'retired',
    'injured',
]);
