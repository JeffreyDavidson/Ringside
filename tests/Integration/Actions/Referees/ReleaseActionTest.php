<?php

declare(strict_types=1);

use App\Actions\Referees\ReleaseAction;
use App\Exceptions\Roster\CannotBeReleasedException;
use App\Models\Referees\Referee;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it releases an employed referee', function () {
    $referee = Referee::factory()->employed()->create();
    $employment = $referee->currentEmployment;

    expect($referee->isEmployed())->toBeTrue();
    expect($employment->ended_at)->toBeNull();

    ReleaseAction::run($referee);

    $referee->refresh();
    $employment->refresh();

    expect($referee->isEmployed())->toBeFalse();
    expect($employment->ended_at)->not->toBeNull();

    $this->assertDatabaseHas('referees_employments', [
        'id' => $employment->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it releases referee with specific release date', function () {
    $referee = Referee::factory()->employed()->create();
    $employment = $referee->currentEmployment;
    $releaseDate = now()->subDays(4);

    ReleaseAction::run($referee, $releaseDate);

    $referee->refresh();
    $employment->refresh();

    expect($referee->isEmployed())->toBeFalse();
    expect($employment->ended_at->eq($releaseDate))->toBeTrue();

    $this->assertDatabaseHas('referees_employments', [
        'id' => $employment->id,
        'ended_at' => $releaseDate->toDateTimeString(),
    ]);
});

test('it handles DateHelper date resolution', function () {
    $referee = Referee::factory()->employed()->create();
    $releaseDate = now()->subDays(6);

    ReleaseAction::run($referee, $releaseDate);

    $referee->refresh();

    // DateHelper should have processed the release date
    $this->assertDatabaseHas('referees_employments', [
        'referee_id' => $referee->id,
        'ended_at' => $releaseDate->toDateTimeString(),
    ]);
});

test('it validates referee can be released', function () {
    $referee = Referee::factory()->employed()->create();

    // Should succeed without throwing validation exception
    ReleaseAction::run($referee);

    $referee->refresh();
    expect($referee->isEmployed())->toBeFalse();
});

test('it throws exception when referee cannot be released', function () {
    $referee = Referee::factory()->create(); // Not employed

    expect($referee->isEmployed())->toBeFalse();

    expect(fn () => ReleaseAction::run($referee))
        ->toThrow(CannotBeReleasedException::class);
});

test('it ends suspension before releasing', function () {
    $referee = Referee::factory()->employed()->suspended()->create();
    $suspension = $referee->currentSuspension;

    expect($referee->isSuspended())->toBeTrue();
    expect($suspension->ended_at)->toBeNull();

    ReleaseAction::run($referee);

    $referee->refresh();
    $suspension->refresh();

    expect($referee->isEmployed())->toBeFalse();
    expect($referee->isSuspended())->toBeFalse();
    expect($suspension->ended_at)->not->toBeNull();
});

test('it ends injury before releasing', function () {
    $referee = Referee::factory()->employed()->injured()->create();
    $injury = $referee->currentInjury;

    expect($referee->isInjured())->toBeTrue();
    expect($injury->ended_at)->toBeNull();

    ReleaseAction::run($referee);

    $referee->refresh();
    $injury->refresh();

    expect($referee->isEmployed())->toBeFalse();
    expect($referee->isInjured())->toBeFalse();
    expect($injury->ended_at)->not->toBeNull();
});

test('it maintains transaction boundaries', function () {
    $referee = Referee::factory()->employed()->suspended()->create();
    $employment = $referee->currentEmployment;
    $suspension = $referee->currentSuspension;

    ReleaseAction::run($referee);

    $referee->refresh();
    $employment->refresh();
    $suspension->refresh();

    // All changes should be atomic - employment ended and suspension ended
    expect($referee->isEmployed())->toBeFalse();
    expect($referee->isSuspended())->toBeFalse();
    expect($employment->ended_at)->not->toBeNull();
    expect($suspension->ended_at)->not->toBeNull();
});

test('it preserves employment history', function () {
    $referee = Referee::factory()->employed()->create();
    $employment = $referee->currentEmployment;
    $originalStartedAt = $employment->started_at;

    ReleaseAction::run($referee);

    $employment->refresh();

    // Employment record should be preserved with ended_at set
    $this->assertDatabaseHas('referees_employments', [
        'id' => $employment->id,
        'referee_id' => $referee->id,
        'started_at' => $originalStartedAt->toDateTimeString(),
        'ended_at' => now()->toDateTimeString(),
    ]);
});
