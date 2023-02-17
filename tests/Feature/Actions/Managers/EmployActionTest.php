<?php

use App\Actions\Managers\EmployAction;
use App\Enums\ManagerStatus;
use App\Models\Manager;

test('invoke employs an unemployed manager and redirects', function () {
    $manager = Manager::factory()->unemployed()->create();

    EmployAction::run($manager);

    expect($manager->fresh())
        ->employments->toHaveCount(1)
        ->status->toMatchObject(ManagerStatus::AVAILABLE);
});

test('invoke employs a future employed manager and redirects', function () {
    $manager = Manager::factory()->withFutureEmployment()->create();
    $startDate = $manager->employments->first()->started_at;

    EmployAction::run($manager);

    expect($manager->fresh())
        ->currentEmployment->started_at->toBeLessThan($startDate)
        ->status->toMatchObject(ManagerStatus::AVAILABLE);
});

test('invoke employs a released manager and redirects', function () {
    $manager = Manager::factory()->released()->create();

    EmployAction::run($manager);

    expect($manager->fresh())
        ->employments->toHaveCount(2)
        ->status->toMatchObject(ManagerStatus::AVAILABLE);
});
