<?php

declare(strict_types=1);

use App\Actions\Referees\RetireAction;
use App\Exceptions\Roster\CannotBeRetiredException;
use App\Models\Referees\Referee;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it retires an employed referee', function () {
    $referee = Referee::factory()->employed()->create();
    $employment = $referee->currentEmployment;

    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isRetired())->toBeFalse();

    RetireAction::run($referee);

    $referee->refresh();
    $employment->refresh();

    expect($referee->isRetired())->toBeTrue();
    expect($referee->isEmployed())->toBeFalse();
    expect($employment->ended_at)->not->toBeNull();

    $this->assertDatabaseHas('referees_retirements', [
        'referee_id' => $referee->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it retires referee with specific retirement date', function () {
    $referee = Referee::factory()->employed()->create();
    $retirementDate = now()->subDays(10);

    RetireAction::run($referee, $retirementDate);

    $referee->refresh();
    expect($referee->isRetired())->toBeTrue();

    $this->assertDatabaseHas('referees_retirements', [
        'referee_id' => $referee->id,
        'started_at' => $retirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);

    // Employment should be ended on the same date
    $this->assertDatabaseHas('referees_employments', [
        'referee_id' => $referee->id,
        'ended_at' => $retirementDate->toDateTimeString(),
    ]);
});

test('it handles DateHelper date resolution', function () {
    $referee = Referee::factory()->employed()->create();
    $retirementDate = now()->subDays(7);

    RetireAction::run($referee, $retirementDate);

    $referee->refresh();

    // DateHelper should have processed the retirement date
    $this->assertDatabaseHas('referees_retirements', [
        'referee_id' => $referee->id,
        'started_at' => $retirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it validates referee can be retired', function () {
    $referee = Referee::factory()->employed()->create();

    // Should succeed without throwing validation exception
    RetireAction::run($referee);

    $referee->refresh();
    expect($referee->isRetired())->toBeTrue();
});

test('it throws exception when referee cannot be retired', function () {
    $referee = Referee::factory()->create(); // Not employed

    expect($referee->isEmployed())->toBeFalse();

    expect(fn () => RetireAction::run($referee))
        ->toThrow(CannotBeRetiredException::class);
});

test('it ends employment when retiring', function () {
    $referee = Referee::factory()->employed()->create();
    $employment = $referee->currentEmployment;

    expect($employment->ended_at)->toBeNull();

    RetireAction::run($referee);

    $employment->refresh();
    expect($employment->ended_at)->not->toBeNull();

    $this->assertDatabaseHas('referees_employments', [
        'id' => $employment->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it ends suspension before retiring', function () {
    $referee = Referee::factory()->employed()->suspended()->create();
    $suspension = $referee->currentSuspension;

    expect($referee->isSuspended())->toBeTrue();
    expect($suspension->ended_at)->toBeNull();

    RetireAction::run($referee);

    $referee->refresh();
    $suspension->refresh();

    expect($referee->isRetired())->toBeTrue();
    expect($referee->isSuspended())->toBeFalse();
    expect($suspension->ended_at)->not->toBeNull();
});

test('it ends injury before retiring', function () {
    $referee = Referee::factory()->employed()->injured()->create();
    $injury = $referee->currentInjury;

    expect($referee->isInjured())->toBeTrue();
    expect($injury->ended_at)->toBeNull();

    RetireAction::run($referee);

    $referee->refresh();
    $injury->refresh();

    expect($referee->isRetired())->toBeTrue();
    expect($referee->isInjured())->toBeFalse();
    expect($injury->ended_at)->not->toBeNull();
});

test('it creates retirement record with correct structure', function () {
    $referee = Referee::factory()->employed()->create();
    $retirementDate = now()->subDays(5);

    RetireAction::run($referee, $retirementDate);

    $retirement = $referee->fresh()->currentRetirement;

    expect($retirement)->not->toBeNull();
    expect($retirement->referee_id)->toBe($referee->id);
    expect($retirement->started_at->toDateTimeString())->toBe($retirementDate->toDateTimeString());
    expect($retirement->ended_at)->toBeNull();
});
