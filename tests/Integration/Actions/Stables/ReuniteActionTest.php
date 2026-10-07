<?php

declare(strict_types=1);

use App\Actions\Stables\ReuniteAction;
use App\Data\Stables\StableMembershipData;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Stables\CannotBeReunitedException;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Wrestlers\Wrestler;

test('it reunites a disbanded stable and preserves its history', function (): void {
    $stable = Stable::factory()->disbanded()->create();
    $reuniteDate = now()->subDay();

    resolve(ReuniteAction::class)->handle($stable, formerMembersOf($stable), $reuniteDate);

    $stable->refresh();
    $transition = $stable->lifecycleTransitions()->sole();
    $activityPeriods = $stable->activityPeriods()->orderBy('started_at')->get();
    $currentPeriod = $stable->currentActivityPeriod()->firstOrFail();

    expect($activityPeriods)->toHaveCount(2)
        ->and($currentPeriod->started_at->toDateTimeString())->toBe($reuniteDate->toDateTimeString())
        ->and($currentPeriod->ended_at)->toBeNull()
        ->and($transition->transition)->toBe(LifecycleTransitionType::Reunited)
        ->and($transition->effective_at->toDateTimeString())->toBe($reuniteDate->toDateTimeString());
});

test('it rejects reuniting a stable that was never active or is retired', function (Closure $makeStable, string $message): void {
    $stable = $makeStable();
    $activityPeriodCount = $stable->activityPeriods()->count();

    expect(fn () => resolve(ReuniteAction::class)->handle($stable, formerMembersOf($stable)))
        ->toThrow(CannotBeReunitedException::class, $message)
        ->and($stable->activityPeriods()->count())->toBe($activityPeriodCount)
        ->and($stable->currentActivityPeriod()->exists())->toBeFalse()
        ->and($stable->lifecycleTransitions()->exists())->toBeFalse();
})->with([
    'never active' => [
        fn (): Stable => Stable::factory()->create(),
        'has never been active and cannot be reunited',
    ],
    'retired' => [
        fn (): Stable => Stable::factory()->retired()->create(),
        'is retired and cannot be reunited',
    ],
]);

test('it reunites without a former member who has become unavailable', function (Closure $makeMemberUnavailable): void {
    // Arrange
    $stable = Stable::factory()->disbanded()->create();
    $spareWrestler = Wrestler::factory()->employed()->create();
    $stable->wrestlers()->attach($spareWrestler, [
        'joined_at' => now()->subDays(2),
        'left_at' => now()->subDay(),
    ]);
    $unavailableMember = $makeMemberUnavailable($stable);
    $returningMembers = formerMembersOf($stable);

    // Act
    resolve(ReuniteAction::class)->handle($stable, $returningMembers);

    // Assert
    $offeredIds = $unavailableMember instanceof Wrestler
        ? $returningMembers->wrestlers?->pluck('id')->all()
        : $returningMembers->tagTeams?->pluck('id')->all();
    expect($offeredIds ?? [])->not->toContain($unavailableMember->getKey())
        ->and($stable->currentActivityPeriod()->exists())->toBeTrue()
        ->and($unavailableMember->currentStable()->whereKey($stable->getKey())->exists())->toBeFalse();
})->with('unavailable stable former members');

test('it rejects an unavailable former member chosen to return', function (Closure $makeMemberUnavailable): void {
    // Arrange
    $stable = Stable::factory()->disbanded()->create();
    $stable->wrestlers()->attach(Wrestler::factory()->employed()->count(2)->create(), [
        'joined_at' => now()->subDays(2),
        'left_at' => now()->subDay(),
    ]);
    $unavailableMember = $makeMemberUnavailable($stable);
    $available = formerMembersOf($stable);
    $returningMembers = $unavailableMember instanceof Wrestler
        ? new StableMembershipData(($available->wrestlers ?? collect())->push($unavailableMember), $available->tagTeams)
        : new StableMembershipData($available->wrestlers, ($available->tagTeams ?? collect())->push($unavailableMember));

    // Act & Assert
    expect(fn () => resolve(ReuniteAction::class)->handle($stable, $returningMembers))
        ->toThrow(CannotBeReunitedException::class, $unavailableMember->name)
        ->and($stable->currentActivityPeriod()->exists())->toBeFalse()
        ->and($stable->lifecycleTransitions()->exists())->toBeFalse();
})->with('unavailable stable former members');

test('it rejects reuniting when the available former members count under three', function (): void {
    // Arrange
    $stable = Stable::factory()->disbanded()->create();
    $stable->previousWrestlers()->get()->each(fn (Wrestler $wrestler) => $wrestler->retirements()->create(['started_at' => now()->subHour()]));
    $stable->previousTagTeams()->get()->each(fn ($tagTeam) => $tagTeam->retirements()->create(['started_at' => now()->subHour()]));

    // Act & Assert
    expect(fn () => resolve(ReuniteAction::class)->handle($stable, formerMembersOf($stable)))
        ->toThrow(CannotBeReunitedException::class)
        ->and($stable->currentActivityPeriod()->exists())->toBeFalse();
});

test('it restores the returning members on the reunite date and keeps their history', function (): void {
    $stable = Stable::factory()->disbanded()->create();
    $reuniteDate = now()->subHour();
    $returningMembers = formerMembersOf($stable);
    $originalMemberships = $stable->wrestlers()->count() + $stable->tagTeams()->count();

    resolve(ReuniteAction::class)->handle($stable, $returningMembers, $reuniteDate);

    expect($stable->currentWrestlers()->pluck('wrestlers.id')->all())->toEqualCanonicalizing($returningMembers->wrestlers?->pluck('id')->all())
        ->and($stable->currentTagTeams()->pluck('tag_teams.id')->all())->toEqualCanonicalizing($returningMembers->tagTeams?->pluck('id')->all())
        ->and($stable->currentWrestlers()->wherePivot('joined_at', $reuniteDate)->count())->toBe($returningMembers->wrestlers?->count())
        ->and($stable->currentTagTeams()->wherePivot('joined_at', $reuniteDate)->count())->toBe($returningMembers->tagTeams?->count())
        ->and($stable->previousWrestlers()->count())->toBe($returningMembers->wrestlers?->count())
        ->and($stable->wrestlers()->count() + $stable->tagTeams()->count())->toBe($originalMemberships * 2);
});

test('it only restores the members it was given', function (): void {
    $stable = Stable::factory()->disbanded()->create();
    $leftBehind = $stable->previousWrestlers()->firstOrFail();
    $returningMembers = new StableMembershipData(
        wrestlers: $stable->previousWrestlers()->whereKeyNot($leftBehind->getKey())->get(),
        tagTeams: $stable->previousTagTeams()->get(),
    );

    resolve(ReuniteAction::class)->handle($stable, $returningMembers);

    expect($stable->currentWrestlers()->whereKey($leftBehind->getKey())->exists())->toBeFalse()
        ->and($stable->currentWrestlers()->count())->toBe(1)
        ->and($stable->currentTagTeams()->count())->toBe(1);
});

test('it rejects returning members below the minimum headcount', function (): void {
    $stable = Stable::factory()->disbanded()->create();
    $returningMembers = new StableMembershipData(wrestlers: $stable->previousWrestlers()->get());

    expect(fn () => resolve(ReuniteAction::class)->handle($stable, $returningMembers))
        ->toThrow(CannotBeReunitedException::class, 'the returning members count as 2, but at least 3 are required')
        ->and($stable->currentActivityPeriod()->exists())->toBeFalse()
        ->and($stable->currentWrestlers()->exists())->toBeFalse()
        ->and($stable->lifecycleTransitions()->exists())->toBeFalse();
});

test('it counts a returning tag team as two members towards the minimum', function (): void {
    $stable = Stable::factory()->disbanded()->create();
    $returningMembers = new StableMembershipData(
        wrestlers: $stable->previousWrestlers()->limit(1)->get(),
        tagTeams: $stable->previousTagTeams()->get(),
    );

    resolve(ReuniteAction::class)->handle($stable, $returningMembers);

    expect($stable->currentActivityPeriod()->exists())->toBeTrue();
});

test('it rejects a returning member who was never a former member without reuniting', function (): void {
    $stable = Stable::factory()->disbanded()->create();
    $outsider = Wrestler::factory()->employed()->create();
    $returningMembers = new StableMembershipData(
        wrestlers: formerMembersOf($stable)->wrestlers?->push($outsider),
        tagTeams: formerMembersOf($stable)->tagTeams,
    );

    expect(fn () => resolve(ReuniteAction::class)->handle($stable, $returningMembers))
        ->toThrow(CannotBeReunitedException::class, "not available former members: {$outsider->name}")
        ->and($stable->currentActivityPeriod()->exists())->toBeFalse()
        ->and($stable->currentWrestlers()->exists())->toBeFalse()
        ->and($stable->lifecycleTransitions()->exists())->toBeFalse();
});

test('it rejects a returning member who is no longer employed', function (): void {
    $stable = Stable::factory()->disbanded()->create();
    $spareWrestler = Wrestler::factory()->employed()->create();
    $stable->wrestlers()->attach($spareWrestler, [
        'joined_at' => now()->subDays(2),
        'left_at' => now()->subDay(),
    ]);
    $released = $stable->previousWrestlers()->whereKeyNot($spareWrestler->getKey())->firstOrFail();
    $released->currentEmployment()->update(['ended_at' => now()->subHour()]);
    $returningMembers = new StableMembershipData(
        wrestlers: $stable->previousWrestlers()->get(),
        tagTeams: $stable->previousTagTeams()->get(),
    );

    expect(fn () => resolve(ReuniteAction::class)->handle($stable, $returningMembers))
        ->toThrow(CannotBeReunitedException::class, "not available former members: {$released->name}")
        ->and($stable->currentActivityPeriod()->exists())->toBeFalse();
});
