<?php

use App\Actions\Referees\ReleaseAction;
use App\Enums\RefereeStatus;
use App\Exceptions\CannotBeReleasedException;
use App\Models\Referee;

test('invoke releases a available referee and redirects', function () {
    $referee = Referee::factory()->bookable()->create();

    ReleaseAction::run($referee);

    expect($referee->fresh())
        ->employments->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(RefereeStatus::RELEASED);
});

test('invoke releases an injured referee and redirects', function () {
    $referee = Referee::factory()->injured()->create();

    ReleaseAction::run($referee);

    expect($referee->fresh())
        ->injuries->last()->ended_at->not->toBeNull()
        ->employments->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(RefereeStatus::RELEASED);
});

test('invoke releases an suspended referee and redirects', function () {
    $referee = Referee::factory()->suspended()->create();

    ReleaseAction::run($referee);

    expect($referee->fresh())
        ->suspensions->last()->ended_at->not->toBeNull()
        ->employments->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(RefereeStatus::RELEASED);
});

test('invoke throws an exception for releasing a non releasable referee', function ($factoryState) {
    $this->withoutExceptionHandling();

    $referee = Referee::factory()->{$factoryState}()->create();

    ReleaseAction::run($referee);
})->throws(CannotBeReleasedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'released',
    'retired',
]);
