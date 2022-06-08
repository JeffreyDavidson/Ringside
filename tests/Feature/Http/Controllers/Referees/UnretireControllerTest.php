<?php

use App\Enums\RefereeStatus;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\UnretireController;
use App\Models\Referee;

beforeEach(function () {
    $this->referee = Referee::factory()->retired()->create();
});

test('invoke unretires a retired referee and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect($this->referee->fresh())
        ->retirements->last()->ended_at->not->toBeNull()
        ->status->toBe(RefereeStatus::BOOKABLE);
});

test('a basic user cannot unretire a referee', function () {
    $this->actingAs(basicUser())
        ->patch(action([UnretireController::class], $this->referee))
        ->assertForbidden();
});

test('a guest cannot unretire a referee', function () {
    $this->patch(action([UnretireController::class], $this->referee))
        ->assertRedirect(route('login'));
});

test('invoke throws exception for unretiring a non unretirable referee', function ($factoryState) {
    $this->withoutExceptionHandling();

    $referee = Referee::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $referee));
})->throws(CannotBeUnretiredException::class)->with([
    'bookable',
    'withFutureEmployment',
    'injured',
    'released',
    'suspended',
    'unemployed',
]);
