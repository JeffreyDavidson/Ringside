<?php

declare(strict_types=1);

use App\Actions\Referees\SuspendAction;
use App\Exceptions\Roster\CannotBeSuspendedException;
use App\Models\Referees\Referee;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it suspends an employed referee', function () {
    $referee = Referee::factory()->employed()->create();

    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isSuspended())->toBeFalse();

    SuspendAction::run($referee);

    $referee->refresh();
    expect($referee->isSuspended())->toBeTrue();

    $this->assertDatabaseHas('referees_suspensions', [
        'referee_id' => $referee->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it suspends referee with specific suspension date', function () {
    $referee = Referee::factory()->employed()->create();
    $suspensionDate = now()->subDays(5);

    SuspendAction::run($referee, $suspensionDate);

    $referee->refresh();
    expect($referee->isSuspended())->toBeTrue();

    $this->assertDatabaseHas('referees_suspensions', [
        'referee_id' => $referee->id,
        'started_at' => $suspensionDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it handles DateHelper date resolution', function () {
    $referee = Referee::factory()->employed()->create();
    $suspensionDate = now()->subDays(3);

    SuspendAction::run($referee, $suspensionDate);

    $referee->refresh();

    // DateHelper should have processed the suspension date
    $this->assertDatabaseHas('referees_suspensions', [
        'referee_id' => $referee->id,
        'started_at' => $suspensionDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it validates referee can be suspended', function () {
    $referee = Referee::factory()->employed()->create();

    // Should succeed without throwing validation exception
    SuspendAction::run($referee);

    $referee->refresh();
    expect($referee->isSuspended())->toBeTrue();
});

test('it throws exception when referee cannot be suspended', function () {
    $referee = Referee::factory()->create(); // Not employed

    expect($referee->isEmployed())->toBeFalse();

    expect(fn () => SuspendAction::run($referee))
        ->toThrow(CannotBeSuspendedException::class);
});

test('it maintains referee employment after suspension', function () {
    $referee = Referee::factory()->employed()->create();
    $employment = $referee->currentEmployment;

    expect($referee->isEmployed())->toBeTrue();

    SuspendAction::run($referee);

    $referee->refresh();
    $employment->refresh();

    // Should remain employed after suspension
    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isSuspended())->toBeTrue();
    expect($employment->ended_at)->toBeNull();
});

test('it creates suspension record with correct structure', function () {
    $referee = Referee::factory()->employed()->create();
    $suspensionDate = now()->subDays(1);

    SuspendAction::run($referee, $suspensionDate);

    $suspension = $referee->fresh()->currentSuspension;

    expect($suspension)->not->toBeNull();
    expect($suspension->referee_id)->toBe($referee->id);
    expect($suspension->started_at->toDateTimeString())->toBe($suspensionDate->toDateTimeString());
    expect($suspension->ended_at)->toBeNull();
});
