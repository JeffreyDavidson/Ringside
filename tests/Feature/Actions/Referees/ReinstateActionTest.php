<?php

use App\Actions\Referees\ReinstateAction;
use App\Enums\RefereeStatus;
use App\Exceptions\CannotBeReinstatedException;
use App\Models\Referee;

test('invoke reinstates a suspended referee and redirects', function () {
    $referee = Referee::factory()->suspended()->create();

    ReinstateAction::run($referee);

    expect($referee->fresH())
        ->suspensions->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(RefereeStatus::BOOKABLE);
});

test('invoke throws exception for reinstating a non reinstatable referee', function ($factoryState) {
    $this->withoutExceptionHandling();

    $referee = Referee::factory()->{$factoryState}()->create();

    ReinstateAction::run($referee);
})->throws(CannotBeReinstatedException::class)->with([
    'bookable',
    'unemployed',
    'injured',
    'released',
    'withFutureEmployment',
    'retired',
]);
