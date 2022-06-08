<?php

use App\Enums\ManagerStatus;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Managers\EmployController;
use App\Http\Controllers\Managers\ManagersController;
use App\Models\Manager;

test('invoke employs an unemployed manager and redirects', function () {
    $manager = Manager::factory()->unemployed()->create();

    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($manager->fresh())
        ->employments->toHaveCount(1)
        ->status->toBe(ManagerStatus::AVAILABLE);
});

test('invoke employs a future employed manager and redirects', function () {
    $manager = Manager::factory()->withFutureEmployment()->create();
    $startedAt = $manager->employments->first()->started_at;

    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($manager->fresh())
        ->currentEmployment->started_at->toBeLessThan($startedAt)
        ->status->toBe(ManagerStatus::AVAILABLE);
});

test('invoke employs a released manager and redirects', function () {
    $manager = Manager::factory()->released()->create();

    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($manager->fresh())
        ->employments->toHaveCount(2)
        ->status->toBe(ManagerStatus::AVAILABLE);
});

test('a basic user cannot employ a manager', function () {
    $manager = Manager::factory()->create();

    $this->actingAs(basicUser())
        ->patch(action([EmployController::class], $manager))
        ->assertForbidden();
});

test('a guest user cannot injure a manager', function () {
    $manager = Manager::factory()->create();

    $this->patch(action([EmployController::class], $manager))
        ->assertRedirect(route('login'));
});

test('invoke throws exception for injuring a non injurable manager', function ($factoryState) {
    $this->withoutExceptionHandling();

    $manager = Manager::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $manager));
})->throws(CannotBeEmployedException::class)->with([
    'suspended',
    'injured',
    'available',
    'retired',
]);
