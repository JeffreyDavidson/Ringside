<?php

declare(strict_types=1);

use App\Actions\Referees\ReinstateAction;
use App\Exceptions\Roster\CannotBeReinstatedException;
use App\Models\Referees\Referee;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it reinstates a suspended referee', function () {
    $referee = Referee::factory()->employed()->suspended()->create();
    $suspension = $referee->currentSuspension;

    expect($referee->isSuspended())->toBeTrue();
    expect($suspension->ended_at)->toBeNull();

    ReinstateAction::run($referee);

    $referee->refresh();
    $suspension->refresh();

    expect($referee->isSuspended())->toBeFalse();
    expect($suspension->ended_at)->not->toBeNull();

    $this->assertDatabaseHas('referees_suspensions', [
        'id' => $suspension->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it reinstates referee with specific reinstatement date', function () {
    $referee = Referee::factory()->employed()->suspended()->create();
    $suspension = $referee->currentSuspension;
    $reinstatementDate = now()->subDays(1);

    ReinstateAction::run($referee, $reinstatementDate);

    $referee->refresh();
    $suspension->refresh();

    expect($referee->isSuspended())->toBeFalse();
    expect($suspension->ended_at->toDateTimeString())->toBe($reinstatementDate->toDateTimeString());

    $this->assertDatabaseHas('referees_suspensions', [
        'id' => $suspension->id,
        'ended_at' => $reinstatementDate->toDateTimeString(),
    ]);
});

test('it handles DateHelper date resolution', function () {
    $referee = Referee::factory()->employed()->suspended()->create();
    $reinstatementDate = now()->subDays(2);

    ReinstateAction::run($referee, $reinstatementDate);

    $referee->refresh();

    // DateHelper should have processed the reinstatement date
    $this->assertDatabaseHas('referees_suspensions', [
        'referee_id' => $referee->id,
        'ended_at' => $reinstatementDate->toDateTimeString(),
    ]);
});

test('it validates referee can be reinstated', function () {
    $referee = Referee::factory()->employed()->suspended()->create();

    // Should succeed without throwing validation exception
    ReinstateAction::run($referee);

    $referee->refresh();
    expect($referee->isSuspended())->toBeFalse();
});

test('it throws exception when referee cannot be reinstated', function () {
    $referee = Referee::factory()->employed()->create(); // Not suspended

    expect($referee->isSuspended())->toBeFalse();

    expect(fn () => ReinstateAction::run($referee))
        ->toThrow(CannotBeReinstatedException::class);
});

test('it maintains referee employment after reinstatement', function () {
    $referee = Referee::factory()->employed()->suspended()->create();
    $employment = $referee->currentEmployment;

    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isSuspended())->toBeTrue();

    ReinstateAction::run($referee);

    $referee->refresh();
    $employment->refresh();

    // Should remain employed after reinstatement
    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isSuspended())->toBeFalse();
    expect($employment->ended_at)->toBeNull();
});

test('it preserves suspension history', function () {
    $referee = Referee::factory()->employed()->suspended()->create();
    $suspension = $referee->currentSuspension;
    $originalStartedAt = $suspension->started_at;

    ReinstateAction::run($referee);

    $suspension->refresh();

    // Suspension record should be preserved with ended_at set
    $this->assertDatabaseHas('referees_suspensions', [
        'id' => $suspension->id,
        'referee_id' => $referee->id,
        'started_at' => $originalStartedAt->toDateTimeString(),
        'ended_at' => now()->toDateTimeString(),
    ]);
});
