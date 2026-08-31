<?php

declare(strict_types=1);

use App\Actions\Wrestlers\InjureAction;
use App\Exceptions\Roster\Individuals\CannotBeInjuredException;
use App\Models\Roster\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it injures an employed wrestler', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler->currentEmployment()->exists())->toBeTrue();
    expect($wrestler->isInjured())->toBeFalse();

    resolve(InjureAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->isInjured())->toBeTrue();
    expect($wrestler->currentEmployment()->exists())->toBeTrue(); // Should remain employed while injured

    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $wrestler->id,
        'injurable_type' => $wrestler->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it injures wrestler with specific injury date', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $injuryDate = now()->subDays(4);

    resolve(InjureAction::class)->handle($wrestler, $injuryDate);

    $wrestler->refresh();
    expect($wrestler->isInjured())->toBeTrue();

    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $wrestler->id,
        'injurable_type' => $wrestler->getMorphClass(),
        'started_at' => $injuryDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it persists the injury lifecycle', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler->currentInjury)->toBeNull();

    resolve(InjureAction::class)->handle($wrestler);

    $wrestler->refresh();

    // Verify injury period was created
    expect($wrestler->currentInjury)->not()->toBeNull();
    expect($wrestler->isInjured())->toBeTrue();

    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $wrestler->id,
        'injurable_type' => $wrestler->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it uses the current time when no date is provided', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    // Test with null date (should use now())
    resolve(InjureAction::class)->handle($wrestler, null);

    $wrestler->refresh();
    expect($wrestler->isInjured())->toBeTrue();

    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $wrestler->id,
        'injurable_type' => $wrestler->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
    ]);
});

test('it handles multiple injury scenarios', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    // Create old injury record (already cleared from injury)
    $wrestler->injuries()->create([
        'started_at' => now()->subDays(60),
        'ended_at' => now()->subDays(30),
    ]);

    expect($wrestler->currentEmployment()->exists())->toBeTrue();
    expect($wrestler->isInjured())->toBeFalse();

    resolve(InjureAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->isInjured())->toBeTrue();

    // New injury should be created
    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $wrestler->id,
        'injurable_type' => $wrestler->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);

    // Old injury should remain unchanged
    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $wrestler->id,
        'injurable_type' => $wrestler->getMorphClass(),
        'started_at' => now()->subDays(60)->toDateTimeString(),
        'ended_at' => now()->subDays(30)->toDateTimeString(),
    ]);
});

test('it prevents injuring already injured wrestler', function () {
    $wrestler = Wrestler::factory()->injured()->create();

    expect($wrestler->isInjured())->toBeTrue();

    expect(fn () => resolve(InjureAction::class)->handle($wrestler))
        ->toThrow(Exception::class);
});

test('it prevents injuring retired wrestler', function () {
    $wrestler = Wrestler::factory()->retired()->create();

    expect($wrestler->currentRetirement()->exists())->toBeTrue();

    expect(fn () => resolve(InjureAction::class)->handle($wrestler))
        ->toThrow(Exception::class);
});

test('it prevents injuring unemployed wrestler', function () {
    $wrestler = Wrestler::factory()->create(); // Unemployed by default

    expect($wrestler->currentEmployment()->exists())->toBeFalse();

    expect(fn () => resolve(InjureAction::class)->handle($wrestler))
        ->toThrow(Exception::class);
});

test('it prevents injuring a suspended wrestler', function () {
    $wrestler = Wrestler::factory()->suspended()->create();

    expect($wrestler->currentSuspension()->exists())->toBeTrue();
    expect($wrestler->currentEmployment()->exists())->toBeTrue();

    expect(fn () => resolve(InjureAction::class)->handle($wrestler))
        ->toThrow(CannotBeInjuredException::class);

    $wrestler->refresh();

    expect($wrestler->currentSuspension()->exists())->toBeTrue();
    expect($wrestler->isInjured())->toBeFalse();
    expect($wrestler->currentEmployment()->exists())->toBeTrue();
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

    resolve(InjureAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->isInjured())->toBeTrue();

    // All injury records should be preserved
    $this->assertDatabaseHas('injuries', [
        'id' => $firstInjury->id,
        'ended_at' => now()->subDays(60)->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('injuries', [
        'id' => $secondInjury->id,
        'ended_at' => now()->subDays(20)->toDateTimeString(),
    ]);

    // New current injury should exist
    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $wrestler->id,
        'injurable_type' => $wrestler->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);

    // Should have exactly 3 injury records
    expect($wrestler->injuries()->count())->toBe(3);
});

test('it allows re-injury after injury clearance', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    // Create and end an injury (wrestler was cleared from injury)
    $wrestler->injuries()->create([
        'started_at' => now()->subDays(30),
        'ended_at' => now()->subDays(10), // Cleared from injury 10 days ago
    ]);

    expect($wrestler->isInjured())->toBeFalse();
    expect($wrestler->currentEmployment()->exists())->toBeTrue();

    // Should be able to get injured again
    resolve(InjureAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->isInjured())->toBeTrue();

    // Should have 2 injury records now
    expect($wrestler->injuries()->count())->toBe(2);

    // Current injury should be active
    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $wrestler->id,
        'injurable_type' => $wrestler->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});
