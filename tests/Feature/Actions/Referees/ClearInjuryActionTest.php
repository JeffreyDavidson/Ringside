<?php

use App\Actions\Referees\ClearInjuryAction;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Models\Referee;

test('invoke marks an injured referee as being cleared from injury and redirects', function () {
    $referee = Referee::factory()->injured()->create();
    $now = now();

    ClearInjuryAction::run($referee);

    expect($referee->fresh())
        ->isInjured()->toBeFalse()
        ->injuries->last()->ended_at->toEqual($now->toDateTimeString());
});

test('invoke throws exception for injuring a non injurable referee', function ($factoryState) {
    $this->withoutExceptionHandling();

    $referee = Referee::factory()->{$factoryState}()->create();

    ClearInjuryAction::run($referee);
})->throws(CannotBeClearedFromInjuryException::class)->with([
    'unemployed',
    'released',
    'withFutureEmployment',
    'bookable',
    'retired',
    'suspended',
]);
