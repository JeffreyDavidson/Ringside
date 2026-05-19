<?php

declare(strict_types=1);

use App\Actions\Wrestlers\InjureAction;
use App\Models\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it injures an employed wrestler', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler->isEmployed())->toBeTrue();
    expect($wrestler->isInjured())->toBeFalse();

    InjureAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->isInjured())->toBeTrue();
    expect($wrestler->isEmployed())->toBeTrue(); // Should remain employed while injured

    $this->assertDatabaseHas('wrestlers_injuries', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it injures wrestler with specific injury date', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $injuryDate = now()->subDays(4);

    InjureAction::run($wrestler, $injuryDate);

    $wrestler->refresh();
    expect($wrestler->isInjured())->toBeTrue();

    $this->assertDatabaseHas('wrestlers_injuries', [
        'wrestler_id' => $wrestler->id,
        'started_at' => $injuryDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it uses StatusTransitionPipeline for injury', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler->currentInjury)->toBeNull();

    InjureAction::run($wrestler);

    $wrestler->refresh();

    // Verify injury created through pipeline
    expect($wrestler->currentInjury)->not()->toBeNull();
    expect($wrestler->isInjured())->toBeTrue();

    $this->assertDatabaseHas('wrestlers_injuries', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it handles DateHelper date resolution', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    // Test with null date (should use now())
    InjureAction::run($wrestler, null);

    $wrestler->refresh();
    expect($wrestler->isInjured())->toBeTrue();

    $this->assertDatabaseHas('wrestlers_injuries', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->toDateTimeString(),
    ]);
});

test('it handles multiple injury scenarios', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    // Create old injury record (already healed)
    $wrestler->injuries()->create([
        'started_at' => now()->subDays(60),
        'ended_at' => now()->subDays(30),
    ]);

    expect($wrestler->isEmployed())->toBeTrue();
    expect($wrestler->isInjured())->toBeFalse();

    InjureAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->isInjured())->toBeTrue();

    // New injury should be created
    $this->assertDatabaseHas('wrestlers_injuries', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);

    // Old injury should remain unchanged
    $this->assertDatabaseHas('wrestlers_injuries', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->subDays(60)->toDateTimeString(),
        'ended_at' => now()->subDays(30)->toDateTimeString(),
    ]);
});

test('it prevents injuring already injured wrestler', function () {
    $wrestler = Wrestler::factory()->injured()->create();

    expect($wrestler->isInjured())->toBeTrue();

    expect(fn () => InjureAction::run($wrestler))
        ->toThrow(Exception::class);
});

test('it prevents injuring retired wrestler', function () {
    $wrestler = Wrestler::factory()->retired()->create();

    expect($wrestler->isRetired())->toBeTrue();

    expect(fn () => InjureAction::run($wrestler))
        ->toThrow(Exception::class);
});

test('it prevents injuring unemployed wrestler', function () {
    $wrestler = Wrestler::factory()->create(); // Unemployed by default

    expect($wrestler->isEmployed())->toBeFalse();

    expect(fn () => InjureAction::run($wrestler))
        ->toThrow(Exception::class);
});

test('it injures suspended wrestler', function () {
    $wrestler = Wrestler::factory()->suspended()->create();

    expect($wrestler->isSuspended())->toBeTrue();
    expect($wrestler->isEmployed())->toBeTrue();

    InjureAction::run($wrestler);

    $wrestler->refresh();

    expect($wrestler->isSuspended())->toBeTrue();
    expect($wrestler->isInjured())->toBeTrue();
    expect($wrestler->isEmployed())->toBeTrue();
});

test('it maintains injury history integrity', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    // Create a complete injury history
    $firstInjury = $wrestler->injuries()->create([
        'started_at' => now()->subDays(100),
        'ended_at' => now()->subDays(60),
    ]);

    $secondInjury = $wrestler->injuries()->create([
        'started_at' => now()->subDays(50),
        'ended_at' => now()->subDays(20),
    ]);

    expect($wrestler->isInjured())->toBeFalse();

    InjureAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->isInjured())->toBeTrue();

    // All injury records should be preserved
    $this->assertDatabaseHas('wrestlers_injuries', [
        'id' => $firstInjury->id,
        'ended_at' => now()->subDays(60)->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('wrestlers_injuries', [
        'id' => $secondInjury->id,
        'ended_at' => now()->subDays(20)->toDateTimeString(),
    ]);

    // New current injury should exist
    $this->assertDatabaseHas('wrestlers_injuries', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);

    // Should have exactly 3 injury records
    expect($wrestler->injuries()->count())->toBe(3);
});

test('it allows re-injury after healing', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    // Create and end an injury (wrestler was healed)
    $wrestler->injuries()->create([
        'started_at' => now()->subDays(30),
        'ended_at' => now()->subDays(10), // Healed 10 days ago
    ]);

    expect($wrestler->isInjured())->toBeFalse();
    expect($wrestler->isEmployed())->toBeTrue();

    // Should be able to get injured again
    InjureAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->isInjured())->toBeTrue();

    // Should have 2 injury records now
    expect($wrestler->injuries()->count())->toBe(2);

    // Current injury should be active
    $this->assertDatabaseHas('wrestlers_injuries', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});
