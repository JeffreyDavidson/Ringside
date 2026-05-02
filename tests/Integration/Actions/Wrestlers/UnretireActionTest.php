<?php

declare(strict_types=1);

use App\Actions\Wrestlers\UnretireAction;
use App\Models\Managers\Manager;
use App\Models\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it unretires a retired wrestler with employment', function () {
    $wrestler = Wrestler::factory()->retired()->create();

    expect($wrestler->isRetired())->toBeTrue();
    expect($wrestler->isEmployed())->toBeFalse();

    UnretireAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->isRetired())->toBeFalse();
    expect($wrestler->isEmployed())->toBeTrue(); // Should be employed by default

    // Verify retirement record was ended
    $this->assertDatabaseHas('wrestlers_retirements', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify employment record was created
    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it unretires wrestler without immediate employment', function () {
    $wrestler = Wrestler::factory()->retired()->create();

    expect($wrestler->isRetired())->toBeTrue();

    UnretireAction::run($wrestler, null, false);

    $wrestler->refresh();
    expect($wrestler->isRetired())->toBeFalse();
    expect($wrestler->isEmployed())->toBeFalse(); // Should remain unemployed

    // Verify retirement record was ended
    $this->assertDatabaseHas('wrestlers_retirements', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify no employment record was created
    $this->assertDatabaseMissing('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->toDateTimeString(),
    ]);
});

test('it unretires wrestler with specific date', function () {
    $wrestler = Wrestler::factory()->retired()->create();
    $unretirementDate = now()->subDays(5);

    UnretireAction::run($wrestler, $unretirementDate);

    $wrestler->refresh();
    expect($wrestler->isRetired())->toBeFalse();
    expect($wrestler->isEmployed())->toBeTrue();

    // Verify retirement was ended with specific date
    $this->assertDatabaseHas('wrestlers_retirements', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => $unretirementDate->toDateTimeString(),
    ]);

    // Verify employment started with same date
    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'started_at' => $unretirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it uses StatusTransitionPipeline for unretirement', function () {
    $wrestler = Wrestler::factory()->retired()->create();

    // Get current retirement to verify it gets ended
    $currentRetirement = $wrestler->currentRetirement;
    expect($currentRetirement)->not()->toBeNull();
    expect($currentRetirement->ended_at)->toBeNull();

    UnretireAction::run($wrestler);

    $wrestler->refresh();

    // Verify retirement ended through pipeline
    expect($wrestler->currentRetirement)->toBeNull();
    expect($wrestler->isRetired())->toBeFalse();

    // Verify the specific retirement record was updated
    $this->assertDatabaseHas('wrestlers_retirements', [
        'id' => $currentRetirement->id,
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it employs unemployed managers when wrestler is employed', function () {
    $wrestler = Wrestler::factory()->retired()->create();
    $manager1 = Manager::factory()->create(); // unemployed
    $manager2 = Manager::factory()->employed()->create(); // already employed

    // Assign managers to retired wrestler
    $wrestler->managers()->attach($manager1->id, ['hired_at' => now()->subDays(10)]);
    $wrestler->managers()->attach($manager2->id, ['hired_at' => now()->subDays(5)]);

    expect($wrestler->isRetired())->toBeTrue();
    expect($manager1->isEmployed())->toBeFalse();
    expect($manager2->isEmployed())->toBeTrue();

    UnretireAction::run($wrestler); // employImmediately defaults to true

    $wrestler->refresh();
    $manager1->refresh();
    $manager2->refresh();

    expect($wrestler->isEmployed())->toBeTrue();
    expect($manager1->isEmployed())->toBeTrue(); // Should now be employed via cascade
    expect($manager2->isEmployed())->toBeTrue(); // Should remain employed

    // Both wrestler and manager1 should have new employment records
    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => null,
    ]);

    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager1->id,
        'ended_at' => null,
    ]);
});

test('it does not employ managers when wrestler is not employed immediately', function () {
    $wrestler = Wrestler::factory()->retired()->create();
    $manager = Manager::factory()->create(); // unemployed

    $wrestler->managers()->attach($manager->id, ['hired_at' => now()->subDays(5)]);

    expect($wrestler->isRetired())->toBeTrue();
    expect($manager->isEmployed())->toBeFalse();

    UnretireAction::run($wrestler, null, false); // employImmediately = false

    $wrestler->refresh();
    $manager->refresh();

    expect($wrestler->isEmployed())->toBeFalse();
    expect($manager->isEmployed())->toBeFalse(); // Should remain unemployed

    // No employment records should be created
    $this->assertDatabaseMissing('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => null,
    ]);

    $this->assertDatabaseMissing('managers_employments', [
        'manager_id' => $manager->id,
        'ended_at' => null,
    ]);
});

test('it handles DateHelper date resolution', function () {
    $wrestler = Wrestler::factory()->retired()->create();

    // Test with null date (should use now())
    UnretireAction::run($wrestler, null);

    $wrestler->refresh();
    expect($wrestler->isRetired())->toBeFalse();

    $this->assertDatabaseHas('wrestlers_retirements', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it handles multiple retirement records correctly', function () {
    $wrestler = Wrestler::factory()->create();

    // Create multiple retirement records (old one already ended, current one active)
    $wrestler->retirements()->create([
        'started_at' => now()->subDays(100),
        'ended_at' => now()->subDays(60), // Already ended (came out of retirement before)
    ]);

    $currentRetirement = $wrestler->retirements()->create([
        'started_at' => now()->subDays(30),
        'ended_at' => null, // Current retirement
    ]);

    expect($wrestler->isRetired())->toBeTrue();

    UnretireAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->isRetired())->toBeFalse();

    // Only the current retirement should be ended
    $this->assertDatabaseHas('wrestlers_retirements', [
        'id' => $currentRetirement->id,
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->subDays(30)->toDateTimeString(),
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Old retirement should remain unchanged
    $this->assertDatabaseHas('wrestlers_retirements', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->subDays(100)->toDateTimeString(),
        'ended_at' => now()->subDays(60)->toDateTimeString(),
    ]);
});

test('it prevents unretiring non-retired wrestler', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler->isRetired())->toBeFalse();

    expect(fn () => UnretireAction::run($wrestler))
        ->toThrow(Exception::class);
});

test('it prevents unretiring deleted wrestler', function () {
    $wrestler = Wrestler::factory()->retired()->create();
    $wrestler->delete(); // Soft delete

    expect(fn () => UnretireAction::run($wrestler))
        ->toThrow(Exception::class);
});

test('it maintains retirement history integrity', function () {
    $wrestler = Wrestler::factory()->create();

    // Create a complete retirement history
    $firstRetirement = $wrestler->retirements()->create([
        'started_at' => now()->subDays(200),
        'ended_at' => now()->subDays(150), // First retirement ended
    ]);

    $secondRetirement = $wrestler->retirements()->create([
        'started_at' => now()->subDays(100),
        'ended_at' => now()->subDays(50), // Second retirement ended
    ]);

    $currentRetirement = $wrestler->retirements()->create([
        'started_at' => now()->subDays(20),
        'ended_at' => null, // Current retirement
    ]);

    expect($wrestler->isRetired())->toBeTrue();

    UnretireAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->isRetired())->toBeFalse();

    // All retirement records should be preserved
    $this->assertDatabaseHas('wrestlers_retirements', [
        'id' => $firstRetirement->id,
        'ended_at' => now()->subDays(150)->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('wrestlers_retirements', [
        'id' => $secondRetirement->id,
        'ended_at' => now()->subDays(50)->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('wrestlers_retirements', [
        'id' => $currentRetirement->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Should have exactly 3 retirement records
    expect($wrestler->retirements()->count())->toBe(3);
});
