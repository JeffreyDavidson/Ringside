<?php

declare(strict_types=1);

use App\Actions\Wrestlers\ReleaseAction;
use App\Models\Roster\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it releases an employed wrestler', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler->isEmployed())->toBeTrue();

    resolve(ReleaseAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->isEmployed())->toBeFalse();

    // Verify employment record was ended
    $this->assertDatabaseHas('employments', [
        'employable_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it releases wrestler with specific release date', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $releaseDate = now()->subDays(2);

    resolve(ReleaseAction::class)->handle($wrestler, $releaseDate);

    $wrestler->refresh();
    expect($wrestler->isEmployed())->toBeFalse();

    // Verify employment was ended with specific date
    $this->assertDatabaseHas('employments', [
        'employable_id' => $wrestler->id,
        'ended_at' => $releaseDate->toDateTimeString(),
    ]);
});

test('it persists the release lifecycle', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    // Get current employment to verify it gets ended
    $currentEmployment = $wrestler->currentEmployment()->firstOrFail();
    expect($currentEmployment->ended_at)->toBeNull();

    resolve(ReleaseAction::class)->handle($wrestler);

    $wrestler->refresh();

    // Verify employment period was ended
    expect($wrestler->currentEmployment)->toBeNull();
    expect($wrestler->isEmployed())->toBeFalse();

    // Verify the specific employment record was updated
    $this->assertDatabaseHas('employments', [
        'id' => $currentEmployment->id,
        'employable_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it uses the current time when no date is provided', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    // Test with null date (should use now())
    resolve(ReleaseAction::class)->handle($wrestler, null);

    $wrestler->refresh();
    expect($wrestler->isEmployed())->toBeFalse();

    $this->assertDatabaseHas('employments', [
        'employable_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it handles multiple employment records correctly', function () {
    $wrestler = Wrestler::factory()->create();

    // Create multiple employment records (old one already ended, current one active)
    $wrestler->employments()->create([
        'started_at' => now()->subDays(60),
        'ended_at' => now()->subDays(30), // Already ended
    ]);

    $currentEmployment = $wrestler->employments()->create([
        'started_at' => now()->subDays(20),
        'ended_at' => null, // Current employment
    ]);

    expect($wrestler->isEmployed())->toBeTrue();

    resolve(ReleaseAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->isEmployed())->toBeFalse();

    // Only the current employment should be ended
    $this->assertDatabaseHas('employments', [
        'id' => $currentEmployment->id,
        'employable_id' => $wrestler->id,
        'started_at' => now()->subDays(20)->toDateTimeString(),
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Old employment should remain unchanged
    $this->assertDatabaseHas('employments', [
        'employable_id' => $wrestler->id,
        'started_at' => now()->subDays(60)->toDateTimeString(),
        'ended_at' => now()->subDays(30)->toDateTimeString(),
    ]);
});

test('it prevents releasing non-employed wrestler', function () {
    $wrestler = Wrestler::factory()->create(); // Unemployed by default

    expect($wrestler->isEmployed())->toBeFalse();

    expect(fn () => resolve(ReleaseAction::class)->handle($wrestler))
        ->toThrow(Exception::class);
});

test('it prevents releasing retired wrestler', function () {
    $wrestler = Wrestler::factory()->retired()->create();

    expect($wrestler->isRetired())->toBeTrue();
    expect($wrestler->isEmployed())->toBeFalse();

    expect(fn () => resolve(ReleaseAction::class)->handle($wrestler))
        ->toThrow(Exception::class);
});

test('it can release suspended wrestler', function () {
    $wrestler = Wrestler::factory()->suspended()->create();

    expect($wrestler->isSuspended())->toBeTrue();
    expect($wrestler->isEmployed())->toBeTrue();

    resolve(ReleaseAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->isEmployed())->toBeFalse();
    expect($wrestler->isSuspended())->toBeFalse();

    $this->assertDatabaseHas('employments', [
        'employable_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it can release injured wrestler', function () {
    // Create employed wrestler who then gets injured
    $wrestler = Wrestler::factory()->employed()->create();
    $wrestler->injuries()->create([
        'started_at' => now()->subDays(3),
        'ended_at' => null,
    ]);

    expect($wrestler->isEmployed())->toBeTrue();
    expect($wrestler->isInjured())->toBeTrue();

    resolve(ReleaseAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->isEmployed())->toBeFalse(); // Should no longer be employed
    expect($wrestler->isInjured())->toBeFalse();

    $this->assertDatabaseHas('employments', [
        'employable_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it maintains employment history integrity', function () {
    $wrestler = Wrestler::factory()->create();

    // Create a complete employment history
    $firstEmployment = $wrestler->employments()->create([
        'started_at' => now()->subDays(100),
        'ended_at' => now()->subDays(50),
    ]);

    $currentEmployment = $wrestler->employments()->create([
        'started_at' => now()->subDays(30),
        'ended_at' => null,
    ]);

    expect($wrestler->isEmployed())->toBeTrue();

    resolve(ReleaseAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->isEmployed())->toBeFalse();

    // All employment records should be preserved
    $this->assertDatabaseHas('employments', [
        'id' => $firstEmployment->id,
        'ended_at' => now()->subDays(50)->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('employments', [
        'id' => $currentEmployment->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Should have exactly 2 employment records
    expect($wrestler->employments()->count())->toBe(2);
});
