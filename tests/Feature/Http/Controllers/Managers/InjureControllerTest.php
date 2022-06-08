<?php

use App\Enums\ManagerStatus;
use App\Exceptions\CannotBeInjuredException;
use App\Http\Controllers\Managers\InjureController;
use App\Http\Controllers\Managers\ManagersController;
use App\Models\Manager;

beforeEach(function () {
    $this->manager = Manager::factory()->available()->create();
});

test('invoke injures an available manager and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([InjureController::class], $this->manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($this->manager->fresh())
        ->injuries->toHaveCount(1)
        ->status->toBe(ManagerStatus::INJURED);
});

test('a basic user cannot injure an available manager', function () {
    $this->actingAs(basicUser())
        ->patch(action([InjureController::class], $this->manager))
        ->assertForbidden();
});

test('a guest user cannot injure an available manager', function () {
    $this->patch(action([InjureController::class], $this->manager))
        ->assertRedirect(route('login'));
});

test('invoke throws exception for injuring a non injurable manager', function ($factoryState) {
    $this->withoutExceptionHandling();

    $manager = Manager::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([InjureController::class], $manager));
})->throws(CannotBeInjuredException::class)->with([
    'unemployed',
    'suspended',
    'released',
    'withFutureEmployment',
    'retired',
    'injured',
]);
