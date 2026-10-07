<?php

declare(strict_types=1);

use App\Actions\Stables\UnretireAction;
use App\Enums\Stables\StableStatus;
use App\Exceptions\Roster\Stables\CannotBeUnretiredException;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Wrestlers\Wrestler;

test('it unretires a retired stable and establishes it by default', function (): void {
    $stable = Stable::factory()->retired()->create();
    $unretiredAt = now()->startOfDay();

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

test('it rejects unretiring a stable with too few available former members', function (): void {
    $stable = Stable::factory()->retired()->create();
    $stable->previousWrestlers()->firstOrFail()->suspensions()->create(['started_at' => now()->subHour()]);

    expect(fn () => resolve(UnretireAction::class)->handle($stable))
        ->toThrow(CannotBeUnretiredException::class, 'only 2 former members available, but 3 required')
        ->and($stable->currentRetirement()->exists())->toBeTrue()
        ->and($stable->currentActivityPeriod()->exists())->toBeFalse();
});

test('it rejects unretiring a stable when a key former member is unavailable', function (Closure $makeMemberUnavailable): void {
    $stable = Stable::factory()->retired()->create();
    $spareWrestler = Wrestler::factory()->employed()->create();
    $stable->wrestlers()->attach($spareWrestler, [
        'joined_at' => now()->subDays(3),
        'left_at' => now()->subDay(),
    ]);
    $unavailableMember = $makeMemberUnavailable($stable);

    expect(fn () => resolve(UnretireAction::class)->handle($stable))
        ->toThrow(CannotBeUnretiredException::class, "key former members unavailable: {$unavailableMember->name}")
        ->and($stable->currentRetirement()->exists())->toBeTrue()
        ->and($stable->currentActivityPeriod()->exists())->toBeFalse();
})->with('unavailable stable former members');

test('it unretires a stable without checking former members when they are not required', function (): void {
    $stable = Stable::factory()->retired()->create();
    $stable->previousWrestlers()->firstOrFail()->suspensions()->create(['started_at' => now()->subHour()]);

    resolve(UnretireAction::class)->handle($stable, requireFormerMembers: false);

    expect($stable->currentRetirement()->exists())->toBeFalse()
        ->and($stable->currentActivityPeriod()->exists())->toBeTrue();
});
