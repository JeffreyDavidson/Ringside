<?php

declare(strict_types=1);

use App\Actions\Referees\RetireAction;
use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Models\Referees\Referee;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it retires an employed referee', function () {
    $referee = Referee::factory()->employed()->create();
    $employment = $referee->currentEmployment()->firstOrFail();

    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isRetired())->toBeFalse();

    resolve(RetireAction::class)->handle($referee);

    $referee->refresh();
    $employment->refresh();

    expect($referee->isRetired())->toBeTrue();
    expect($referee->isEmployed())->toBeFalse();
    expect($employment->ended_at)->not->toBeNull();

    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $referee->id,
        'retirable_type' => $referee->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it retires referee with specific retirement date', function () {
    $referee = Referee::factory()->employed()->create();
    $retirementDate = now()->subDays(10);

    resolve(RetireAction::class)->handle($referee, $retirementDate);

    $referee->refresh();
    expect($referee->isRetired())->toBeTrue();

    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $referee->id,
        'retirable_type' => $referee->getMorphClass(),
        'started_at' => $retirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);

    // Employment should be ended on the same date
    $this->assertDatabaseHas('employments', [
        'employable_id' => $referee->id,
        'ended_at' => $retirementDate->toDateTimeString(),
    ]);
});

test('it handles DateHelper date resolution', function () {
    $referee = Referee::factory()->employed()->create();
    $retirementDate = now()->subDays(7);

    resolve(RetireAction::class)->handle($referee, $retirementDate);

    $referee->refresh();

    // DateHelper should have processed the retirement date
    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $referee->id,
        'retirable_type' => $referee->getMorphClass(),
        'started_at' => $retirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it validates referee can be retired', function () {
    $referee = Referee::factory()->employed()->create();

    // Should succeed without throwing validation exception
    resolve(RetireAction::class)->handle($referee);

    $referee->refresh();
    expect($referee->isRetired())->toBeTrue();
});

test('it throws exception when referee cannot be retired', function () {
    $referee = Referee::factory()->create(); // Not employed

    expect($referee->isEmployed())->toBeFalse();

    expect(fn () => resolve(RetireAction::class)->handle($referee))
        ->toThrow(CannotBeRetiredException::class);
});

test('it ends employment when retiring', function () {
    $referee = Referee::factory()->employed()->create();
    $employment = $referee->currentEmployment()->firstOrFail();

    expect($employment->ended_at)->toBeNull();

    resolve(RetireAction::class)->handle($referee);

    $employment->refresh();
    expect($employment->ended_at)->not->toBeNull();

    $this->assertDatabaseHas('employments', [
        'id' => $employment->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it ends suspension before retiring', function () {
    $referee = Referee::factory()->suspended()->create();
    $suspension = $referee->currentSuspension()->firstOrFail();

    expect($referee->isSuspended())->toBeTrue();
    expect($suspension->ended_at)->toBeNull();

    resolve(RetireAction::class)->handle($referee);

    $referee->refresh();
    $suspension->refresh();

    expect($referee->isRetired())->toBeTrue();
    expect($referee->isSuspended())->toBeFalse();
    expect($suspension->ended_at)->not->toBeNull();
});

test('it ends injury before retiring', function () {
    $referee = Referee::factory()->injured()->create();
    $injury = $referee->currentInjury()->firstOrFail();

    expect($referee->isInjured())->toBeTrue();
    expect($injury->ended_at)->toBeNull();

    resolve(RetireAction::class)->handle($referee);

    $referee->refresh();
    $injury->refresh();

    expect($referee->isRetired())->toBeTrue();
    expect($referee->isInjured())->toBeFalse();
    expect($injury->ended_at)->not->toBeNull();
});

test('it creates retirement record with correct structure', function () {
    $referee = Referee::factory()->employed()->create();
    $retirementDate = now()->subDays(5);

    resolve(RetireAction::class)->handle($referee, $retirementDate);

    $retirement = freshModel($referee)->currentRetirement()->firstOrFail();

    expect($retirement)->not->toBeNull();
    expect($retirement->retirable->is($referee))->toBeTrue();
    expect(requiredDate($retirement->started_at)->toDateTimeString())->toBe($retirementDate->toDateTimeString());
    expect($retirement->ended_at)->toBeNull();
});
