<?php

use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Wrestlers\UnretireController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->retired()->create();
});

test('invoke unretires a retired wrestler and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect($this->wrestler->fresh())
        ->retirements->last()->ended_at->not->toBeNull()
        ->status->toBe(WrestlerStatus::BOOKABLE);
});

test('a basic user cannot unretire a wrestler', function () {
    $this->actingAs(basicUser())
        ->patch(action([UnretireController::class], $this->wrestler))
        ->assertForbidden();
});

test('a guest cannot unretire a wrestler', function () {
    $this->patch(action([UnretireController::class], $this->wrestler))
        ->assertRedirect(route('login'));
});

test('invoke throws exception for unretiring a non unretirable wrestler', function ($factoryState) {
    $this->withoutExceptionHandling();

    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $wrestler));
})->throws(CannotBeUnretiredException::class)->with([
    'bookable',
    'withFutureEmployment',
    'injured',
    'released',
    'suspended',
    'unemployed',
]);
