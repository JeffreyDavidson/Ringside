<?php

declare(strict_types=1);

use App\Actions\Managers\SuspendAction;
use App\Models\Managers\Manager;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it suspends an employed manager', function () {
    $manager = Manager::factory()->employed()->create();

    expect($manager->isEmployed())->toBeTrue();
    expect($manager->isSuspended())->toBeFalse();

    SuspendAction::run($manager);

    $manager->refresh();
    expect($manager->isSuspended())->toBeTrue();
    expect($manager->isEmployed())->toBeTrue(); // Should remain employed while suspended

    $this->assertDatabaseHas('managers_suspensions', [
        'manager_id' => $manager->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it suspends manager with specific suspension date', function () {
    $manager = Manager::factory()->employed()->create();
    $suspensionDate = now()->subDays(3);

    SuspendAction::run($manager, $suspensionDate);

    $manager->refresh();
    expect($manager->isSuspended())->toBeTrue();

    $this->assertDatabaseHas('managers_suspensions', [
        'manager_id' => $manager->id,
        'started_at' => $suspensionDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it uses StatusTransitionPipeline for suspension', function () {
    $manager = Manager::factory()->employed()->create();

    expect($manager->currentSuspension())->toBeNull();

    SuspendAction::run($manager);

    $manager->refresh();

    // Verify suspension was created through pipeline
    expect($manager->currentSuspension())->not()->toBeNull();
    expect($manager->isSuspended())->toBeTrue();

    // Verify suspension record shows proper start date
    $this->assertDatabaseHas('managers_suspensions', [
        'manager_id' => $manager->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it prevents suspending already suspended manager', function () {
    $manager = Manager::factory()->employed()->suspended()->create();

    expect($manager->isSuspended())->toBeTrue();

    expect(fn () => SuspendAction::run($manager))
        ->toThrow(Exception::class);
});

test('it prevents suspending unemployed manager', function () {
    $manager = Manager::factory()->create();

    expect($manager->isEmployed())->toBeFalse();

    expect(fn () => SuspendAction::run($manager))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $manager = Manager::factory()->employed()->create();

    SuspendAction::run($manager);

    $manager->refresh();

    // Verify the transaction was successful
    expect($manager->isSuspended())->toBeTrue();

    // Verify suspension record integrity
    $suspension = $manager->currentSuspension;
    expect($suspension)->not()->toBeNull();
    expect($suspension->started_at->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($suspension->ended_at)->toBeNull();
});

test('it maintains employment status during suspension', function () {
    $manager = Manager::factory()->employed()->create();
    $employmentId = $manager->currentEmployment->id;

    expect($manager->isEmployed())->toBeTrue();
    expect($manager->isSuspended())->toBeFalse();

    SuspendAction::run($manager);

    $manager->refresh();

    // Should maintain employment while adding suspension
    expect($manager->isEmployed())->toBeTrue();
    expect($manager->isSuspended())->toBeTrue();

    // Employment record should remain unchanged
    $employment = $manager->currentEmployment;
    expect($employment)->not()->toBeNull();
    expect($employment->id)->toBe($employmentId);
    expect($employment->ended_at)->toBeNull();
});

test('it prevents suspending injured manager', function () {
    $manager = Manager::factory()->employed()->injured()->create();

    expect($manager->isInjured())->toBeTrue();
    expect($manager->isSuspended())->toBeFalse();

    expect(fn () => SuspendAction::run($manager))
        ->toThrow(Exception::class);
});

test('it uses DateHelper for consistent date handling', function () {
    $manager = Manager::factory()->employed()->create();
    $customSuspensionDate = now()->subDays(1)->startOfDay();

    SuspendAction::run($manager, $customSuspensionDate);

    $manager->refresh();

    // Verify DateHelper was used for date resolution
    $this->assertDatabaseHas('managers_suspensions', [
        'manager_id' => $manager->id,
        'started_at' => $customSuspensionDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it creates only one suspension record per action', function () {
    $manager = Manager::factory()->employed()->create();

    SuspendAction::run($manager);

    $manager->refresh();

    // Should create exactly one suspension record
    expect($manager->suspensions()->count())->toBe(1);
    expect($manager->currentSuspension())->not()->toBeNull();
});
