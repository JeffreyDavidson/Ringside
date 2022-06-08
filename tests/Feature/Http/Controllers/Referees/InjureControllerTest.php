<?php

use App\Enums\RefereeStatus;
use App\Exceptions\CannotBeInjuredException;
use App\Http\Controllers\Referees\InjureController;
use App\Http\Controllers\Referees\RefereesController;
use App\Models\Referee;

beforeEach(function () {
    $this->referee = Referee::factory()->bookable()->create();
});

test('invoke injures a bookable referee and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([InjureController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect($this->referee->fresh())
        ->injuries->toHaveCount(1)
        ->status->toBe(RefereeStatus::INJURED);
});

test('a basic user cannot injure a bookable referee', function () {
    $this->actingAs(basicUser())
        ->patch(action([InjureController::class], $this->referee))
        ->assertForbidden();
});

test('a guest user cannot injure a bookable referee', function () {
    $this->patch(action([InjureController::class], $this->referee))
        ->assertRedirect(route('login'));
});

test('invoke throws exception for injuring a non injurable referee', function ($factoryState) {
    $this->withoutExceptionHandling();

    $referee = Referee::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([InjureController::class], $referee));
})->throws(CannotBeInjuredException::class)->with([
    'unemployed',
    'suspended',
    'released',
    'withFutureEmployment',
    'retired',
    'injured',
]);
