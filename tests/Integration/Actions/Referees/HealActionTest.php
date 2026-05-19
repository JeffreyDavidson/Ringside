<?php

declare(strict_types=1);

use App\Actions\Referees\HealAction;
use App\Exceptions\Roster\CannotBeClearedFromInjuryException;
use App\Models\Referees\Referee;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it heals an injured referee', function () {
    $referee = Referee::factory()->employed()->injured()->create();
    $injury = $referee->currentInjury;

    expect($referee->isInjured())->toBeTrue();
    expect($injury->ended_at)->toBeNull();

    HealAction::run($referee);

    $referee->refresh();
    $injury->refresh();

    expect($referee->isInjured())->toBeFalse();
    expect($injury->ended_at)->not->toBeNull();

    $this->assertDatabaseHas('referees_injuries', [
        'id' => $injury->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it heals referee with specific recovery date', function () {
    $referee = Referee::factory()->employed()->injured()->create();
    $injury = $referee->currentInjury;
    $recoveryDate = now()->subDays(2);

    HealAction::run($referee, $recoveryDate);

    $referee->refresh();
    $injury->refresh();

    expect($referee->isInjured())->toBeFalse();
    expect($injury->ended_at->toDateTimeString())->toBe($recoveryDate->toDateTimeString());

    $this->assertDatabaseHas('referees_injuries', [
        'id' => $injury->id,
        'ended_at' => $recoveryDate->toDateTimeString(),
    ]);
});

test('it uses StatusTransitionPipeline for consistent healing', function () {
    $referee = Referee::factory()->employed()->injured()->create();

    expect($referee->isInjured())->toBeTrue();

    HealAction::run($referee);

    $referee->refresh();

    // StatusTransitionPipeline should have handled the healing consistently
    expect($referee->isInjured())->toBeFalse();
    expect($referee->isEmployed())->toBeTrue(); // Should remain employed after healing
});

test('it handles DateHelper date resolution', function () {
    $referee = Referee::factory()->employed()->injured()->create();
    $recoveryDate = now()->subDays(5);

    HealAction::run($referee, $recoveryDate);

    $referee->refresh();

    // DateHelper should have processed the recovery date
    $this->assertDatabaseHas('referees_injuries', [
        'referee_id' => $referee->id,
        'ended_at' => $recoveryDate->toDateTimeString(),
    ]);
});

test('it validates referee can be healed', function () {
    $referee = Referee::factory()->employed()->injured()->create();

    // Should succeed without throwing validation exception
    HealAction::run($referee);

    $referee->refresh();
    expect($referee->isInjured())->toBeFalse();
});

test('it throws exception when referee cannot be healed', function () {
    $referee = Referee::factory()->employed()->create(); // Not injured

    expect($referee->isInjured())->toBeFalse();

    expect(fn () => HealAction::run($referee))
        ->toThrow(CannotBeClearedFromInjuryException::class);
});

test('it maintains referee employment status after healing', function () {
    $referee = Referee::factory()->employed()->injured()->create();
    $employment = $referee->currentEmployment;

    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isInjured())->toBeTrue();

    HealAction::run($referee);

    $referee->refresh();
    $employment->refresh();

    // Should remain employed after healing
    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isInjured())->toBeFalse();
    expect($employment->ended_at)->toBeNull();
});

test('it preserves injury history', function () {
    $referee = Referee::factory()->employed()->injured()->create();
    $injury = $referee->currentInjury;
    $originalStartedAt = $injury->started_at;

    HealAction::run($referee);

    $injury->refresh();

    // Injury record should be preserved with ended_at set
    $this->assertDatabaseHas('referees_injuries', [
        'id' => $injury->id,
        'referee_id' => $referee->id,
        'started_at' => $originalStartedAt->toDateTimeString(),
        'ended_at' => now()->toDateTimeString(),
    ]);
});
