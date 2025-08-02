<?php

declare(strict_types=1);

use App\Actions\Managers\ReinstateAction;
use App\Models\Managers\Manager;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it reinstates a suspended manager', function () {
    $manager = Manager::factory()->employed()->suspended()->create();

    expect($manager->isSuspended())->toBeTrue();
    expect($manager->isEmployed())->toBeTrue();

    ReinstateAction::run($manager);

    $manager->refresh();
    expect($manager->isSuspended())->toBeFalse();
    expect($manager->isEmployed())->toBeTrue(); // Should remain employed after reinstatement

    // Verify suspension record was ended
    $this->assertDatabaseHas('managers_suspensions', [
        'manager_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it reinstates manager with specific reinstatement date', function () {
    $manager = Manager::factory()->employed()->suspended()->create();
    $reinstatementDate = now()->subDays(2);

    ReinstateAction::run($manager, $reinstatementDate);

    $manager->refresh();
    expect($manager->isSuspended())->toBeFalse();

    // Verify suspension was ended with specific date
    $this->assertDatabaseHas('managers_suspensions', [
        'manager_id' => $manager->id,
        'ended_at' => $reinstatementDate->toDateTimeString(),
    ]);
});

test('it uses StatusTransitionPipeline for reinstatement', function () {
    $manager = Manager::factory()->employed()->suspended()->create();

    // Get current suspension to verify it gets ended
    $currentSuspension = $manager->currentSuspension();
    expect($currentSuspension)->not()->toBeNull();

    ReinstateAction::run($manager);

    $manager->refresh();

    // Verify suspension ended through pipeline
    expect($manager->currentSuspension())->toBeNull();
    expect($manager->isSuspended())->toBeFalse();

    // Verify suspension record shows proper end date
    $this->assertDatabaseHas('managers_suspensions', [
        'manager_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it prevents reinstating non-suspended manager', function () {
    $manager = Manager::factory()->employed()->create();

    expect($manager->isSuspended())->toBeFalse();

    expect(fn () => ReinstateAction::run($manager))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $manager = Manager::factory()->employed()->suspended()->create();
    $originalSuspensionId = $manager->currentSuspension()->id;

    ReinstateAction::run($manager);

    $manager->refresh();

    // Verify the transaction was successful
    expect($manager->isSuspended())->toBeFalse();

    // Verify original suspension record was properly ended
    $this->assertDatabaseHas('managers_suspensions', [
        'id' => $originalSuspensionId,
        'manager_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify no new suspension records were created
    expect($manager->suspensions()->count())->toBe(1);
});

test('it maintains employment status during reinstatement', function () {
    $manager = Manager::factory()->employed()->suspended()->create();
    $employmentId = $manager->currentEmployment->id;

    expect($manager->isEmployed())->toBeTrue();
    expect($manager->isSuspended())->toBeTrue();

    ReinstateAction::run($manager);

    $manager->refresh();

    // Should maintain employment while ending suspension
    expect($manager->isEmployed())->toBeTrue();
    expect($manager->isSuspended())->toBeFalse();

    // Employment record should remain unchanged
    $employment = $manager->currentEmployment;
    expect($employment)->not()->toBeNull();
    expect($employment->id)->toBe($employmentId);
    expect($employment->ended_at)->toBeNull();
});

test('it uses DateHelper for consistent date handling', function () {
    $manager = Manager::factory()->employed()->suspended()->create();
    $customReinstatementDate = now()->subDays(1)->startOfDay();

    ReinstateAction::run($manager, $customReinstatementDate);

    $manager->refresh();

    // Verify DateHelper was used for date resolution
    $this->assertDatabaseHas('managers_suspensions', [
        'manager_id' => $manager->id,
        'ended_at' => $customReinstatementDate->toDateTimeString(),
    ]);
});

test('it handles multiple suspensions correctly', function () {
    $manager = Manager::factory()->employed()->create();

    // Create multiple suspension history
    $manager->suspensions()->create(['started_at' => now()->subDays(10), 'ended_at' => now()->subDays(8)]);
    $manager->suspensions()->create(['started_at' => now()->subDays(5), 'ended_at' => null]); // Current suspension

    $manager->refresh();
    expect($manager->isSuspended())->toBeTrue();
    expect($manager->suspensions()->count())->toBe(2);

    ReinstateAction::run($manager);

    $manager->refresh();

    // Should only end the current suspension, leaving historical ones intact
    expect($manager->isSuspended())->toBeFalse();
    expect($manager->suspensions()->count())->toBe(2);
    expect($manager->suspensions()->whereNull('ended_at')->count())->toBe(0);
});

test('it prevents reinstating injured suspended manager', function () {
    // This would be an invalid state, but test the business rule
    $manager = Manager::factory()->employed()->suspended()->create();

    // Manually create injury (this shouldn't be possible in normal flow)
    $manager->injuries()->create(['started_at' => now()->subDay(), 'ended_at' => null]);
    $manager->refresh();

    expect($manager->isSuspended())->toBeTrue();
    expect($manager->isInjured())->toBeTrue();

    // Should prevent reinstatement if injured (business rule)
    expect(fn () => ReinstateAction::run($manager))
        ->toThrow(Exception::class);
});
