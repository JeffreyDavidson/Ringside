<?php

declare(strict_types=1);

use App\Actions\Wrestlers\ClearFromInjuryAction;
use App\Models\Roster\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it clears an injured wrestler', function () {
    $wrestler = Wrestler::factory()->injured()->create();

    expect($wrestler->isInjured())->toBeTrue();

    resolve(ClearFromInjuryAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->isInjured())->toBeFalse();

    // Verify injury record was ended
    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $wrestler->id,
        'injurable_type' => $wrestler->getMorphClass(),
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it clears wrestler from injury with specific recovery date', function () {
    $wrestler = Wrestler::factory()->injured()->create();
    $recoveryDate = now()->subDays(5);

    resolve(ClearFromInjuryAction::class)->handle($wrestler, $recoveryDate);

    $wrestler->refresh();
    expect($wrestler->isInjured())->toBeFalse();

    // Verify injury was ended with specific date
    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $wrestler->id,
        'injurable_type' => $wrestler->getMorphClass(),
        'ended_at' => $recoveryDate->toDateTimeString(),
    ]);
});

test('it persists the injury clearance lifecycle', function () {
    $wrestler = Wrestler::factory()->injured()->create();

    // Get current injury to verify it gets ended
    $currentInjury = $wrestler->currentInjury()->firstOrFail();

    resolve(ClearFromInjuryAction::class)->handle($wrestler);

    $wrestler->refresh();

    // Verify injury period was ended
    expect($wrestler->currentInjury)->toBeNull();
    expect($wrestler->isInjured())->toBeFalse();

    // Verify the specific injury record was updated
    $this->assertDatabaseHas('injuries', [
        'id' => $currentInjury->id,
        'injurable_id' => $wrestler->id,
        'injurable_type' => $wrestler->getMorphClass(),
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it uses the current time when no date is provided', function () {
    $wrestler = Wrestler::factory()->injured()->create();

    // Test with null date (should use now())
    resolve(ClearFromInjuryAction::class)->handle($wrestler, null);

    $wrestler->refresh();
    expect($wrestler->isInjured())->toBeFalse();

    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $wrestler->id,
        'injurable_type' => $wrestler->getMorphClass(),
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

    resolve(ClearFromInjuryAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->isInjured())->toBeFalse();

    // Only the current injury should be ended
    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $wrestler->id,
        'injurable_type' => $wrestler->getMorphClass(),
        'started_at' => now()->subDays(10)->toDateTimeString(),
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Old injury should remain unchanged
    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $wrestler->id,
        'injurable_type' => $wrestler->getMorphClass(),
        'started_at' => now()->subDays(30)->toDateTimeString(),
        'ended_at' => now()->subDays(20)->toDateTimeString(),
    ]);
});

test('it prevents clearing non-injured wrestler', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler->isInjured())->toBeFalse();

    expect(fn () => resolve(ClearFromInjuryAction::class)->handle($wrestler))
        ->toThrow(Exception::class);
});

test('it prevents clearing retired wrestler', function () {
    $wrestler = Wrestler::factory()->retired()->create();

    expect($wrestler->isRetired())->toBeTrue();
    expect($wrestler->isInjured())->toBeFalse();

    expect(fn () => resolve(ClearFromInjuryAction::class)->handle($wrestler))
        ->toThrow(Exception::class);
});

test('it works with employed injured wrestler', function () {
    // Create employed wrestler, then injure them
    $wrestler = Wrestler::factory()->employed()->create();
    $wrestler->injuries()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    expect($wrestler->currentEmployment()->exists())->toBeTrue();
    expect($wrestler->isInjured())->toBeTrue();

    resolve(ClearFromInjuryAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->currentEmployment()->exists())->toBeTrue(); // Should remain employed
    expect($wrestler->isInjured())->toBeFalse(); // Should no longer be injured
});
