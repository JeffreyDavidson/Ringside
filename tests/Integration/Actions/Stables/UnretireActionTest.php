<?php

declare(strict_types=1);

use App\Actions\Stables\UnretireAction;
use App\Enums\Stables\StableStatus;
use App\Exceptions\Roster\Stables\CannotBeUnretiredException;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableWrestler;
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

test('it rejects unretiring a stable that is not retired', function (): void {
    $stable = Stable::factory()->active()->create();

    expect(fn () => resolve(UnretireAction::class)->handle($stable))
        ->toThrow(CannotBeUnretiredException::class);
});

test('it unretires a stable of one wrestler and one tag team using the weighted headcount', function (): void {
    // Arrange
    $stable = Stable::factory()->retired()->create();
    $stable->wrestlers()->detach($stable->previousWrestlers()->firstOrFail());

    // Act
    resolve(UnretireAction::class)->handle($stable);

    // Assert
    expect($stable->refresh()->status)->toBe(StableStatus::Active);
});

test('it rejects unretiring a stable with too few available former members', function (): void {
    // Arrange
    $stable = Stable::factory()->retired()->create();
    $stable->tagTeams()->detach();

    // Act
    $unretire = fn () => resolve(UnretireAction::class)->handle($stable);

    // Assert
    expect($unretire)->toThrow(CannotBeUnretiredException::class, 'only 2 former members available, but 3 required')
        ->and($stable->currentRetirement()->exists())->toBeTrue()
        ->and($stable->currentActivityPeriod()->exists())->toBeFalse();
});

test('it reports the weighted headcount when a tag team alone is too few', function (): void {
    // Arrange
    $stable = Stable::factory()->retired()->create();
    $stable->previousWrestlers()->get()->each(
        fn (Wrestler $wrestler) => $wrestler->suspensions()->create(['started_at' => now()->subHour()])
    );

    // Act
    $unretire = fn () => resolve(UnretireAction::class)->handle($stable);

    // Assert
    expect($unretire)->toThrow(CannotBeUnretiredException::class, 'only 2 former members available, but 3 required');
});

test('it brings back only the members it had when it retired', function (): void {
    // Arrange
    $this->travelTo(now()->startOfSecond());
    $stable = Stable::factory()
        ->retired()
        ->create();
    $earlierMember = Wrestler::factory()
        ->employed()
        ->create();
    $stable->wrestlers()->attach($earlierMember, [
        'joined_at' => now()->subYears(3),
        'left_at' => now()->subYears(2),
    ]);
    $unretiredAt = now();

    // Act
    resolve(UnretireAction::class)->handle($stable, $unretiredAt);

    // Assert
    expect($stable->currentWrestlers()->count())->toBe(2)
        ->and($stable->currentTagTeams()->count())->toBe(1)
        ->and($stable->currentWrestlers()->whereKey($earlierMember->getKey())->exists())->toBeFalse()
        ->and(StableWrestler::query()->whereBelongsTo($stable)->whereNull('left_at')->get()->every(fn (StableWrestler $membership): bool => requiredDate($membership->joined_at)->equalTo($unretiredAt)))->toBeTrue();
});

test('it is not blocked by a member who left years before the retirement', function (): void {
    // Arrange
    $stable = Stable::factory()
        ->retired()
        ->create();
    $earlierMember = Wrestler::factory()
        ->injured()
        ->create();
    $stable->wrestlers()->attach($earlierMember, [
        'joined_at' => now()->subYears(3),
        'left_at' => now()->subYears(2),
    ]);

    // Act
    resolve(UnretireAction::class)->handle($stable);

    // Assert
    expect($stable->refresh()->status)->toBe(StableStatus::Active);
});

test('it skips an unavailable member and returns the available ones', function (Closure $makeMemberUnavailable): void {
    // Arrange
    $stable = Stable::factory()
        ->retired()
        ->create();
    $spareWrestler = Wrestler::factory()
        ->employed()
        ->create();
    $stable->wrestlers()->attach($spareWrestler, [
        'joined_at' => now()->subDays(3),
        'left_at' => $stable->currentRetirement()->firstOrFail()->started_at,
    ]);
    $unavailableMember = $makeMemberUnavailable($stable);

    // Act
    resolve(UnretireAction::class)->handle($stable);

    // Assert
    $currentMembers = $stable->currentWrestlers()->get()->concat($stable->currentTagTeams()->get());

    expect($stable->refresh()->status)->toBe(StableStatus::Active)
        ->and($currentMembers->contains(fn ($member): bool => $member->is($unavailableMember)))->toBeFalse()
        ->and($currentMembers)->not->toBeEmpty();
})->with('unavailable stable former members');
