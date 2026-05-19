<?php

declare(strict_types=1);

use App\Actions\Wrestlers\ReinstateAction;
use App\Models\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it reinstates a suspended wrestler', function () {
    $wrestler = Wrestler::factory()->suspended()->create();

    expect($wrestler->isSuspended())->toBeTrue();
    expect($wrestler->isEmployed())->toBeTrue();

    ReinstateAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->isSuspended())->toBeFalse();
    expect($wrestler->isEmployed())->toBeTrue();

    // Verify suspension record was ended
    $this->assertDatabaseHas('wrestlers_suspensions', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it reinstates an injured wrestler', function () {
    $wrestler = Wrestler::factory()->injured()->create();

    expect($wrestler->isInjured())->toBeTrue();
    expect($wrestler->isEmployed())->toBeTrue();

    ReinstateAction::run($wrestler);

    $wrestler->refresh();

    expect($wrestler->isInjured())->toBeFalse();
    expect($wrestler->isEmployed())->toBeTrue();

    $this->assertDatabaseHas('wrestlers_injuries', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it reinstates wrestler with specific reinstatement date', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $reinstatementDate = now()->subDays(3);

    ReinstateAction::run($wrestler, $reinstatementDate);

    $wrestler->refresh();
    expect($wrestler->isSuspended())->toBeFalse();

    // Verify suspension was ended with specific date
    $this->assertDatabaseHas('wrestlers_suspensions', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => $reinstatementDate->toDateTimeString(),
    ]);
});

test('it uses StatusTransitionPipeline for reinstatement', function () {
    $wrestler = Wrestler::factory()->suspended()->create();

    // Get current suspension to verify it gets ended
    $currentSuspension = $wrestler->currentSuspension;
    expect($currentSuspension)->not()->toBeNull();
    expect($currentSuspension->ended_at)->toBeNull();

    ReinstateAction::run($wrestler);

    $wrestler->refresh();

    // Verify suspension ended through pipeline
    expect($wrestler->currentSuspension)->toBeNull();
    expect($wrestler->isSuspended())->toBeFalse();

    // Verify the specific suspension record was updated
    $this->assertDatabaseHas('wrestlers_suspensions', [
        'id' => $currentSuspension->id,
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it handles DateHelper date resolution', function () {
    $wrestler = Wrestler::factory()->suspended()->create();

    // Test with null date (should use now())
    ReinstateAction::run($wrestler, null);

    $wrestler->refresh();
    expect($wrestler->isSuspended())->toBeFalse();

    $this->assertDatabaseHas('wrestlers_suspensions', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it reinstates wrestler with both suspension and injury', function () {
    // Create employed wrestler, then suspend and injure them
    $wrestler = Wrestler::factory()->employed()->create();

    $wrestler->suspensions()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => null,
        'notes' => 'Suspended for violation',
    ]);

    $wrestler->injuries()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    expect($wrestler->isSuspended())->toBeTrue();
    expect($wrestler->isInjured())->toBeTrue();
    expect($wrestler->isEmployed())->toBeTrue(); // Still employed despite suspension/injury

    ReinstateAction::run($wrestler);

    $wrestler->refresh();

    expect($wrestler->isSuspended())->toBeFalse();
    expect($wrestler->isInjured())->toBeFalse();
    expect($wrestler->isEmployed())->toBeTrue();
});

test('it handles multiple suspension records correctly', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    // Create old records (already ended)
    $wrestler->suspensions()->create([
        'started_at' => now()->subDays(60),
        'ended_at' => now()->subDays(40),
        'notes' => 'Old suspension',
    ]);

    // Create current active records
    $currentSuspension = $wrestler->suspensions()->create([
        'started_at' => now()->subDays(20),
        'ended_at' => null,
        'notes' => 'Current suspension',
    ]);

    expect($wrestler->isSuspended())->toBeTrue();

    ReinstateAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->isSuspended())->toBeFalse();

    // Only current suspension should be ended
    $this->assertDatabaseHas('wrestlers_suspensions', [
        'id' => $currentSuspension->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Old records should remain unchanged
    $this->assertDatabaseHas('wrestlers_suspensions', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->subDays(60)->toDateTimeString(),
        'ended_at' => now()->subDays(40)->toDateTimeString(),
    ]);
});

test('it prevents reinstating non-suspended non-injured wrestler', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler->isSuspended())->toBeFalse();
    expect($wrestler->isInjured())->toBeFalse();

    expect(fn () => ReinstateAction::run($wrestler))
        ->toThrow(Exception::class);
});

test('it prevents reinstating retired wrestler', function () {
    $wrestler = Wrestler::factory()->retired()->create();

    expect($wrestler->isRetired())->toBeTrue();

    expect(fn () => ReinstateAction::run($wrestler))
        ->toThrow(Exception::class);
});

test('it can reinstate suspended wrestler who is also employed', function () {
    // Create employed wrestler who gets suspended but remains under contract
    $wrestler = Wrestler::factory()->employed()->create();

    $wrestler->suspensions()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
        'notes' => 'Temporary suspension',
    ]);

    expect($wrestler->isEmployed())->toBeTrue();
    expect($wrestler->isSuspended())->toBeTrue();

    ReinstateAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->isEmployed())->toBeTrue(); // Should remain employed
    expect($wrestler->isSuspended())->toBeFalse(); // Should no longer be suspended

    $this->assertDatabaseHas('wrestlers_suspensions', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it maintains status integrity after reinstatement', function () {
    $wrestler = Wrestler::factory()->suspended()->create();

    // Verify initial state
    expect($wrestler->isSuspended())->toBeTrue();
    expect($wrestler->isEmployed())->toBeTrue();
    expect($wrestler->isInjured())->toBeFalse();
    expect($wrestler->isRetired())->toBeFalse();

    ReinstateAction::run($wrestler);

    $wrestler->refresh();

    // After reinstatement, wrestler should be active under the same employment.
    expect($wrestler->isSuspended())->toBeFalse();
    expect($wrestler->isEmployed())->toBeTrue();
    expect($wrestler->isInjured())->toBeFalse();
    expect($wrestler->isRetired())->toBeFalse();
});
