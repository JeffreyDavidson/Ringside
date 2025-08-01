<?php

declare(strict_types=1);

use App\Actions\Wrestlers\HealAction;
use App\Models\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it heals an injured wrestler', function () {
    $wrestler = Wrestler::factory()->injured()->create();

    expect($wrestler->isInjured())->toBeTrue();

    HealAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->isInjured())->toBeFalse();

    // Verify injury record was ended
    $this->assertDatabaseHas('wrestler_injuries', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it heals wrestler with specific recovery date', function () {
    $wrestler = Wrestler::factory()->injured()->create();
    $recoveryDate = now()->subDays(5);

    HealAction::run($wrestler, $recoveryDate);

    $wrestler->refresh();
    expect($wrestler->isInjured())->toBeFalse();

    // Verify injury was ended with specific date
    $this->assertDatabaseHas('wrestler_injuries', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => $recoveryDate->toDateTimeString(),
    ]);
});

test('it uses StatusTransitionPipeline for healing', function () {
    $wrestler = Wrestler::factory()->injured()->create();

    // Get current injury to verify it gets ended
    $currentInjury = $wrestler->currentInjury();
    expect($currentInjury)->not()->toBeNull();

    HealAction::run($wrestler);

    $wrestler->refresh();

    // Verify injury ended through pipeline
    expect($wrestler->currentInjury())->toBeNull();
    expect($wrestler->isInjured())->toBeFalse();

    // Verify the specific injury record was updated
    $this->assertDatabaseHas('wrestler_injuries', [
        'id' => $currentInjury->id,
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it handles DateHelper date resolution', function () {
    $wrestler = Wrestler::factory()->injured()->create();

    // Test with null date (should use now())
    HealAction::run($wrestler, null);

    $wrestler->refresh();
    expect($wrestler->isInjured())->toBeFalse();

    $this->assertDatabaseHas('wrestler_injuries', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it handles multiple injury records correctly', function () {
    $wrestler = Wrestler::factory()->create();

    // Create multiple injury records (old one already ended, current one active)
    $wrestler->injuries()->create([
        'started_at' => now()->subDays(30),
        'ended_at' => now()->subDays(20), // Already ended
    ]);

    $wrestler->injuries()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => null, // Current injury
    ]);

    expect($wrestler->isInjured())->toBeTrue();

    HealAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->isInjured())->toBeFalse();

    // Only the current injury should be ended
    $this->assertDatabaseHas('wrestler_injuries', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->subDays(10)->toDateTimeString(),
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Old injury should remain unchanged
    $this->assertDatabaseHas('wrestler_injuries', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->subDays(30)->toDateTimeString(),
        'ended_at' => now()->subDays(20)->toDateTimeString(),
    ]);
});

test('it prevents healing non-injured wrestler', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler->isInjured())->toBeFalse();

    expect(fn () => HealAction::run($wrestler))
        ->toThrow(Exception::class);
});

test('it prevents healing retired wrestler', function () {
    $wrestler = Wrestler::factory()->retired()->create();

    expect($wrestler->isRetired())->toBeTrue();
    expect($wrestler->isInjured())->toBeFalse();

    expect(fn () => HealAction::run($wrestler))
        ->toThrow(Exception::class);
});

test('it works with employed injured wrestler', function () {
    // Create employed wrestler, then injure them
    $wrestler = Wrestler::factory()->employed()->create();
    $wrestler->injuries()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    expect($wrestler->isEmployed())->toBeTrue();
    expect($wrestler->isInjured())->toBeTrue();

    HealAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->isEmployed())->toBeTrue(); // Should remain employed
    expect($wrestler->isInjured())->toBeFalse(); // Should no longer be injured
});
