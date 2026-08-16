<?php

declare(strict_types=1);

use App\Actions\Referees\HealAction;
use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException;
use App\Models\Roster\Referees\Referee;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it heals an injured referee', function () {
    $referee = Referee::factory()->injured()->create();
    $injury = $referee->currentInjury()->firstOrFail();

    expect($referee->isInjured())->toBeTrue();
    expect($injury->ended_at)->toBeNull();

    resolve(HealAction::class)->handle($referee);

    $referee->refresh();
    $injury->refresh();

    expect($referee->isInjured())->toBeFalse();
    expect($injury->ended_at)->not->toBeNull();

    $this->assertDatabaseHas('injuries', [
        'id' => $injury->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it heals referee with specific recovery date', function () {
    $referee = Referee::factory()->injured()->create();
    $injury = $referee->currentInjury()->firstOrFail();
    $recoveryDate = now()->subDays(2);

    resolve(HealAction::class)->handle($referee, $recoveryDate);

    $referee->refresh();
    $injury->refresh();

    expect($referee->isInjured())->toBeFalse();
    expect(requiredDate($injury->ended_at)->toDateTimeString())->toBe($recoveryDate->toDateTimeString());

    $this->assertDatabaseHas('injuries', [
        'id' => $injury->id,
        'ended_at' => $recoveryDate->toDateTimeString(),
    ]);
});

test('it persists the healing lifecycle', function () {
    $referee = Referee::factory()->injured()->create();

    expect($referee->isInjured())->toBeTrue();

    resolve(HealAction::class)->handle($referee);

    $referee->refresh();

    // Verify the injury period was closed consistently
    expect($referee->isInjured())->toBeFalse();
    expect($referee->isEmployed())->toBeTrue(); // Should remain employed after healing
});

test('it handles DateHelper date resolution', function () {
    $referee = Referee::factory()->injured()->create();
    $recoveryDate = now()->subDays(5);

    resolve(HealAction::class)->handle($referee, $recoveryDate);

    $referee->refresh();

    // DateHelper should have processed the recovery date
    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $referee->id,
        'injurable_type' => $referee->getMorphClass(),
        'ended_at' => $recoveryDate->toDateTimeString(),
    ]);
});

test('it validates referee can be healed', function () {
    $referee = Referee::factory()->injured()->create();

    // Should succeed without throwing validation exception
    resolve(HealAction::class)->handle($referee);

    $referee->refresh();
    expect($referee->isInjured())->toBeFalse();
});

test('it throws exception when referee cannot be healed', function () {
    $referee = Referee::factory()->employed()->create(); // Not injured

    expect($referee->isInjured())->toBeFalse();

    expect(fn () => resolve(HealAction::class)->handle($referee))
        ->toThrow(CannotBeClearedFromInjuryException::class);
});

test('it maintains referee employment status after healing', function () {
    $referee = Referee::factory()->injured()->create();
    $employment = $referee->currentEmployment()->firstOrFail();

    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isInjured())->toBeTrue();

    resolve(HealAction::class)->handle($referee);

    $referee->refresh();
    $employment->refresh();

    // Should remain employed after healing
    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isInjured())->toBeFalse();
    expect($employment->ended_at)->toBeNull();
});

test('it preserves injury history', function () {
    $referee = Referee::factory()->injured()->create();
    $injury = $referee->currentInjury()->firstOrFail();
    $originalStartedAt = $injury->started_at;

    resolve(HealAction::class)->handle($referee);

    $injury->refresh();

    // Injury record should be preserved with ended_at set
    $this->assertDatabaseHas('injuries', [
        'id' => $injury->id,
        'injurable_id' => $referee->id,
        'injurable_type' => $referee->getMorphClass(),
        'started_at' => $originalStartedAt->toDateTimeString(),
        'ended_at' => now()->toDateTimeString(),
    ]);
});
