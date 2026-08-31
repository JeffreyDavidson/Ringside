<?php

declare(strict_types=1);

use App\Actions\Wrestlers\SuspendAction;
use App\Exceptions\Roster\Individuals\CannotBeSuspendedException;
use App\Models\Roster\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it suspends an employed wrestler', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler->currentEmployment()->exists())->toBeTrue();
    expect($wrestler->currentSuspension()->exists())->toBeFalse();

    resolve(SuspendAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->currentSuspension()->exists())->toBeTrue();
    expect($wrestler->currentEmployment()->exists())->toBeTrue();

    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $wrestler->id,
        'suspendable_type' => $wrestler->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it suspends wrestler with specific suspension date', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $suspensionDate = now()->subDays(3);

    resolve(SuspendAction::class)->handle($wrestler, $suspensionDate);

    $wrestler->refresh();
    expect($wrestler->currentSuspension()->exists())->toBeTrue();

    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $wrestler->id,
        'suspendable_type' => $wrestler->getMorphClass(),
        'started_at' => $suspensionDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it suspends a wrestler without suspension notes', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    resolve(SuspendAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->currentSuspension()->exists())->toBeTrue();

    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $wrestler->id,
        'suspendable_type' => $wrestler->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it persists the suspension lifecycle', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect($wrestler->currentSuspension)->toBeNull();

    resolve(SuspendAction::class)->handle($wrestler);

    $wrestler->refresh();

    // Verify suspension period was created
    expect($wrestler->currentSuspension)->not()->toBeNull();
    expect($wrestler->currentSuspension()->exists())->toBeTrue();

    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $wrestler->id,
        'suspendable_type' => $wrestler->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it uses the current time when no date is provided', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    // Test with null date (should use now())
    resolve(SuspendAction::class)->handle($wrestler, null);

    $wrestler->refresh();
    expect($wrestler->currentSuspension()->exists())->toBeTrue();

    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $wrestler->id,
        'suspendable_type' => $wrestler->getMorphClass(),
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

    expect($wrestler->currentEmployment()->exists())->toBeTrue();
    expect($wrestler->currentSuspension()->exists())->toBeFalse();

    resolve(SuspendAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->currentSuspension()->exists())->toBeTrue();

    // New suspension should be created
    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $wrestler->id,
        'suspendable_type' => $wrestler->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);

    // Old suspension should remain unchanged
    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $wrestler->id,
        'suspendable_type' => $wrestler->getMorphClass(),
        'started_at' => now()->subDays(30)->toDateTimeString(),
        'ended_at' => now()->subDays(20)->toDateTimeString(),
    ]);
});

test('it prevents suspending already suspended wrestler', function () {
    $wrestler = Wrestler::factory()->suspended()->create();

    expect($wrestler->currentSuspension()->exists())->toBeTrue();

    expect(fn () => resolve(SuspendAction::class)->handle($wrestler))
        ->toThrow(Exception::class);
});

test('it prevents suspending retired wrestler', function () {
    $wrestler = Wrestler::factory()->retired()->create();

    expect($wrestler->currentRetirement()->exists())->toBeTrue();

    expect(fn () => resolve(SuspendAction::class)->handle($wrestler))
        ->toThrow(Exception::class);
});

test('it prevents suspending unemployed wrestler', function () {
    $wrestler = Wrestler::factory()->create(); // Unemployed by default

    expect($wrestler->currentEmployment()->exists())->toBeFalse();

    expect(fn () => resolve(SuspendAction::class)->handle($wrestler))
        ->toThrow(Exception::class);
});

test('it prevents suspending an injured wrestler', function () {
    // Create employed wrestler who then gets injured
    $wrestler = Wrestler::factory()->employed()->create();
    $wrestler->injuries()->create([
        'started_at' => now()->subDays(2),
        'ended_at' => null,
    ]);

    expect($wrestler->currentEmployment()->exists())->toBeTrue();
    expect($wrestler->currentInjury()->exists())->toBeTrue();

    expect(fn () => resolve(SuspendAction::class)->handle($wrestler))
        ->toThrow(CannotBeSuspendedException::class);

    $wrestler->refresh();

    expect($wrestler->currentInjury()->exists())->toBeTrue();
    expect($wrestler->currentSuspension()->exists())->toBeFalse();
    expect($wrestler->currentEmployment()->exists())->toBeTrue();
});
