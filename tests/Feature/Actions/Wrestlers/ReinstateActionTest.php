<?php

test('invoke reinstates a suspended wrestler and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([ReinstateController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect($this->wrestler->fresh())
        ->suspensions->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(WrestlerStatus::BOOKABLE);
});

test('reinstating a suspended wrestler on an unbookable tag team makes tag team bookable', function () {
    $tagTeam = TagTeam::factory()
        ->hasAttached($suspendedWrestler = Wrestler::factory()->suspended()->create())
        ->hasAttached(Wrestler::factory()->bookable())
        ->has(Employment::factory()->started(Carbon::yesterday()))
        ->create();

    $this->actingAs(administrator())
        ->patch(action([ReinstateController::class], $suspendedWrestler));

    expect($tagTeam->fresh())
        ->status->toMatchObject(TagTeamStatus::BOOKABLE);
});

test('invoke throws exception for reinstating a non reinstatable wrestler', function ($factoryState) {
    $this->withoutExceptionHandling();

    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([ReinstateController::class], $wrestler));
})->throws(CannotBeReinstatedException::class)->with([
    'bookable',
    'unemployed',
    'injured',
    'released',
    'withFutureEmployment',
    'retired',
]);
