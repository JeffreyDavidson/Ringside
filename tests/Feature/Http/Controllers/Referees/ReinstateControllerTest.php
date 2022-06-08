<?php

use App\Enums\RefereeStatus;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\ReinstateController;
use App\Models\Referee;

beforeEach(function () {
    $this->referee = Referee::factory()->suspended()->create();
});

test('invoke reinstates a suspended referee and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([ReinstateController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect($this->referee->fresh())
        ->suspensions->last()->ended_at->not->toBeNull()
        ->status->toBe(RefereeStatus::BOOKABLE);
});

test('a basic user cannot reinstate a suspended referee', function () {
    $this->actingAs(basicUser())
        ->patch(action([ReinstateController::class], $this->referee))
        ->assertForbidden();
});

test('a guest cannot reinstate a suspended referee', function () {
    $this->patch(action([ReinstateController::class], $this->referee))
        ->assertRedirect(route('login'));
});

test('invoke throws exception for reinstating a non reinstatable referee', function ($factoryState) {
    $this->withoutExceptionHandling();

    $referee = Referee::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([ReinstateController::class], $referee));
})->throws(CannotBeReinstatedException::class)->with([
    'bookable',
    'unemployed',
    'injured',
    'released',
    'withFutureEmployment',
    'retired',
]);
