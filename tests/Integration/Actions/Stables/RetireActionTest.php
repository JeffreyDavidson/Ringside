<?php

declare(strict_types=1);

use App\Actions\Stables\RetireAction;
use App\Exceptions\Roster\Stables\CannotBeRetiredException;
use App\Lifecycle\Roster\Stables\StableRetirementEligibility;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableTagTeam;
use App\Models\Roster\Stables\StableWrestler;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;

test('it retires an active stable at the current datetime by default', function () {
    $stable = Stable::factory()->active()->create();

    // Verify stable is active before retirement
    expect($stable->currentActivityPeriod()->exists())->toBeTrue();
    expect($stable->currentRetirement()->exists())->toBeFalse();

    // Call the action
    resolve(RetireAction::class)->handle($stable);

    // Verify stable is retired after action
    $stable->refresh();
    expect($stable->currentRetirement()->exists())->toBeTrue()
        ->and($stable->currentActivityPeriod()->exists())->toBeFalse();
});

test('it retires an active stable at a specific datetime', function () {
    $stable = Stable::factory()->active()->create();
    $datetime = now();

    // Verify stable is active before retirement
    expect($stable->currentActivityPeriod()->exists())->toBeTrue();
    expect($stable->currentRetirement()->exists())->toBeFalse();

    // Call the action
    resolve(RetireAction::class)->handle($stable, $datetime);

    // Verify stable is retired after action
    $stable->refresh();
    expect($stable->currentRetirement()->exists())->toBeTrue()
        ->and($stable->currentActivityPeriod()->exists())->toBeFalse();
});

test('it records a future retirement date while ending current operations now', function () {
    $stable = Stable::factory()->active()->create();
    $datetime = now()->addMonth();

    resolve(RetireAction::class)->handle($stable, $datetime);

    $stable->refresh();

    expect($stable->currentRetirement()->exists())->toBeTrue()
        ->and(requiredDate($stable->currentRetirement()->firstOrFail()->started_at)->toDateTimeString())->toBe($datetime->toDateTimeString())
        ->and(requiredDate($stable->activityPeriods()->latest('id')->firstOrFail()->ended_at)->toDateTimeString())->toBe(now()->toDateTimeString());

    foreach ($stable->previousWrestlers as $wrestler) {
        $membership = StableWrestler::query()
            ->whereBelongsTo($stable)
            ->whereBelongsTo($wrestler)
            ->latest('id')
            ->firstOrFail();

        expect(requiredDate($membership->left_at)->toDateTimeString())
            ->toBe(now()->toDateTimeString());
    }

    foreach ($stable->previousTagTeams as $tagTeam) {
        $membership = StableTagTeam::query()
            ->whereBelongsTo($stable)
            ->whereBelongsTo($tagTeam, 'tagTeam')
            ->latest('id')
            ->firstOrFail();

        expect(requiredDate($membership->left_at)->toDateTimeString())
            ->toBe(now()->toDateTimeString());
    }
});

test('it retires an inactive stable at the current datetime by default', function () {
    $stable = Stable::factory()->inactive()->create();

    // Verify stable is inactive before retirement
    expect($stable->currentActivityPeriod()->exists())->toBeFalse();
    expect($stable->currentRetirement()->exists())->toBeFalse();

    // Call the action
    resolve(RetireAction::class)->handle($stable);

    // Verify stable is retired after action
    $stable->refresh();
    expect($stable->currentRetirement()->exists())->toBeTrue()
        ->and($stable->currentActivityPeriod()->exists())->toBeFalse();
});

test('it retires an inactive stable at a specific datetime', function () {
    $stable = Stable::factory()->inactive()->create();
    $datetime = now();

    // Verify stable is inactive before retirement
    expect($stable->currentActivityPeriod()->exists())->toBeFalse();
    expect($stable->currentRetirement()->exists())->toBeFalse();

    // Call the action
    resolve(RetireAction::class)->handle($stable, $datetime);

    // Verify stable is retired after action
    $stable->refresh();
    expect($stable->currentRetirement()->exists())->toBeTrue()
        ->and($stable->currentActivityPeriod()->exists())->toBeFalse();
});

test('it ends only the stable and leaves its members employed and unretired', function () {
    // Arrange
    $this->travelTo(now()->startOfSecond());
    $retiredAt = now();

    $stable = Stable::factory()
        ->active()
        ->create();
    $wrestlers = $stable->currentWrestlers()->get();
    $tagTeams = $stable->currentTagTeams()->get();
    $tagTeamWrestlers = $tagTeams->flatMap(fn (TagTeam $tagTeam) => $tagTeam->currentWrestlers()->get());

    // Act
    resolve(RetireAction::class)->handle($stable, $retiredAt);

    // Assert
    $memberships = StableWrestler::query()->whereBelongsTo($stable)->get()
        ->concat(StableTagTeam::query()->whereBelongsTo($stable)->get());

    expect($stable->currentRetirement()->exists())->toBeTrue()
        ->and($stable->currentWrestlers()->count())->toBe(0)
        ->and($stable->currentTagTeams()->count())->toBe(0)
        ->and($memberships)->toHaveCount(3)
        ->and($memberships->map(fn ($membership): string => requiredDate($membership->left_at)->toDateTimeString())->unique()->all())
        ->toBe([$retiredAt->toDateTimeString()])
        ->and($wrestlers->concat($tagTeamWrestlers)->every(fn (Wrestler $wrestler): bool => $wrestler->currentEmployment()->exists() && ! $wrestler->retirements()->exists()))->toBeTrue()
        ->and($tagTeams->every(fn (TagTeam $tagTeam): bool => $tagTeam->currentEmployment()->exists() && ! $tagTeam->retirements()->exists()))->toBeTrue()
        ->and(TagTeamWrestler::query()->whereIn('tag_team_id', $tagTeams->modelKeys())->whereNotNull('left_at')->exists())->toBeFalse();
});

test('it throws exception trying to retire a non retirable stable', function ($factoryState) {
    $stable = Stable::factory()->{$factoryState}()->create();

    expect(resolve(StableRetirementEligibility::class)->canRetire($stable))->toBeFalse()
        ->and(fn () => resolve(RetireAction::class)->handle($stable))
        ->toThrow(CannotBeRetiredException::class);
})->with([
    'unactivated',
    'withFutureActivation',
    'retired',
]);

test('it rejects a deleted stable', function () {
    $stable = Stable::factory()->inactive()->create();
    $stable->delete();

    expect(resolve(StableRetirementEligibility::class)->canRetire($stable))->toBeFalse()
        ->and(fn () => resolve(RetireAction::class)->handle($stable))
        ->toThrow(CannotBeRetiredException::class);
});
