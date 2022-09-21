<?php

use App\Enums\ManagerStatus;
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
        ->status->toMatchObject(ManagerStatus::AVAILABLE);
});

test('invoke employs a future employed manager and redirects', function () {
    $manager = Manager::factory()->withFutureEmployment()->create();
    $startDate = $manager->employments->first()->started_at;

    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($manager->fresh())
        ->currentEmployment->started_at->toBeLessThan($startDate)
        ->status->toMatchObject(ManagerStatus::AVAILABLE);
});

test('invoke employs a released manager and redirects', function () {
    $manager = Manager::factory()->released()->create();

    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($manager->fresh())
        ->employments->toHaveCount(2)
        ->status->toMatchObject(ManagerStatus::AVAILABLE);
});
