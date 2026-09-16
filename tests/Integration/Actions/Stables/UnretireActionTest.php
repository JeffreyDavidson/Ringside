<?php

declare(strict_types=1);

use App\Actions\Stables\UnretireAction;
use App\Enums\Stables\StableStatus;
use App\Exceptions\Roster\Stables\CannotBeUnretiredException;
use App\Models\Roster\Stables\Stable;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function (): void {
    testTime()->freeze();
});

test('it unretires a retired stable and establishes it by default', function (): void {
    $stable = Stable::factory()->retired()->create();
    $unretiredAt = now()->subWeek();

    resolve(UnretireAction::class)->handle($stable, $unretiredAt);

    $stable->refresh();

    expect($stable->status)->toBe(StableStatus::Active)
        ->and($stable->currentRetirement()->exists())->toBeFalse()
        ->and($stable->currentActivityPeriod()->exists())->toBeTrue();

    $retirement = $stable->retirements()->latest('id')->firstOrFail();
    $activityPeriod = $stable->activityPeriods()->latest('id')->firstOrFail();

    expect(requiredDate($retirement->ended_at)->toDateTimeString())->toBe($unretiredAt->toDateTimeString())
        ->and(requiredDate($activityPeriod->started_at)->toDateTimeString())->toBe($unretiredAt->toDateTimeString());
});

test('it can unretire a stable without immediately establishing it', function (): void {
    $stable = Stable::factory()->retired()->create();

    resolve(UnretireAction::class)->handle($stable, establishImmediately: false);

    $stable->refresh();

    expect($stable->status)->toBe(StableStatus::Inactive)
        ->and($stable->currentRetirement()->exists())->toBeFalse()
        ->and($stable->currentActivityPeriod()->exists())->toBeFalse();
});

test('it rejects unretiring a stable that is not retired', function (): void {
    $stable = Stable::factory()->active()->create();

    expect(fn () => resolve(UnretireAction::class)->handle($stable))
        ->toThrow(CannotBeUnretiredException::class);
});
