<?php

use App\Enums\ManagerStatus;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\SuspendController;
use App\Models\Manager;

test('invoke suspends an available manager and redirects', function () {
    $manager = Manager::factory()->available()->create();

    $this->actingAs(administrator())
        ->patch(action([SuspendController::class], $manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($manager->fresh())
        ->suspensions->toHaveCount(1)
        ->status->toBe(ManagerStatus::SUSPENDED);
});

test('a basic user cannot suspend an available manager', function () {
    $manager = Manager::factory()->available()->create();

    $this->actingAs(basicUser())
        ->patch(action([SuspendController::class], $manager))
        ->assertForbidden();
});

test('a guest cannot suspend an available manager', function () {
    $manager = Manager::factory()->available()->create();

    $this->patch(action([SuspendController::class], $manager))
        ->assertRedirect(route('login'));
});

test('invoke throws exception for suspending a non suspendable manager', function ($factoryState) {
    $this->withoutExceptionHandling();

    $manager = Manager::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([SuspendController::class], $manager));
})->throws(CannotBeSuspendedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'injured',
    'released',
    'retired',
    'suspended',
]);
