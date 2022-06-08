<?php

use App\Enums\ManagerStatus;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\UnretireController;
use App\Models\Manager;

test('invoke unretires a retired manager and redirects', function () {
    $manager = Manager::factory()->retired()->create();

    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($manager->fresh())
        ->retirements->last()->ended_at->not->toBeNull()
        ->status->toBe(ManagerStatus::AVAILABLE);
});

test('a basic user cannot unretire a manager', function () {
    $manager = Manager::factory()->retired()->create();

    $this->actingAs(basicUser())
        ->patch(action([UnretireController::class], $manager))
        ->assertForbidden();
});

test('a guest cannot unretire a manager', function () {
    $manager = Manager::factory()->retired()->create();

    $this->patch(action([UnretireController::class], $manager))
        ->assertRedirect(route('login'));
});

test('invoke throws exception for unretiring a non unretirable manager', function ($factoryState) {
    $this->withoutExceptionHandling();

    $manager = Manager::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $manager));
})->throws(CannotBeUnretiredException::class)->with([
    'available',
    'withFutureEmployment',
    'injured',
    'released',
    'suspended',
    'unemployed',
]);
