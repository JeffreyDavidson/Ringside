<?php

declare(strict_types=1);

use App\Actions\Referees\ClearFromInjuryAction;
use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException;
use App\Models\Roster\Referees\Referee;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it clears an injured referee', function () {
    $referee = Referee::factory()->injured()->create();
    $injury = $referee->currentInjury()->firstOrFail();

    expect($referee->currentInjury()->exists())->toBeTrue();
    expect($injury->ended_at)->toBeNull();

    resolve(ClearFromInjuryAction::class)->handle($referee);

    $referee->refresh();
    $injury->refresh();

    expect($referee->currentInjury()->exists())->toBeFalse();
    expect($injury->ended_at)->not->toBeNull();

    $this->assertDatabaseHas('injuries', [
        'id' => $injury->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it clears referee from injury with specific recovery date', function () {
    $referee = Referee::factory()->injured()->create();
    $injury = $referee->currentInjury()->firstOrFail();
    $recoveryDate = now()->subDays(2);

    resolve(ClearFromInjuryAction::class)->handle($referee, $recoveryDate);

    $referee->refresh();
    $injury->refresh();

    expect($referee->currentInjury()->exists())->toBeFalse();
    expect(requiredDate($injury->ended_at)->toDateTimeString())->toBe($recoveryDate->toDateTimeString());

    $this->assertDatabaseHas('injuries', [
        'id' => $injury->id,
        'ended_at' => $recoveryDate->toDateTimeString(),
    ]);
});

test('it persists the injury clearance lifecycle', function () {
    $referee = Referee::factory()->injured()->create();

    expect($referee->currentInjury()->exists())->toBeTrue();

    resolve(ClearFromInjuryAction::class)->handle($referee);

    $referee->refresh();

    // Verify the injury period was closed consistently
    expect($referee->currentInjury()->exists())->toBeFalse();
    expect($referee->currentEmployment()->exists())->toBeTrue(); // Should remain employed after injury clearance
});

test('it uses the provided date', function () {
    $referee = Referee::factory()->injured()->create();
    $recoveryDate = now()->subDays(5);

    resolve(ClearFromInjuryAction::class)->handle($referee, $recoveryDate);

    $referee->refresh();

    // The provided recovery date should be persisted
    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $referee->id,
        'injurable_type' => $referee->getMorphClass(),
        'ended_at' => $recoveryDate->toDateTimeString(),
    ]);
});

test('it validates referee can be cleared from injury', function () {
    $referee = Referee::factory()->injured()->create();

    // Should succeed without throwing validation exception
    resolve(ClearFromInjuryAction::class)->handle($referee);

    $referee->refresh();
    expect($referee->currentInjury()->exists())->toBeFalse();
});

test('it throws exception when referee cannot be cleared from injury', function () {
    $referee = Referee::factory()->employed()->create(); // Not injured

    expect($referee->currentInjury()->exists())->toBeFalse();

    expect(fn () => resolve(ClearFromInjuryAction::class)->handle($referee))
        ->toThrow(CannotBeClearedFromInjuryException::class);
});

test('it maintains referee employment status after injury clearance', function () {
    $referee = Referee::factory()->injured()->create();
    $employment = $referee->currentEmployment()->firstOrFail();

    expect($referee->currentEmployment()->exists())->toBeTrue();
    expect($referee->currentInjury()->exists())->toBeTrue();

    resolve(ClearFromInjuryAction::class)->handle($referee);

    $referee->refresh();
    $employment->refresh();

    // Should remain employed after injury clearance
    expect($referee->currentEmployment()->exists())->toBeTrue();
    expect($referee->currentInjury()->exists())->toBeFalse();
    expect($employment->ended_at)->toBeNull();
});

test('it preserves injury history', function () {
    $referee = Referee::factory()->injured()->create();
    $injury = $referee->currentInjury()->firstOrFail();
    $originalStartedAt = $injury->started_at;

    resolve(ClearFromInjuryAction::class)->handle($referee);

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
