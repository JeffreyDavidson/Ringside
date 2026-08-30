<?php

declare(strict_types=1);

use App\Actions\Referees\SuspendAction;
use App\Exceptions\Roster\Individuals\CannotBeSuspendedException;
use App\Models\Roster\Referees\Referee;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it suspends an employed referee', function () {
    $referee = Referee::factory()->employed()->create();

    expect($referee->currentEmployment()->exists())->toBeTrue();
    expect($referee->isSuspended())->toBeFalse();

    resolve(SuspendAction::class)->handle($referee);

    $referee->refresh();
    expect($referee->isSuspended())->toBeTrue();

    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $referee->id,
        'suspendable_type' => $referee->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it suspends referee with specific suspension date', function () {
    $referee = Referee::factory()->employed()->create();
    $suspensionDate = now()->subDays(5);

    resolve(SuspendAction::class)->handle($referee, $suspensionDate);

    $referee->refresh();
    expect($referee->isSuspended())->toBeTrue();

    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $referee->id,
        'suspendable_type' => $referee->getMorphClass(),
        'started_at' => $suspensionDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it uses the provided date', function () {
    $referee = Referee::factory()->employed()->create();
    $suspensionDate = now()->subDays(3);

    resolve(SuspendAction::class)->handle($referee, $suspensionDate);

    $referee->refresh();

    // The provided suspension date should be persisted
    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $referee->id,
        'suspendable_type' => $referee->getMorphClass(),
        'started_at' => $suspensionDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it validates referee can be suspended', function () {
    $referee = Referee::factory()->employed()->create();

    // Should succeed without throwing validation exception
    resolve(SuspendAction::class)->handle($referee);

    $referee->refresh();
    expect($referee->isSuspended())->toBeTrue();
});

test('it throws exception when referee cannot be suspended', function () {
    $referee = Referee::factory()->create(); // Not employed

    expect($referee->currentEmployment()->exists())->toBeFalse();

    expect(fn () => resolve(SuspendAction::class)->handle($referee))
        ->toThrow(CannotBeSuspendedException::class);
});

test('it prevents suspending an injured referee', function () {
    $referee = Referee::factory()->injured()->create();

    expect($referee->currentEmployment()->exists())->toBeTrue();

    expect(fn () => resolve(SuspendAction::class)->handle($referee))
        ->toThrow(CannotBeSuspendedException::class);

    $referee->refresh();

    expect($referee->isInjured())->toBeTrue()
        ->and($referee->isSuspended())->toBeFalse()
        ->and($referee->currentEmployment()->exists())->toBeTrue();
});

test('it maintains referee employment after suspension', function () {
    $referee = Referee::factory()->employed()->create();
    $employment = $referee->currentEmployment()->firstOrFail();

    expect($referee->currentEmployment()->exists())->toBeTrue();

    resolve(SuspendAction::class)->handle($referee);

    $referee->refresh();
    $employment->refresh();

    // Should remain employed after suspension
    expect($referee->currentEmployment()->exists())->toBeTrue();
    expect($referee->isSuspended())->toBeTrue();
    expect($employment->ended_at)->toBeNull();
});

test('it creates suspension record with correct structure', function () {
    $referee = Referee::factory()->employed()->create();
    $suspensionDate = now()->subDays(1);

    resolve(SuspendAction::class)->handle($referee, $suspensionDate);

    $suspension = freshModel($referee)->currentSuspension()->firstOrFail();

    expect($suspension)->not->toBeNull();
    expect($suspension->suspendable->is($referee))->toBeTrue();
    expect(requiredDate($suspension->started_at)->toDateTimeString())->toBe($suspensionDate->toDateTimeString());
    expect($suspension->ended_at)->toBeNull();
});
