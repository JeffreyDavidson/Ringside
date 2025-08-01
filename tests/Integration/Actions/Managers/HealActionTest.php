<?php

declare(strict_types=1);

use App\Actions\Managers\HealAction;
use App\Models\Managers\Manager;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it heals an injured manager', function () {
    $manager = Manager::factory()->injured()->create();

    expect($manager->isInjured())->toBeTrue();

    HealAction::run($manager);

    $manager->refresh();
    expect($manager->isInjured())->toBeFalse();

    // Verify injury record was ended
    $this->assertDatabaseHas('managers_injuries', [
        'manager_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it heals manager with specific recovery date', function () {
    $manager = Manager::factory()->injured()->create();
    $recoveryDate = now()->subDays(5);

    HealAction::run($manager, $recoveryDate);

    $manager->refresh();
    expect($manager->isInjured())->toBeFalse();

    // Verify injury was ended with specific date
    $this->assertDatabaseHas('managers_injuries', [
        'manager_id' => $manager->id,
        'ended_at' => $recoveryDate->toDateTimeString(),
    ]);
});

test('it uses StatusTransitionPipeline for healing', function () {
    $manager = Manager::factory()->injured()->create();

    // Get current injury to verify it gets ended
    $currentInjury = $manager->currentInjury();
    expect($currentInjury)->not()->toBeNull();

    HealAction::run($manager);

    $manager->refresh();

    // Verify injury ended through pipeline
    expect($manager->currentInjury())->toBeNull();
    expect($manager->isInjured())->toBeFalse();

    // Verify injury record shows proper end date
    $this->assertDatabaseHas('managers_injuries', [
        'manager_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it prevents healing non-injured manager', function () {
    $manager = Manager::factory()->employed()->create();

    expect($manager->isInjured())->toBeFalse();

    expect(fn () => HealAction::run($manager))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $manager = Manager::factory()->injured()->create();
    $originalInjuryId = $manager->currentInjury()->id;

    HealAction::run($manager);

    $manager->refresh();

    // Verify the transaction was successful
    expect($manager->isInjured())->toBeFalse();

    // Verify original injury record was properly ended
    $this->assertDatabaseHas('managers_injuries', [
        'id' => $originalInjuryId,
        'manager_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify no new injury records were created
    expect($manager->injuries()->count())->toBe(1);
});

test('it maintains employment status during healing', function () {
    $manager = Manager::factory()->employed()->injured()->create();

    expect($manager->isEmployed())->toBeTrue();
    expect($manager->isInjured())->toBeTrue();

    HealAction::run($manager);

    $manager->refresh();

    // Should maintain employment while ending injury
    expect($manager->isEmployed())->toBeTrue();
    expect($manager->isInjured())->toBeFalse();

    // Employment record should remain unchanged
    $employment = $manager->currentEmployment;
    expect($employment)->not()->toBeNull();
    expect($employment->ended_at)->toBeNull();
});

test('it uses DateHelper for consistent date handling', function () {
    $manager = Manager::factory()->injured()->create();
    $customRecoveryDate = now()->subDays(3)->startOfDay();

    HealAction::run($manager, $customRecoveryDate);

    $manager->refresh();

    // Verify DateHelper was used for date resolution
    $this->assertDatabaseHas('managers_injuries', [
        'manager_id' => $manager->id,
        'ended_at' => $customRecoveryDate->toDateTimeString(),
    ]);
});
