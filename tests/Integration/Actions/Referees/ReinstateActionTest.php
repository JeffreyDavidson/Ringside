<?php

declare(strict_types=1);

use App\Actions\Referees\ReinstateAction;
use App\Exceptions\Roster\Individuals\CannotBeReinstatedException;
use App\Models\Roster\Referees\Referee;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it reinstates a suspended referee', function () {
    $referee = Referee::factory()->suspended()->create();
    $suspension = $referee->currentSuspension()->firstOrFail();

    expect($referee->isSuspended())->toBeTrue();
    expect($suspension->ended_at)->toBeNull();

    resolve(ReinstateAction::class)->handle($referee);

    $referee->refresh();
    $suspension->refresh();

    expect($referee->isSuspended())->toBeFalse();
    expect($suspension->ended_at)->not->toBeNull();

    $this->assertDatabaseHas('suspensions', [
        'id' => $suspension->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it prevents reinstating an injured referee', function () {
    $referee = Referee::factory()->injured()->create();
    $injuryId = $referee->currentInjury()->firstOrFail()->id;

    expect(fn () => resolve(ReinstateAction::class)->handle($referee))
        ->toThrow(CannotBeReinstatedException::class);

    $referee->refresh();
    expect($referee->isInjured())->toBeTrue();
    $this->assertDatabaseHas('injuries', [
        'id' => $injuryId,
        'ended_at' => null,
    ]);
});

test('it reinstates referee with specific reinstatement date', function () {
    $referee = Referee::factory()->suspended()->create();
    $suspension = $referee->currentSuspension()->firstOrFail();
    $reinstatementDate = now()->subDays(1);

    resolve(ReinstateAction::class)->handle($referee, $reinstatementDate);

    $referee->refresh();
    $suspension->refresh();

    expect($referee->isSuspended())->toBeFalse();
    expect(requiredDate($suspension->ended_at)->toDateTimeString())->toBe($reinstatementDate->toDateTimeString());

    $this->assertDatabaseHas('suspensions', [
        'id' => $suspension->id,
        'ended_at' => $reinstatementDate->toDateTimeString(),
    ]);
});

test('it handles DateHelper date resolution', function () {
    $referee = Referee::factory()->suspended()->create();
    $reinstatementDate = now()->subDays(2);

    resolve(ReinstateAction::class)->handle($referee, $reinstatementDate);

    $referee->refresh();

    // DateHelper should have processed the reinstatement date
    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $referee->id,
        'suspendable_type' => $referee->getMorphClass(),
        'ended_at' => $reinstatementDate->toDateTimeString(),
    ]);
});

test('it validates referee can be reinstated', function () {
    $referee = Referee::factory()->suspended()->create();

    // Should succeed without throwing validation exception
    resolve(ReinstateAction::class)->handle($referee);

    $referee->refresh();
    expect($referee->isSuspended())->toBeFalse();
});

test('it throws exception when referee cannot be reinstated', function () {
    $referee = Referee::factory()->employed()->create(); // Not suspended

    expect($referee->isSuspended())->toBeFalse();

    expect(fn () => resolve(ReinstateAction::class)->handle($referee))
        ->toThrow(CannotBeReinstatedException::class);
});

test('it maintains referee employment after reinstatement', function () {
    $referee = Referee::factory()->suspended()->create();
    $employment = $referee->currentEmployment()->firstOrFail();

    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isSuspended())->toBeTrue();

    resolve(ReinstateAction::class)->handle($referee);

    $referee->refresh();
    $employment->refresh();

    // Should remain employed after reinstatement
    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isSuspended())->toBeFalse();
    expect($employment->ended_at)->toBeNull();
});

test('it preserves suspension history', function () {
    $referee = Referee::factory()->suspended()->create();
    $suspension = $referee->currentSuspension()->firstOrFail();
    $originalStartedAt = $suspension->started_at;

    resolve(ReinstateAction::class)->handle($referee);

    $suspension->refresh();

    // Suspension record should be preserved with ended_at set
    $this->assertDatabaseHas('suspensions', [
        'id' => $suspension->id,
        'suspendable_id' => $referee->id,
        'suspendable_type' => $referee->getMorphClass(),
        'started_at' => $originalStartedAt->toDateTimeString(),
        'ended_at' => now()->toDateTimeString(),
    ]);
});
