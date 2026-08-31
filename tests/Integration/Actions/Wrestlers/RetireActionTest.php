<?php

declare(strict_types=1);

use App\Actions\Wrestlers\RetireAction;
use App\Models\Roster\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it retires an employed wrestler', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler->currentEmployment()->exists())->toBeTrue();
    expect($wrestler->currentRetirement()->exists())->toBeFalse();

    resolve(RetireAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->currentRetirement()->exists())->toBeTrue();
    expect($wrestler->currentEmployment()->exists())->toBeFalse(); // Should no longer be employed when retired

    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $wrestler->id,
        'retirable_type' => $wrestler->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it retires wrestler with specific retirement date', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $retirementDate = now()->subDays(7);

    resolve(RetireAction::class)->handle($wrestler, $retirementDate);

    $wrestler->refresh();
    expect($wrestler->currentRetirement()->exists())->toBeTrue();

    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $wrestler->id,
        'retirable_type' => $wrestler->getMorphClass(),
        'started_at' => $retirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it persists the retirement lifecycle', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler->currentRetirement)->toBeNull();

    resolve(RetireAction::class)->handle($wrestler);

    $wrestler->refresh();

    // Verify retirement period was created
    expect($wrestler->currentRetirement)->not()->toBeNull();
    expect($wrestler->currentRetirement()->exists())->toBeTrue();

    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $wrestler->id,
        'retirable_type' => $wrestler->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it uses the current time when no date is provided', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    // Test with null date (should use now())
    resolve(RetireAction::class)->handle($wrestler, null);

    $wrestler->refresh();
    expect($wrestler->currentRetirement()->exists())->toBeTrue();

    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $wrestler->id,
        'retirable_type' => $wrestler->getMorphClass(),
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

    expect($wrestler->currentEmployment()->exists())->toBeTrue();
    expect($wrestler->currentRetirement()->exists())->toBeFalse();

    resolve(RetireAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->currentRetirement()->exists())->toBeTrue();

    // New retirement should be created
    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $wrestler->id,
        'retirable_type' => $wrestler->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);

    // Old retirement should remain unchanged
    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $wrestler->id,
        'retirable_type' => $wrestler->getMorphClass(),
        'started_at' => now()->subDays(60)->toDateTimeString(),
        'ended_at' => now()->subDays(30)->toDateTimeString(),
    ]);
});

test('it ends employment when retiring', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    // Get the current employment
    $currentEmployment = $wrestler->currentEmployment()->firstOrFail();
    expect($currentEmployment->ended_at)->toBeNull();

    resolve(RetireAction::class)->handle($wrestler);

    $wrestler->refresh();

    // Employment should be ended
    $this->assertDatabaseHas('employments', [
        'id' => $currentEmployment->id,
        'employable_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    expect($wrestler->currentEmployment()->exists())->toBeFalse();
    expect($wrestler->currentRetirement()->exists())->toBeTrue();
});

test('it prevents retiring already retired wrestler', function () {
    $wrestler = Wrestler::factory()->retired()->create();

    expect($wrestler->currentRetirement()->exists())->toBeTrue();

    expect(fn () => resolve(RetireAction::class)->handle($wrestler))
        ->toThrow(Exception::class);
});

test('it prevents retiring unemployed wrestler', function () {
    $wrestler = Wrestler::factory()->create(); // Unemployed by default

    expect($wrestler->currentEmployment()->exists())->toBeFalse();
    expect($wrestler->currentRetirement()->exists())->toBeFalse();

    expect(fn () => resolve(RetireAction::class)->handle($wrestler))
        ->toThrow(Exception::class);
});

test('it can retire suspended wrestler', function () {
    $wrestler = Wrestler::factory()->suspended()->create();

    expect($wrestler->currentSuspension()->exists())->toBeTrue();
    expect($wrestler->currentEmployment()->exists())->toBeTrue();

    // Suspended wrestlers can be retired (career-ending situation)
    resolve(RetireAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->currentRetirement()->exists())->toBeTrue();
    expect($wrestler->currentSuspension()->exists())->toBeFalse();

    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $wrestler->id,
        'retirable_type' => $wrestler->getMorphClass(),
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

    expect($wrestler->currentEmployment()->exists())->toBeTrue();
    expect($wrestler->isInjured())->toBeTrue();

    resolve(RetireAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->currentRetirement()->exists())->toBeTrue();
    expect($wrestler->isInjured())->toBeFalse();
    expect($wrestler->currentEmployment()->exists())->toBeFalse(); // Employment should end

    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $wrestler->id,
        'retirable_type' => $wrestler->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});
