<?php

declare(strict_types=1);

use App\Actions\Wrestlers\SuspendAction;
use App\Models\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it suspends an employed wrestler', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler->isEmployed())->toBeTrue();
    expect($wrestler->isSuspended())->toBeFalse();

    SuspendAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->isSuspended())->toBeTrue();
    expect($wrestler->isEmployed())->toBeTrue(); // Suspension preserves employment, only restricts availability

    $this->assertDatabaseHas('wrestlers_suspensions', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it suspends wrestler with specific suspension date', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $suspensionDate = now()->subDays(3);

    SuspendAction::run($wrestler, $suspensionDate);

    $wrestler->refresh();
    expect($wrestler->isSuspended())->toBeTrue();

    $this->assertDatabaseHas('wrestlers_suspensions', [
        'wrestler_id' => $wrestler->id,
        'started_at' => $suspensionDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it suspends wrestler with current timestamp by default', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    SuspendAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->isSuspended())->toBeTrue();

    $this->assertDatabaseHas('wrestlers_suspensions', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it uses StatusTransitionPipeline for suspension', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler->currentSuspension)->toBeNull();

    SuspendAction::run($wrestler);

    $wrestler->refresh();

    // Verify suspension created through pipeline
    expect($wrestler->currentSuspension)->not()->toBeNull();
    expect($wrestler->isSuspended())->toBeTrue();

    $this->assertDatabaseHas('wrestlers_suspensions', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it handles DateHelper date resolution', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    // Test with null date (should use now())
    SuspendAction::run($wrestler, null);

    $wrestler->refresh();
    expect($wrestler->isSuspended())->toBeTrue();

    $this->assertDatabaseHas('wrestlers_suspensions', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->toDateTimeString(),
    ]);
});

test('it handles multiple suspension scenarios', function () {
    $wrestler = Wrestler::factory()->create();

    // Create old suspension record (already ended)
    $wrestler->suspensions()->create([
        'started_at' => now()->subDays(30),
        'ended_at' => now()->subDays(20),
    ]);

    // Employ the wrestler
    $wrestler->employments()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => null,
    ]);

    expect($wrestler->isEmployed())->toBeTrue();
    expect($wrestler->isSuspended())->toBeFalse();

    SuspendAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->isSuspended())->toBeTrue();

    // New suspension should be created
    $this->assertDatabaseHas('wrestlers_suspensions', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);

    // Old suspension should remain unchanged
    $this->assertDatabaseHas('wrestlers_suspensions', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->subDays(30)->toDateTimeString(),
        'ended_at' => now()->subDays(20)->toDateTimeString(),
    ]);
});

test('it prevents suspending already suspended wrestler', function () {
    $wrestler = Wrestler::factory()->suspended()->create();

    expect($wrestler->isSuspended())->toBeTrue();

    expect(fn () => SuspendAction::run($wrestler))
        ->toThrow(Exception::class);
});

test('it prevents suspending retired wrestler', function () {
    $wrestler = Wrestler::factory()->retired()->create();

    expect($wrestler->isRetired())->toBeTrue();

    expect(fn () => SuspendAction::run($wrestler))
        ->toThrow(Exception::class);
});

test('it prevents suspending unemployed wrestler', function () {
    $wrestler = Wrestler::factory()->create(); // Unemployed by default

    expect($wrestler->isEmployed())->toBeFalse();

    expect(fn () => SuspendAction::run($wrestler))
        ->toThrow(Exception::class);
});

test('it can suspend injured employed wrestler', function () {
    // Create employed wrestler who then gets injured
    $wrestler = Wrestler::factory()->employed()->create();
    $wrestler->injuries()->create([
        'started_at' => now()->subDays(2),
        'ended_at' => null,
    ]);

    expect($wrestler->isEmployed())->toBeTrue();
    expect($wrestler->isInjured())->toBeTrue();

    SuspendAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->isSuspended())->toBeTrue();
    expect($wrestler->isInjured())->toBeTrue(); // Should remain injured
    expect($wrestler->isEmployed())->toBeTrue(); // Suspension preserves employment

    $this->assertDatabaseHas('wrestlers_suspensions', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});
