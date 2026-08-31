<?php

declare(strict_types=1);

use App\Actions\Referees\ReleaseAction;
use App\Exceptions\Roster\Individuals\CannotBeReleasedException;
use App\Models\Roster\Referees\Referee;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it releases an employed referee', function () {
    $referee = Referee::factory()->employed()->create();
    $employment = $referee->currentEmployment()->firstOrFail();

    expect($referee->currentEmployment()->exists())->toBeTrue();
    expect($employment->ended_at)->toBeNull();

    resolve(ReleaseAction::class)->handle($referee);

    $referee->refresh();
    $employment->refresh();

    expect($referee->currentEmployment()->exists())->toBeFalse();
    expect($employment->ended_at)->not->toBeNull();

    $this->assertDatabaseHas('employments', [
        'id' => $employment->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it releases referee with specific release date', function () {
    $referee = Referee::factory()->employed()->create();
    $employment = $referee->currentEmployment()->firstOrFail();
    $releaseDate = now()->subDays(4);

    resolve(ReleaseAction::class)->handle($referee, $releaseDate);

    $referee->refresh();
    $employment->refresh();

    expect($referee->currentEmployment()->exists())->toBeFalse();
    expect(requiredDate($employment->ended_at)->toDateTimeString())->toBe($releaseDate->toDateTimeString());

    $this->assertDatabaseHas('employments', [
        'id' => $employment->id,
        'ended_at' => $releaseDate->toDateTimeString(),
    ]);
});

test('it uses the provided date', function () {
    $referee = Referee::factory()->employed()->create();
    $releaseDate = now()->subDays(6);

    resolve(ReleaseAction::class)->handle($referee, $releaseDate);

    $referee->refresh();

    // The provided release date should be persisted
    $this->assertDatabaseHas('employments', [
        'employable_id' => $referee->id,
        'ended_at' => $releaseDate->toDateTimeString(),
    ]);
});

test('it validates referee can be released', function () {
    $referee = Referee::factory()->employed()->create();

    // Should succeed without throwing validation exception
    resolve(ReleaseAction::class)->handle($referee);

    $referee->refresh();
    expect($referee->currentEmployment()->exists())->toBeFalse();
});

test('it throws exception when referee cannot be released', function () {
    $referee = Referee::factory()->create(); // Not employed

    expect($referee->currentEmployment()->exists())->toBeFalse();

    expect(fn () => resolve(ReleaseAction::class)->handle($referee))
        ->toThrow(CannotBeReleasedException::class);
});

test('it ends suspension before releasing', function () {
    $referee = Referee::factory()->suspended()->create();
    $suspension = $referee->currentSuspension()->firstOrFail();

    expect($referee->currentSuspension()->exists())->toBeTrue();
    expect($suspension->ended_at)->toBeNull();

    resolve(ReleaseAction::class)->handle($referee);

    $referee->refresh();
    $suspension->refresh();

    expect($referee->currentEmployment()->exists())->toBeFalse();
    expect($referee->currentSuspension()->exists())->toBeFalse();
    expect($suspension->ended_at)->not->toBeNull();
});

test('it ends injury before releasing', function () {
    $referee = Referee::factory()->injured()->create();
    $injury = $referee->currentInjury()->firstOrFail();

    expect($referee->currentInjury()->exists())->toBeTrue();
    expect($injury->ended_at)->toBeNull();

    resolve(ReleaseAction::class)->handle($referee);

    $referee->refresh();
    $injury->refresh();

    expect($referee->currentEmployment()->exists())->toBeFalse();
    expect($referee->currentInjury()->exists())->toBeFalse();
    expect($injury->ended_at)->not->toBeNull();
});

test('it maintains transaction boundaries', function () {
    $referee = Referee::factory()->suspended()->create();
    $employment = $referee->currentEmployment()->firstOrFail();
    $suspension = $referee->currentSuspension()->firstOrFail();

    resolve(ReleaseAction::class)->handle($referee);

    $referee->refresh();
    $employment->refresh();
    $suspension->refresh();

    expect($referee->currentEmployment()->exists())->toBeFalse();
    expect($referee->currentSuspension()->exists())->toBeFalse();
    expect($employment->ended_at)->not->toBeNull();
    expect($suspension->ended_at)->not->toBeNull();
});

test('it preserves employment history', function () {
    $referee = Referee::factory()->employed()->create();
    $employment = $referee->currentEmployment()->firstOrFail();
    $originalStartedAt = $employment->started_at;

    resolve(ReleaseAction::class)->handle($referee);

    $employment->refresh();

    // Employment record should be preserved with ended_at set
    $this->assertDatabaseHas('employments', [
        'id' => $employment->id,
        'employable_id' => $referee->id,
        'started_at' => $originalStartedAt->toDateTimeString(),
        'ended_at' => now()->toDateTimeString(),
    ]);
});
