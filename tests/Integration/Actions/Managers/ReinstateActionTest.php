<?php

declare(strict_types=1);

use App\Actions\Managers\ReinstateAction;
use App\Exceptions\Roster\Individuals\CannotBeReinstatedException;
use App\Models\Roster\Managers\Manager;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it reinstates a suspended manager', function () {
    $manager = Manager::factory()->suspended()->create();

    expect($manager->isSuspended())->toBeTrue();
    expect($manager->isEmployed())->toBeTrue();

    resolve(ReinstateAction::class)->handle($manager);

    $manager->refresh();
    expect($manager->isSuspended())->toBeFalse();
    expect($manager->isEmployed())->toBeTrue(); // Should remain employed after reinstatement

    // Verify suspension record was ended
    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $manager->id,
        'suspendable_type' => $manager->getMorphClass(),
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it prevents reinstating an injured manager', function () {
    $manager = Manager::factory()->injured()->create();
    $injuryId = $manager->currentInjury()->firstOrFail()->id;

    expect(fn () => resolve(ReinstateAction::class)->handle($manager))
        ->toThrow(CannotBeReinstatedException::class);

    $manager->refresh();
    expect($manager->isInjured())->toBeTrue();
    $this->assertDatabaseHas('injuries', [
        'id' => $injuryId,
        'ended_at' => null,
    ]);
});

test('it reinstates manager with specific reinstatement date', function () {
    $manager = Manager::factory()->suspended()->create();
    $reinstatementDate = now()->subDays(2);

    resolve(ReinstateAction::class)->handle($manager, $reinstatementDate);

    $manager->refresh();
    expect($manager->isSuspended())->toBeFalse();

    // Verify suspension was ended with specific date
    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $manager->id,
        'suspendable_type' => $manager->getMorphClass(),
        'ended_at' => $reinstatementDate->toDateTimeString(),
    ]);
});

test('it persists the reinstatement lifecycle', function () {
    $manager = Manager::factory()->suspended()->create();

    // Get current suspension to verify it gets ended
    $currentSuspension = $manager->currentSuspension()->firstOrFail();

    resolve(ReinstateAction::class)->handle($manager);

    $manager->refresh();

    // Verify suspension period was ended
    expect($manager->currentSuspension)->toBeNull();
    expect($manager->isSuspended())->toBeFalse();

    // Verify suspension record shows proper end date
    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $manager->id,
        'suspendable_type' => $manager->getMorphClass(),
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it prevents reinstating non-suspended manager', function () {
    $manager = Manager::factory()->employed()->create();

    expect($manager->isSuspended())->toBeFalse();

    expect(fn () => resolve(ReinstateAction::class)->handle($manager))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $manager = Manager::factory()->suspended()->create();
    $originalSuspensionId = $manager->currentSuspension()->firstOrFail()->id;

    resolve(ReinstateAction::class)->handle($manager);

    $manager->refresh();

    // Verify the transaction was successful
    expect($manager->isSuspended())->toBeFalse();

    // Verify original suspension record was properly ended
    $this->assertDatabaseHas('suspensions', [
        'id' => $originalSuspensionId,
        'suspendable_id' => $manager->id,
        'suspendable_type' => $manager->getMorphClass(),
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify no new suspension records were created
    expect($manager->suspensions()->count())->toBe(1);
});

test('it maintains employment status during reinstatement', function () {
    $manager = Manager::factory()->suspended()->create();
    $employmentId = $manager->currentEmployment()->firstOrFail()->id;

    expect($manager->isEmployed())->toBeTrue();
    expect($manager->isSuspended())->toBeTrue();

    resolve(ReinstateAction::class)->handle($manager);

    $manager->refresh();

    // Should maintain employment while ending suspension
    expect($manager->isEmployed())->toBeTrue();
    expect($manager->isSuspended())->toBeFalse();

    // Employment record should remain unchanged
    $employment = $manager->currentEmployment()->firstOrFail();
    expect($employment->id)->toBe($employmentId);
    expect($employment->ended_at)->toBeNull();
});

test('it uses DateHelper for consistent date handling', function () {
    $manager = Manager::factory()->suspended()->create();
    $customReinstatementDate = now()->subDays(1)->startOfDay();

    resolve(ReinstateAction::class)->handle($manager, $customReinstatementDate);

    $manager->refresh();

    // Verify DateHelper was used for date resolution
    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $manager->id,
        'suspendable_type' => $manager->getMorphClass(),
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

    resolve(ReinstateAction::class)->handle($manager);

    $manager->refresh();

    // Should only end the current suspension, leaving historical ones intact
    expect($manager->isSuspended())->toBeFalse();
    expect($manager->suspensions()->count())->toBe(2);
    expect($manager->suspensions()->whereNull('ended_at')->count())->toBe(0);
});
