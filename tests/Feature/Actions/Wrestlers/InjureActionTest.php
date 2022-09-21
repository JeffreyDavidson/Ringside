<?php

use App\Actions\Wrestlers\InjureAction;
use App\Models\Wrestler;

test('invoke injures a bookable wrestler and redirects', function () {
    $wrestler = Wrestler::factory()->create();

    InjureAction::run($wrestler);

    expect($this->wrestler->fresh())
        ->injuries->toHaveCount(1)
        ->status->toMatchObject(WrestlerStatus::INJURED);
});

test('injuring a bookable wrestler on a bookable tag team makes tag team unbookable', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $wrestler = $tagTeam->currentWrestlers()->first();

    InjureAction::run($wrestler);

    expect($wrestler->currentTagTeam->fresh())
        ->status->toMatchObject(TagTeamStatus::UNBOOKABLE);
});

test('invoke throws exception for injuring a non injurable wrestler', function ($factoryState) {
    $this->withoutExceptionHandling();

    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    InjureAction::run($wrestler);
})->throws(CannotBeInjuredException::class)->with([
    'unemployed',
    'suspended',
    'released',
    'withFutureEmployment',
    'retired',
    'injured',
]);
