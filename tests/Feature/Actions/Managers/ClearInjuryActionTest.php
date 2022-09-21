<?php

use App\Actions\Managers\ClearInjuryAction;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Models\Manager;
use Illuminate\Support\Carbon;

test('run makes injured manager not injured using current datetime', function () {
    $manager = Manager::factory()->injured()->create();
    $now = now();

    ClearInjuryAction::run($manager);

    expect($manager->fresh())
        ->isInjured()->toBeFalse()
        ->injuries->last()->ended_at->toEqual($now->toDateTimeString());
});

test('run makes injured manager not injured using specific datetime', function () {
    $manager = Manager::factory()->injured()->create();
    $recoveryDate = Carbon::parse('2022-05-27 12:00:00');

    ClearInjuryAction::run($manager, $recoveryDate);

    expect($manager->fresh())
        ->isInjured()->toBeFalse()
        ->injuries->last()->ended_at->toEqual($recoveryDate);
});

test('invoke throws exception for injuring a non injurable manager', function ($factoryState) {
    $this->withoutExceptionHandling();

    $manager = Manager::factory()->{$factoryState}()->create();

    ClearInjuryAction::run($manager);
})->throws(CannotBeClearedFromInjuryException::class)->with([
    'unemployed',
    'released',
    'withFutureEmployment',
    'available',
    'retired',
    'suspended',
]);
