<?php

declare(strict_types=1);

use App\Actions\Managers\EmployAction;
use App\Models\Managers\Manager;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it employs an unemployed manager', function () {
    $manager = Manager::factory()->create();

    expect($manager->isEmployed())->toBeFalse();

    EmployAction::run($manager);

    $manager->refresh();
    expect($manager->isEmployed())->toBeTrue();

    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it employs manager with specific employment date', function () {
    $manager = Manager::factory()->create();
    $employmentDate = now()->subDays(30);

    EmployAction::run($manager, $employmentDate);

    $manager->refresh();
    expect($manager->isEmployed())->toBeTrue();

    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it employs retired manager and ends retirement', function () {
    $manager = Manager::factory()->retired()->create();

    expect($manager->isRetired())->toBeTrue();
    expect($manager->isEmployed())->toBeFalse();

    EmployAction::run($manager);

    $manager->refresh();
    expect($manager->isEmployed())->toBeTrue();
    expect($manager->isRetired())->toBeFalse();

    // Retirement should be ended
    $this->assertDatabaseHas('managers_retirements', [
        'manager_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Employment should be created
    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it employs suspended manager and ends suspension', function () {
    $manager = Manager::factory()->suspended()->create();

    expect($manager->isSuspended())->toBeTrue();
    expect($manager->isEmployed())->toBeTrue();

    expect(fn () => EmployAction::run($manager))
        ->toThrow(Exception::class);
});

test('it employs injured manager and ends injury', function () {
    $manager = Manager::factory()->injured()->create();

    expect($manager->isInjured())->toBeTrue();
    expect($manager->isEmployed())->toBeTrue();

    expect(fn () => EmployAction::run($manager))
        ->toThrow(Exception::class);
});

test('it prevents employing already employed manager', function () {
    $manager = Manager::factory()->employed()->create();

    expect($manager->isEmployed())->toBeTrue();

    expect(fn () => EmployAction::run($manager))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $manager = Manager::factory()->create();

    expect($manager->isEmployed())->toBeFalse();

    EmployAction::run($manager);

    // Verify the transaction was successful
    $manager->refresh();
    expect($manager->isEmployed())->toBeTrue();
    expect($manager->status->value)->toBe('employed');

    // Verify employment record integrity
    $employment = $manager->currentEmployment;
    expect($employment)->not()->toBeNull();
    expect($employment->started_at->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($employment->ended_at)->toBeNull();
});

test('it uses StatusTransitionPipeline for consistent status handling', function () {
    $manager = Manager::factory()->create();

    EmployAction::run($manager);

    $manager->refresh();

    // Should properly update both employment record and status field
    expect($manager->isEmployed())->toBeTrue();
    expect($manager->status->value)->toBe('employed');

    // Should create proper employment record
    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager->id,
        'ended_at' => null,
    ]);
});
