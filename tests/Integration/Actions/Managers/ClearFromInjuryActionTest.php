<?php

declare(strict_types=1);

use App\Actions\Managers\ClearFromInjuryAction;
use App\Models\Roster\Managers\Manager;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it clears an injured manager', function () {
    $manager = Manager::factory()->injured()->create();

    expect($manager->isInjured())->toBeTrue();

    resolve(ClearFromInjuryAction::class)->handle($manager);

    $manager->refresh();
    expect($manager->isInjured())->toBeFalse();

    // Verify injury record was ended
    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $manager->id,
        'injurable_type' => $manager->getMorphClass(),
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it clears manager from injury with specific recovery date', function () {
    $manager = Manager::factory()->injured()->create();
    $recoveryDate = now()->subDays(5);

    resolve(ClearFromInjuryAction::class)->handle($manager, $recoveryDate);

    $manager->refresh();
    expect($manager->isInjured())->toBeFalse();

    // Verify injury was ended with specific date
    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $manager->id,
        'injurable_type' => $manager->getMorphClass(),
        'ended_at' => $recoveryDate->toDateTimeString(),
    ]);
});

test('it persists the injury clearance lifecycle', function () {
    $manager = Manager::factory()->injured()->create();

    // Get current injury to verify it gets ended
    $currentInjury = $manager->currentInjury()->firstOrFail();

    resolve(ClearFromInjuryAction::class)->handle($manager);

    $manager->refresh();

    // Verify injury period was ended
    expect($manager->currentInjury)->toBeNull();
    expect($manager->isInjured())->toBeFalse();

    // Verify injury record shows proper end date
    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $manager->id,
        'injurable_type' => $manager->getMorphClass(),
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it prevents clearing non-injured manager', function () {
    $manager = Manager::factory()->employed()->create();

    expect($manager->isInjured())->toBeFalse();

    expect(fn () => resolve(ClearFromInjuryAction::class)->handle($manager))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $manager = Manager::factory()->injured()->create();
    $originalInjuryId = $manager->currentInjury()->firstOrFail()->id;

    resolve(ClearFromInjuryAction::class)->handle($manager);

    $manager->refresh();

    // Verify the transaction was successful
    expect($manager->isInjured())->toBeFalse();

    // Verify original injury record was properly ended
    $this->assertDatabaseHas('injuries', [
        'id' => $originalInjuryId,
        'injurable_id' => $manager->id,
        'injurable_type' => $manager->getMorphClass(),
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify no new injury records were created
    expect($manager->injuries()->count())->toBe(1);
});

test('it maintains employment status during injury clearance', function () {
    $manager = Manager::factory()->injured()->create();

    expect($manager->currentEmployment()->exists())->toBeTrue();
    expect($manager->isInjured())->toBeTrue();

    resolve(ClearFromInjuryAction::class)->handle($manager);

    $manager->refresh();

    // Should maintain employment while ending injury
    expect($manager->currentEmployment()->exists())->toBeTrue();
    expect($manager->isInjured())->toBeFalse();

    // Employment record should remain unchanged
    $employment = $manager->currentEmployment()->firstOrFail();
    expect($employment->ended_at)->toBeNull();
});

test('it uses the provided date', function () {
    $manager = Manager::factory()->injured()->create();
    $customRecoveryDate = now()->subDays(3)->startOfDay();

    resolve(ClearFromInjuryAction::class)->handle($manager, $customRecoveryDate);

    $manager->refresh();

    // Verify the provided date was persisted
    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $manager->id,
        'injurable_type' => $manager->getMorphClass(),
        'ended_at' => $customRecoveryDate->toDateTimeString(),
    ]);
});
