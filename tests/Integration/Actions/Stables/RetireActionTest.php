<?php

declare(strict_types=1);

use App\Actions\Stables\RetireAction;
use App\Exceptions\Roster\Stables\CannotBeRetiredException;
use App\Lifecycle\Roster\Stables\StableRetirementEligibility;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Lifecycle\Employment;
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

test('it retires the current tag teams and current wrestlers of a stable', function () {
    $stable = Stable::factory()->active()->create();

    // Verify stable has current members before retirement
    expect($stable->currentWrestlers()->count())->toBeGreaterThan(0);
    expect($stable->currentTagTeams()->count())->toBeGreaterThan(0);

    // Get the current members before retirement
    $currentWrestlers = $stable->currentWrestlers;
    $currentTagTeams = $stable->currentTagTeams;

    // Verify they are not retired before action
    foreach ($currentWrestlers as $wrestler) {
        expect($wrestler->currentRetirement()->exists())->toBeFalse();
    }
    foreach ($currentTagTeams as $tagTeam) {
        expect($tagTeam->currentRetirement()->exists())->toBeFalse();
    }

    // Call the action
    resolve(RetireAction::class)->handle($stable);

    // Verify stable is retired
    $stable->refresh();
    expect($stable->currentRetirement()->exists())->toBeTrue();

    // Verify current members were retired
    foreach ($currentWrestlers as $wrestler) {
        $wrestler->refresh();
        expect($wrestler->currentRetirement()->exists())->toBeTrue();
    }
    foreach ($currentTagTeams as $tagTeam) {
        $tagTeam->refresh();
        expect($tagTeam->currentRetirement()->exists())->toBeTrue();
    }
});

test('it retires a tag team with its members intact when a member is also a direct stable member', function () {
    // Arrange
    $this->travelTo(now()->startOfSecond());
    $joinedAt = now()->subWeek();

    $stable = Stable::factory()
        ->withNoMembers()
        ->has(ActivityPeriod::factory()->started($joinedAt), 'activityPeriods')
        ->create();
    $wrestlerA = Wrestler::factory()
        ->has(Employment::factory()->started($joinedAt), 'employments')
        ->create();
    $wrestlerB = Wrestler::factory()
        ->has(Employment::factory()->started($joinedAt), 'employments')
        ->create();
    $tagTeam = TagTeam::factory()
        ->has(Employment::factory()->started($joinedAt), 'employments')
        ->create();
    $tagTeam->wrestlers()->attach([$wrestlerA->id, $wrestlerB->id], ['joined_at' => $joinedAt]);
    $stable->tagTeams()->attach($tagTeam, ['joined_at' => $joinedAt]);
    $stable->wrestlers()->attach($wrestlerA, ['joined_at' => $joinedAt]);

    // Act
    resolve(RetireAction::class)->handle($stable);

    // Assert
    $memberships = TagTeamWrestler::query()
        ->whereBelongsTo($tagTeam, 'tagTeam')
        ->get();

    expect($tagTeam->currentRetirement()->exists())->toBeTrue()
        ->and($memberships)->toHaveCount(2)
        ->and($memberships->whereNotNull('left_at'))->toBeEmpty()
        ->and($tagTeam->currentWrestlers()->count())->toBe(2)
        ->and($wrestlerA->retirements()->count())->toBe(1)
        ->and($wrestlerB->retirements()->count())->toBe(1);
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
