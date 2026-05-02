<?php

declare(strict_types=1);

use App\Actions\Managers\UnretireAction;
use App\Models\Managers\Manager;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it unretires a retired manager', function () {
    $manager = Manager::factory()->retired()->create();

    expect($manager->isRetired())->toBeTrue();
    expect($manager->isEmployed())->toBeFalse();

    UnretireAction::run($manager);

    $manager->refresh();
    expect($manager->isRetired())->toBeFalse();
    expect($manager->isEmployed())->toBeTrue();

    // Verify retirement record was ended
    $this->assertDatabaseHas('managers_retirements', [
        'manager_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify employment record was created
    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it unretires manager with specific unretirement date', function () {
    $manager = Manager::factory()->retired()->create();
    $unretirementDate = now()->subDays(3);

    UnretireAction::run($manager, $unretirementDate);

    $manager->refresh();
    expect($manager->isRetired())->toBeFalse();
    expect($manager->isEmployed())->toBeTrue();

    // Verify retirement ended and employment started with specific date
    $this->assertDatabaseHas('managers_retirements', [
        'manager_id' => $manager->id,
        'ended_at' => $unretirementDate->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager->id,
        'started_at' => $unretirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it uses StatusTransitionPipeline for unretirement', function () {
    $manager = Manager::factory()->retired()->create();

    // Get current retirement to verify it gets ended
    $currentRetirement = $manager->currentRetirement();
    expect($currentRetirement)->not()->toBeNull();
    expect($manager->currentEmployment())->toBeNull();

    UnretireAction::run($manager);

    $manager->refresh();

    // Verify retirement ended and employment created through pipeline
    expect($manager->currentRetirement())->toBeNull();
    expect($manager->currentEmployment())->not()->toBeNull();
    expect($manager->isRetired())->toBeFalse();
    expect($manager->isEmployed())->toBeTrue();

    // Verify records show proper dates
    $this->assertDatabaseHas('managers_retirements', [
        'manager_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it prevents unretiring non-retired manager', function () {
    $manager = Manager::factory()->employed()->create();

    expect($manager->isRetired())->toBeFalse();

    expect(fn () => UnretireAction::run($manager))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $manager = Manager::factory()->retired()->create();
    $originalRetirementId = $manager->currentRetirement->id;

    UnretireAction::run($manager);

    $manager->refresh();

    // Verify the transaction was successful
    expect($manager->isRetired())->toBeFalse();
    expect($manager->isEmployed())->toBeTrue();

    // Verify original retirement record was properly ended
    $this->assertDatabaseHas('managers_retirements', [
        'id' => $originalRetirementId,
        'manager_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify new employment record was created
    $employment = $manager->currentEmployment();
    expect($employment)->not()->toBeNull();
    expect($employment->started_at->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($employment->ended_at)->toBeNull();
});

test('it creates new employment period during unretirement', function () {
    $manager = Manager::factory()->retired()->create();
    $originalEmploymentCount = $manager->employments()->count();

    UnretireAction::run($manager);

    $manager->refresh();

    // Should create a new employment record
    expect($manager->employments()->count())->toBe($originalEmploymentCount + 1);
    expect($manager->isEmployed())->toBeTrue();

    // New employment should be current and active
    $currentEmployment = $manager->currentEmployment();
    expect($currentEmployment)->not()->toBeNull();
    expect($currentEmployment->started_at->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($currentEmployment->ended_at)->toBeNull();
});

test('it uses DateHelper for consistent date handling', function () {
    $manager = Manager::factory()->retired()->create();
    $customUnretirementDate = now()->subDays(2)->startOfDay();

    UnretireAction::run($manager, $customUnretirementDate);

    $manager->refresh();

    // Verify DateHelper was used for date resolution across all operations
    $this->assertDatabaseHas('managers_retirements', [
        'manager_id' => $manager->id,
        'ended_at' => $customUnretirementDate->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager->id,
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

    UnretireAction::run($manager);

    $manager->refresh();

    // Should only end the current retirement, leaving historical ones intact
    expect($manager->isRetired())->toBeFalse();
    expect($manager->retirements()->count())->toBe(2);
    expect($manager->retirements()->whereNull('ended_at')->count())->toBe(0);

    // Should be employed now
    expect($manager->isEmployed())->toBeTrue();
});

test('it preserves retirement history during unretirement', function () {
    $manager = Manager::factory()->retired()->create();
    $originalRetirementCount = $manager->retirements()->count();

    UnretireAction::run($manager);

    $manager->refresh();

    // Should preserve all retirement history
    expect($manager->retirements()->count())->toBe($originalRetirementCount);

    // All retirement records should have end dates now
    expect($manager->retirements()->whereNull('ended_at')->count())->toBe(0);

    // Current retirement should be null
    expect($manager->currentRetirement())->toBeNull();
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
    expect($manager->isEmployed())->toBeFalse();

    UnretireAction::run($manager);

    $manager->refresh();

    // Should now be employed, not retired
    expect($manager->isRetired())->toBeFalse();
    expect($manager->isEmployed())->toBeTrue();

    // Should have preserved all historical records
    expect($manager->employments()->count())->toBe(3); // 2 historical + 1 new
    expect($manager->retirements()->count())->toBe(2); // Both historical now

    // New employment should be current
    $currentEmployment = $manager->currentEmployment();
    expect($currentEmployment)->not()->toBeNull();
    expect($currentEmployment->started_at->toDateTimeString())->toBe(now()->toDateTimeString());
});
