<?php

use App\Enums\StableStatus;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Stables\StablesController;
use App\Http\Controllers\Stables\UnretireController;
use App\Models\Stable;

beforeEach(function () {
    $this->stable = Stable::factory()->retired()->create();
});

test('invoke unretires a retired stable and its members and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $this->stable))
        ->assertRedirect(action([StablesController::class, 'index']));

    expect($this->stable->fresh())
        ->retirements->last()->ended_at->not->toBeNull()
        ->status->toBe(StableStatus::ACTIVE);
});

test('a basic user cannot unretire a stable', function () {
    $this->actingAs(basicUser())
        ->patch(action([UnretireController::class], $this->stable))
        ->assertForbidden();
});

test('a guest cannot unretire a stable', function () {
    $this->patch(action([UnretireController::class], $this->stable))
        ->assertRedirect(route('login'));
});

test('invoke throws exception for unretiring a non unretirable stable', function ($factoryState) {
    $this->withoutExceptionHandling();

    $stable = Stable::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $stable));
})->throws(CannotBeUnretiredException::class)->with([
    'active',
    'withFutureActivation',
    'inactive',
    'unactivated',
]);
