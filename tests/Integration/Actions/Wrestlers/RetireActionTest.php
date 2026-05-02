<?php

declare(strict_types=1);

use App\Actions\Wrestlers\RetireAction;
use App\Models\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it retires an employed wrestler', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler->isEmployed())->toBeTrue();
    expect($wrestler->isRetired())->toBeFalse();

    RetireAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->isRetired())->toBeTrue();
    expect($wrestler->isEmployed())->toBeFalse(); // Should no longer be employed when retired

    $this->assertDatabaseHas('wrestlers_retirements', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it retires wrestler with specific retirement date', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $retirementDate = now()->subDays(7);

    RetireAction::run($wrestler, $retirementDate);

    $wrestler->refresh();
    expect($wrestler->isRetired())->toBeTrue();

    $this->assertDatabaseHas('wrestlers_retirements', [
        'wrestler_id' => $wrestler->id,
        'started_at' => $retirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it uses StatusTransitionPipeline for retirement', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler->currentRetirement)->toBeNull();

    RetireAction::run($wrestler);

    $wrestler->refresh();

    // Verify retirement created through pipeline
    expect($wrestler->currentRetirement)->not()->toBeNull();
    expect($wrestler->isRetired())->toBeTrue();

    $this->assertDatabaseHas('wrestlers_retirements', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it handles DateHelper date resolution', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    // Test with null date (should use now())
    RetireAction::run($wrestler, null);

    $wrestler->refresh();
    expect($wrestler->isRetired())->toBeTrue();

    $this->assertDatabaseHas('wrestlers_retirements', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->toDateTimeString(),
    ]);
});

test('it handles multiple retirement scenarios', function () {
    $wrestler = Wrestler::factory()->create();

    // Create old retirement record (already ended - came out of retirement)
    $wrestler->retirements()->create([
        'started_at' => now()->subDays(60),
        'ended_at' => now()->subDays(30),
    ]);

    // Employ the wrestler (post-comeback)
    $wrestler->employments()->create([
        'started_at' => now()->subDays(25),
        'ended_at' => null,
    ]);

    expect($wrestler->isEmployed())->toBeTrue();
    expect($wrestler->isRetired())->toBeFalse();

    RetireAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->isRetired())->toBeTrue();

    // New retirement should be created
    $this->assertDatabaseHas('wrestlers_retirements', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);

    // Old retirement should remain unchanged
    $this->assertDatabaseHas('wrestlers_retirements', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->subDays(60)->toDateTimeString(),
        'ended_at' => now()->subDays(30)->toDateTimeString(),
    ]);
});

test('it ends employment when retiring', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    // Get the current employment
    $currentEmployment = $wrestler->currentEmployment;
    expect($currentEmployment)->not()->toBeNull();
    expect($currentEmployment->ended_at)->toBeNull();

    RetireAction::run($wrestler);

    $wrestler->refresh();

    // Employment should be ended
    $this->assertDatabaseHas('wrestlers_employments', [
        'id' => $currentEmployment->id,
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    expect($wrestler->isEmployed())->toBeFalse();
    expect($wrestler->isRetired())->toBeTrue();
});

test('it prevents retiring already retired wrestler', function () {
    $wrestler = Wrestler::factory()->retired()->create();

    expect($wrestler->isRetired())->toBeTrue();

    expect(fn () => RetireAction::run($wrestler))
        ->toThrow(Exception::class);
});

test('it prevents retiring unemployed wrestler', function () {
    $wrestler = Wrestler::factory()->create(); // Unemployed by default

    expect($wrestler->isEmployed())->toBeFalse();
    expect($wrestler->isRetired())->toBeFalse();

    expect(fn () => RetireAction::run($wrestler))
        ->toThrow(Exception::class);
});

test('it can retire suspended wrestler', function () {
    // Suspended factory creates an employed-and-suspended wrestler (orthogonal states)
    $wrestler = Wrestler::factory()->suspended()->create();

    expect($wrestler->isSuspended())->toBeTrue();
    expect($wrestler->isEmployed())->toBeTrue();

    RetireAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->isRetired())->toBeTrue();
    expect($wrestler->isSuspended())->toBeFalse(); // Retirement ends suspension
    expect($wrestler->isEmployed())->toBeFalse();

    $this->assertDatabaseHas('wrestlers_retirements', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it can retire injured wrestler', function () {
    // Create employed wrestler who then gets injured
    $wrestler = Wrestler::factory()->employed()->create();
    $wrestler->injuries()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    expect($wrestler->isEmployed())->toBeTrue();
    expect($wrestler->isInjured())->toBeTrue();

    RetireAction::run($wrestler);

    $wrestler->refresh();
    expect($wrestler->isRetired())->toBeTrue();
    expect($wrestler->isInjured())->toBeFalse(); // Retirement ends active injury
    expect($wrestler->isEmployed())->toBeFalse();

    $this->assertDatabaseHas('wrestlers_retirements', [
        'wrestler_id' => $wrestler->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});
