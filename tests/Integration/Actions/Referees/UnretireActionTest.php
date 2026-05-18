<?php

declare(strict_types=1);

use App\Actions\Referees\UnretireAction;
use App\Exceptions\Roster\CannotBeUnretiredException;
use App\Models\Referees\Referee;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it unretires a retired referee', function () {
    $referee = Referee::factory()->retired()->create();
    $retirement = $referee->currentRetirement;

    expect($referee->isRetired())->toBeTrue();
    expect($referee->isEmployed())->toBeFalse();
    expect($retirement->ended_at)->toBeNull();

    UnretireAction::run($referee);

    $referee->refresh();
    $retirement->refresh();

    expect($referee->isRetired())->toBeFalse();
    expect($referee->isEmployed())->toBeFalse();
    expect($retirement->ended_at)->not->toBeNull();

    $this->assertDatabaseMissing('referees_employments', [
        'referee_id' => $referee->id,
        'ended_at' => null,
    ]);
});

test('it unretires referee with specific unretirement date', function () {
    $referee = Referee::factory()->retired()->create();
    $retirement = $referee->currentRetirement;
    $unretiredDate = now()->subDays(3);

    UnretireAction::run($referee, $unretiredDate);

    $referee->refresh();
    $retirement->refresh();

    expect($referee->isRetired())->toBeFalse();
    expect($referee->isEmployed())->toBeFalse();
    expect($retirement->ended_at->toDateTimeString())->toBe($unretiredDate->toDateTimeString());

    $this->assertDatabaseHas('referees_retirements', [
        'id' => $retirement->id,
        'ended_at' => $unretiredDate->toDateTimeString(),
    ]);

    $this->assertDatabaseMissing('referees_employments', [
        'referee_id' => $referee->id,
        'ended_at' => null,
    ]);
});

test('it uses StatusTransitionPipeline for consistent unretirement', function () {
    $referee = Referee::factory()->retired()->create();

    expect($referee->isRetired())->toBeTrue();
    expect($referee->isEmployed())->toBeFalse();

    UnretireAction::run($referee);

    $referee->refresh();

    expect($referee->isRetired())->toBeFalse();
    expect($referee->isEmployed())->toBeFalse();
});

test('it handles DateHelper date resolution', function () {
    $referee = Referee::factory()->retired()->create();
    $unretiredDate = now()->subDays(5);

    UnretireAction::run($referee, $unretiredDate);

    $referee->refresh();

    // DateHelper should have processed the unretirement date
    $this->assertDatabaseHas('referees_retirements', [
        'referee_id' => $referee->id,
        'ended_at' => $unretiredDate->toDateTimeString(),
    ]);

    $this->assertDatabaseMissing('referees_employments', [
        'referee_id' => $referee->id,
        'ended_at' => null,
    ]);
});

test('it validates referee can be unretired', function () {
    $referee = Referee::factory()->retired()->create();

    // Should succeed without throwing validation exception
    UnretireAction::run($referee);

    $referee->refresh();
    expect($referee->isRetired())->toBeFalse();
    expect($referee->isEmployed())->toBeFalse();
});

test('it throws exception when referee cannot be unretired', function () {
    $referee = Referee::factory()->employed()->create(); // Not retired

    expect($referee->isRetired())->toBeFalse();

    expect(fn () => UnretireAction::run($referee))
        ->toThrow(CannotBeUnretiredException::class);
});

test('it preserves retirement history', function () {
    $referee = Referee::factory()->retired()->create();
    $retirement = $referee->currentRetirement;
    $originalStartedAt = $retirement->started_at;

    UnretireAction::run($referee);

    $retirement->refresh();

    // Retirement record should be preserved with ended_at set
    $this->assertDatabaseHas('referees_retirements', [
        'id' => $retirement->id,
        'referee_id' => $referee->id,
        'started_at' => $originalStartedAt->toDateTimeString(),
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it leaves referee unemployed after unretirement', function () {
    $referee = Referee::factory()->retired()->create();

    expect($referee->isEmployed())->toBeFalse();

    UnretireAction::run($referee);

    $referee->refresh();
    expect($referee->currentEmployment)->toBeNull();
});
