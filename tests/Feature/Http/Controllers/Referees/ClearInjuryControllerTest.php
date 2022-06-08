<?php

use App\Enums\RefereeStatus;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Referees\ClearInjuryController;
use App\Http\Controllers\Referees\RefereesController;
use App\Models\Referee;

beforeEach(function () {
    $this->referee = Referee::factory()->injured()->create();
});

test('invoke marks an injured referee as being cleared from injury and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([ClearInjuryController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect($this->referee->fresh())
        ->injuries->last()->ended_at->not->toBeNull()
        ->status->toBe(RefereeStatus::BOOKABLE);
});

test('a basic user cannot mark an injured referee as cleared', function () {
    $this->actingAs(basicUser())
        ->patch(action([ClearInjuryController::class], $this->referee))
        ->assertForbidden();
});

test('a guest cannot mark an injured referee as cleared', function () {
    $this->patch(action([ClearInjuryController::class], $this->referee))
        ->assertRedirect(route('login'));
});

test('invoke throws exception for injuring a non injurable referee', function ($factoryState) {
    $this->withoutExceptionHandling();

    $referee = Referee::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([ClearInjuryController::class], $referee));
})->throws(CannotBeClearedFromInjuryException::class)->with([
    'unemployed',
    'released',
    'withFutureEmployment',
    'bookable',
    'retired',
    'suspended',
]);
