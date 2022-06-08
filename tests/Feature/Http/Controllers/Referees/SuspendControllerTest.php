<?php

use App\Enums\RefereeStatus;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\SuspendController;
use App\Models\Referee;

beforeEach(function () {
    $this->referee = Referee::factory()->bookable()->create();
});

test('invoke suspends a bookable referee and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([SuspendController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect($this->referee->fresh())
        ->suspensions->toHaveCount(1)
        ->status->toBe(RefereeStatus::SUSPENDED);
});

test('a basic user cannot suspend a bookable referee', function () {
    $this->actingAs(basicUser())
        ->patch(action([SuspendController::class], $this->referee))
        ->assertForbidden();
});

test('a guest cannot suspend a bookable referee', function () {
    $this->patch(action([SuspendController::class], $this->referee))
        ->assertRedirect(route('login'));
});

test('invoke throws exception for suspending a non suspendable referee', function ($factoryState) {
    $this->withoutExceptionHandling();

    $referee = Referee::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([SuspendController::class], $referee));
})->throws(CannotBeSuspendedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'injured',
    'released',
    'retired',
    'suspended',
]);
