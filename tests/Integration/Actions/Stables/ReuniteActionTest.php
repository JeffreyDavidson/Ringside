<?php

declare(strict_types=1);

use App\Actions\Stables\ReuniteAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Stables\CannotBeReunitedException;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Wrestlers\Wrestler;

test('it reunites a disbanded stable and preserves its history', function (): void {
    $stable = Stable::factory()->disbanded()->create();
    $reuniteDate = now()->subDay();

    resolve(ReuniteAction::class)->handle($stable, $reuniteDate);

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

    expect(fn () => resolve(ReuniteAction::class)->handle($stable))
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

test('it rejects reuniting a stable when a key former member is unavailable', function (Closure $makeMemberUnavailable): void {
    $stable = Stable::factory()->disbanded()->create();
    $spareWrestler = Wrestler::factory()->employed()->create();
    $stable->wrestlers()->attach($spareWrestler, [
        'joined_at' => now()->subDays(2),
        'left_at' => now()->subDay(),
    ]);
    $unavailableMember = $makeMemberUnavailable($stable);

    expect(fn () => resolve(ReuniteAction::class)->handle($stable))
        ->toThrow(CannotBeReunitedException::class, "key former members unavailable: {$unavailableMember->name}")
        ->and($stable->currentActivityPeriod()->exists())->toBeFalse()
        ->and($stable->lifecycleTransitions()->exists())->toBeFalse();
})->with('unavailable stable former members');

test('it names every unavailable key former member when reunion is rejected', function (): void {
    $stable = Stable::factory()->disbanded()->create();
    $spareWrestler = Wrestler::factory()->employed()->create();
    $stable->wrestlers()->attach($spareWrestler, [
        'joined_at' => now()->subDays(2),
        'left_at' => now()->subDay(),
    ]);
    $suspendedWrestler = $stable->previousWrestlers()->where('wrestlers.id', '!=', $spareWrestler->id)->firstOrFail();
    $suspendedWrestler->suspensions()->create(['started_at' => now()->subHour()]);
    $retiredTagTeam = $stable->previousTagTeams()->get()->firstOrFail();
    $retiredTagTeam->retirements()->create(['started_at' => now()->subHour()]);
    $extraWrestler = Wrestler::factory()->employed()->create();
    $stable->wrestlers()->attach($extraWrestler, [
        'joined_at' => now()->subDays(2),
        'left_at' => now()->subDay(),
    ]);
    $expectedNames = "{$suspendedWrestler->name}, {$retiredTagTeam->name}";

    expect(fn () => resolve(ReuniteAction::class)->handle($stable))
        ->toThrow(CannotBeReunitedException::class, "key former members unavailable: {$expectedNames}");
});
