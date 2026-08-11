<?php

declare(strict_types=1);

use App\Actions\Referees\UnretireAction;
use App\Exceptions\Roster\Individuals\CannotBeUnretiredException;
use App\Models\Referees\Referee;
use App\Models\Referees\RefereeEmployment;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it unretires a retired referee', function () {
    $referee = Referee::factory()->retired()->create();
    $retirement = $referee->currentRetirement()->firstOrFail();

    expect($referee->isRetired())->toBeTrue();
    expect($referee->isEmployed())->toBeFalse();
    expect($retirement->ended_at)->toBeNull();

    resolve(UnretireAction::class)->handle($referee);

    $referee->refresh();
    $retirement->refresh();

    expect($referee->isRetired())->toBeFalse();
    expect($referee->isEmployed())->toBeTrue();
    expect($retirement->ended_at)->not->toBeNull();

    $this->assertDatabaseHas('referees_employments', [
        'referee_id' => $referee->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it unretires referee with specific unretirement date', function () {
    $referee = Referee::factory()->retired()->create();
    $retirement = $referee->currentRetirement()->firstOrFail();
    $unretiredDate = now()->subDays(3);

    resolve(UnretireAction::class)->handle($referee, $unretiredDate);

    $referee->refresh();
    $retirement->refresh();

    expect($referee->isRetired())->toBeFalse();
    expect($referee->isEmployed())->toBeTrue();
    expect(requiredDate($retirement->ended_at)->toDateTimeString())->toBe($unretiredDate->toDateTimeString());

    $this->assertDatabaseHas('referees_retirements', [
        'id' => $retirement->id,
        'ended_at' => $unretiredDate->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('referees_employments', [
        'referee_id' => $referee->id,
        'started_at' => $unretiredDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it persists the unretirement lifecycle', function () {
    $referee = Referee::factory()->retired()->create();

    expect($referee->isRetired())->toBeTrue();
    expect($referee->isEmployed())->toBeFalse();

    resolve(UnretireAction::class)->handle($referee);

    $referee->refresh();

    expect($referee->isRetired())->toBeFalse();
    expect($referee->isEmployed())->toBeTrue();
});

test('it handles DateHelper date resolution', function () {
    $referee = Referee::factory()->retired()->create();
    $unretiredDate = now()->subDays(5);

    resolve(UnretireAction::class)->handle($referee, $unretiredDate);

    $referee->refresh();

    // DateHelper should have processed the unretirement date
    $this->assertDatabaseHas('referees_retirements', [
        'referee_id' => $referee->id,
        'ended_at' => $unretiredDate->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('referees_employments', [
        'referee_id' => $referee->id,
        'started_at' => $unretiredDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it validates referee can be unretired', function () {
    $referee = Referee::factory()->retired()->create();

    // Should succeed without throwing validation exception
    resolve(UnretireAction::class)->handle($referee);

    $referee->refresh();
    expect($referee->isRetired())->toBeFalse();
    expect($referee->isEmployed())->toBeTrue();
});

test('it throws exception when referee cannot be unretired', function () {
    $referee = Referee::factory()->employed()->create(); // Not retired

    expect($referee->isRetired())->toBeFalse();

    expect(fn () => resolve(UnretireAction::class)->handle($referee))
        ->toThrow(CannotBeUnretiredException::class);
});

test('it preserves retirement history', function () {
    $referee = Referee::factory()->retired()->create();
    $retirement = $referee->currentRetirement()->firstOrFail();
    $originalStartedAt = $retirement->started_at;

    resolve(UnretireAction::class)->handle($referee);

    $retirement->refresh();

    // Retirement record should be preserved with ended_at set
    $this->assertDatabaseHas('referees_retirements', [
        'id' => $retirement->id,
        'referee_id' => $referee->id,
        'started_at' => $originalStartedAt->toDateTimeString(),
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it restores referee employment after unretirement', function () {
    $referee = Referee::factory()->retired()->create();

    expect($referee->isEmployed())->toBeFalse();

    resolve(UnretireAction::class)->handle($referee);

    $referee->refresh();
    $employment = $referee->currentEmployment()->firstOrFail();

    expect($employment)->not->toBeNull();
    expect($employment->referee_id)->toBe($referee->id);
    expect(requiredDate($employment->started_at)->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($employment->ended_at)->toBeNull();
});

test('it rolls back retirement changes when employment restoration fails', function () {
    $referee = Referee::factory()->retired()->create();
    $retirement = $referee->currentRetirement()->firstOrFail();

    RefereeEmployment::creating(function (): void {
        throw new RuntimeException('Employment restoration failed.');
    });

    try {
        expect(fn () => resolve(UnretireAction::class)->handle($referee))
            ->toThrow(RuntimeException::class);
    } finally {
        RefereeEmployment::flushEventListeners();
    }

    $referee->refresh();
    $retirement->refresh();

    expect($referee->isRetired())->toBeTrue();
    expect($referee->isEmployed())->toBeFalse();
    expect($retirement->ended_at)->toBeNull();
});
