<?php

declare(strict_types=1);

use App\Actions\Managers\UnretireAction;
use App\Models\Roster\Managers\Manager;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it unretires a retired manager', function () {
    $manager = Manager::factory()->retired()->create();

    expect($manager->isRetired())->toBeTrue();
    expect($manager->currentEmployment()->exists())->toBeFalse();

    resolve(UnretireAction::class)->handle($manager);

    $manager->refresh();
    expect($manager->isRetired())->toBeFalse();
    expect($manager->currentEmployment()->exists())->toBeTrue();

    // Verify retirement record was ended
    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $manager->id,
        'retirable_type' => $manager->getMorphClass(),
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify employment record was created
    $this->assertDatabaseHas('employments', [
        'employable_id' => $manager->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it unretires manager with specific unretirement date', function () {
    $manager = Manager::factory()->retired()->create();
    $unretirementDate = now()->subDays(3);

    resolve(UnretireAction::class)->handle($manager, $unretirementDate);

    $manager->refresh();
    expect($manager->isRetired())->toBeFalse();
    expect($manager->currentEmployment()->exists())->toBeTrue();

    // Verify retirement ended and employment started with specific date
    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $manager->id,
        'retirable_type' => $manager->getMorphClass(),
        'ended_at' => $unretirementDate->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('employments', [
        'employable_id' => $manager->id,
        'started_at' => $unretirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it persists the unretirement lifecycle', function () {
    $manager = Manager::factory()->retired()->create();

    // Get current retirement to verify it gets ended
    $currentRetirement = $manager->currentRetirement()->firstOrFail();
    expect($manager->currentEmployment)->toBeNull();

    resolve(UnretireAction::class)->handle($manager);

    $manager->refresh();

    // Verify retirement ended and employment was created
    expect($manager->currentRetirement)->toBeNull();
    expect($manager->currentEmployment)->not()->toBeNull();
    expect($manager->isRetired())->toBeFalse();
    expect($manager->currentEmployment()->exists())->toBeTrue();

    // Verify records show proper dates
    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $manager->id,
        'retirable_type' => $manager->getMorphClass(),
        'ended_at' => now()->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('employments', [
        'employable_id' => $manager->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it prevents unretiring non-retired manager', function () {
    $manager = Manager::factory()->employed()->create();

    expect($manager->isRetired())->toBeFalse();

    expect(fn () => resolve(UnretireAction::class)->handle($manager))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $manager = Manager::factory()->retired()->create();
    $originalRetirementId = $manager->currentRetirement()->firstOrFail()->id;

    resolve(UnretireAction::class)->handle($manager);

    $manager->refresh();

    // Verify the transaction was successful
    expect($manager->isRetired())->toBeFalse();
    expect($manager->currentEmployment()->exists())->toBeTrue();

    // Verify original retirement record was properly ended
    $this->assertDatabaseHas('retirements', [
        'id' => $originalRetirementId,
        'retirable_id' => $manager->id,
        'retirable_type' => $manager->getMorphClass(),
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify new employment record was created
    $employment = $manager->currentEmployment()->firstOrFail();
    expect(requiredDate($employment->started_at)->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($employment->ended_at)->toBeNull();
});

test('it creates new employment period during unretirement', function () {
    $manager = Manager::factory()->retired()->create();
    $originalEmploymentCount = $manager->employments()->count();

    resolve(UnretireAction::class)->handle($manager);

    $manager->refresh();

    // Should create a new employment record
    expect($manager->employments()->count())->toBe($originalEmploymentCount + 1);
    expect($manager->currentEmployment()->exists())->toBeTrue();

    // New employment should be current and active
    $currentEmployment = $manager->currentEmployment()->firstOrFail();
    expect(requiredDate($currentEmployment->started_at)->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($currentEmployment->ended_at)->toBeNull();
});

test('it uses the provided date', function () {
    $manager = Manager::factory()->retired()->create();
    $customUnretirementDate = now()->subDays(2)->startOfDay();

    resolve(UnretireAction::class)->handle($manager, $customUnretirementDate);

    $manager->refresh();

    // Verify the provided date was used across all operations
    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $manager->id,
        'retirable_type' => $manager->getMorphClass(),
        'ended_at' => $customUnretirementDate->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('employments', [
        'employable_id' => $manager->id,
        'started_at' => $customUnretirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it handles multiple retirement history correctly', function () {
    $manager = Manager::factory()->create();

    // Create multiple retirement history
    $manager->retirements()->create(['started_at' => now()->subDays(20), 'ended_at' => now()->subDays(15)]);
    $manager->retirements()->create(['started_at' => now()->subDays(10), 'ended_at' => null]); // Current retirement

    $manager->refresh();
    expect($manager->isRetired())->toBeTrue();
    expect($manager->retirements()->count())->toBe(2);

    resolve(UnretireAction::class)->handle($manager);

    $manager->refresh();

    // Should only end the current retirement, leaving historical ones intact
    expect($manager->isRetired())->toBeFalse();
    expect($manager->retirements()->count())->toBe(2);
    expect($manager->retirements()->whereNull('ended_at')->count())->toBe(0);

    // Should be employed now
    expect($manager->currentEmployment()->exists())->toBeTrue();
});

test('it preserves retirement history during unretirement', function () {
    $manager = Manager::factory()->retired()->create();
    $originalRetirementCount = $manager->retirements()->count();

    resolve(UnretireAction::class)->handle($manager);

    $manager->refresh();

    // Should preserve all retirement history
    expect($manager->retirements()->count())->toBe($originalRetirementCount);

    // All retirement records should have end dates now
    expect($manager->retirements()->whereNull('ended_at')->count())->toBe(0);

    // Current retirement should be null
    expect($manager->currentRetirement)->toBeNull();
});

test('it handles manager with complex status history', function () {
    $manager = Manager::factory()->create();

    // Create complex employment/retirement history
    $manager->employments()->create(['started_at' => now()->subDays(30), 'ended_at' => now()->subDays(25)]);
    $manager->retirements()->create(['started_at' => now()->subDays(25), 'ended_at' => now()->subDays(20)]);
    $manager->employments()->create(['started_at' => now()->subDays(20), 'ended_at' => now()->subDays(15)]);
    $manager->retirements()->create(['started_at' => now()->subDays(15), 'ended_at' => null]); // Current

    $manager->refresh();
    expect($manager->isRetired())->toBeTrue();
    expect($manager->currentEmployment()->exists())->toBeFalse();

    resolve(UnretireAction::class)->handle($manager);

    $manager->refresh();

    // Should now be employed, not retired
    expect($manager->isRetired())->toBeFalse();
    expect($manager->currentEmployment()->exists())->toBeTrue();

    // Should have preserved all historical records
    expect($manager->employments()->count())->toBe(3); // 2 historical + 1 new
    expect($manager->retirements()->count())->toBe(2); // Both historical now

    // New employment should be current
    $currentEmployment = $manager->currentEmployment()->firstOrFail();
    expect(requiredDate($currentEmployment->started_at)->toDateTimeString())->toBe(now()->toDateTimeString());
});
