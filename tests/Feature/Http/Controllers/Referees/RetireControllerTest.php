<?php

use App\Enums\RefereeStatus;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\RetireController;
use App\Models\Referee;

test('invoke retires a retirable referee and redirects', function ($factoryState) {
    $referee = Referee::factory()->$factoryState()->create();

    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect($referee->fresh())
        ->retirements->toHaveCount(1)
        ->status->toBe(RefereeStatus::RETIRED);
})->with([
    'bookable',
    'injured',
    'suspended',
]);

test('a basic user cannot retire a bookable referee', function () {
    $referee = Referee::factory()->bookable()->create();

    $this->actingAs(basicUser())
        ->patch(action([RetireController::class], $referee))
        ->assertForbidden();
});

test('a guest cannot suspend a bookable referee', function () {
    $referee = Referee::factory()->bookable()->create();

    $this->patch(action([RetireController::class], $referee))
        ->assertRedirect(route('login'));
});

test('invoke throws exception for retiring a non retirable referee', function ($factoryState) {
    $this->withoutExceptionHandling();

    $referee = Referee::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $referee));
})->throws(CannotBeRetiredException::class)->with([
    'retired',
    'withFutureEmployment',
    'released',
    'unemployed',
]);
