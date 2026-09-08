<?php

declare(strict_types=1);

use App\Enums\Stables\StableStatus;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

test('established stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();

    $activeStables = Stable::query()->established()->get();

    expect($activeStables)
        ->toHaveCount(1)
        ->and($activeStables->contains($activeStable))->toBeTrue();
});

test('future established stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();

    $futureActivatedStables = Stable::query()->withFutureEstablishment()->get();

    expect($futureActivatedStables)
        ->toHaveCount(1)
        ->and($futureActivatedStables->contains($futureActivatedStable))->toBeTrue();
});

test('disbanded stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();
    $pendingReestablishmentStable = Stable::factory()
        ->has(
            ActivityPeriod::factory()
                ->started(now()->subDays(4))
                ->ended(now()->subDays(2)),
            'activityPeriods',
        )
        ->has(ActivityPeriod::factory()->started(now()->addDays(2)), 'activityPeriods')
        ->create();

    $inactiveStables = Stable::query()->disbanded()->get();

    expect($inactiveStables)
        ->toHaveCount(1)
        ->and($inactiveStables->contains($inactiveStable))->toBeTrue();
});

test('unestablished stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();

    $unactivatedStables = Stable::query()->unestablished()->get();

    expect($unactivatedStables)
        ->toHaveCount(1)
        ->and($unactivatedStables->contains($unactivatedStable))->toBeTrue();
});

test('projected activity status does not query per stable', function () {
    // Arrange
    $active = Stable::factory()->active()->create();
    $retired = Stable::factory()->retired()->create();
    $pending = Stable::factory()->withFutureActivation()->create();
    $inactive = Stable::factory()->inactive()->create();
    $initial = Stable::factory()->unactivated()->create();
    $this->expectsDatabaseQueryCount(1);

    // Act
    $query = Stable::query();
    $query->withActivityStatusState();
    $query->orderBy('id');
    $stables = $query->get();
    $statuses = $stables->mapWithKeys(fn (Stable $stable): array => [$stable->id => $stable->status]);

    // Assert
    expect($statuses->all())->toBe([
        $active->id => StableStatus::Active,
        $retired->id => StableStatus::Retired,
        $pending->id => StableStatus::PendingEstablishment,
        $inactive->id => StableStatus::Inactive,
        $initial->id => StableStatus::Unformed,
    ]);
});

test('previous stables can be retrieved for a wrestler', function () {
    $wrestler = Wrestler::factory()->create();
    $previousStable = Stable::factory()->create();
    $currentStable = Stable::factory()->create();
    $previousStable->wrestlers()->attach($wrestler, [
        'joined_at' => now()->subMonths(2),
        'left_at' => now()->subMonth(),
    ]);
    $currentStable->wrestlers()->attach($wrestler, [
        'joined_at' => now(),
    ]);

    $stables = Stable::query()
        ->previousForWrestlerId($wrestler->id)
        ->get();

    expect($stables)->toHaveCount(1)
        ->and($stables->firstOrFail()->is($previousStable))->toBeTrue()
        ->and($stables->firstOrFail()->getAttribute('joined_at'))->not->toBeNull()
        ->and($stables->firstOrFail()->getAttribute('left_at'))->not->toBeNull();
});

test('previous stables can be retrieved for a tag team', function () {
    $tagTeam = TagTeam::factory()->create();
    $previousStable = Stable::factory()->create();
    $currentStable = Stable::factory()->create();
    $previousStable->tagTeams()->attach($tagTeam, [
        'joined_at' => now()->subMonths(2),
        'left_at' => now()->subMonth(),
    ]);
    $currentStable->tagTeams()->attach($tagTeam, [
        'joined_at' => now(),
    ]);

    $stables = Stable::query()
        ->previousForTagTeamId($tagTeam->id)
        ->get();

    expect($stables)->toHaveCount(1)
        ->and($stables->firstOrFail()->is($previousStable))->toBeTrue()
        ->and($stables->firstOrFail()->getAttribute('joined_at'))->not->toBeNull()
        ->and($stables->firstOrFail()->getAttribute('left_at'))->not->toBeNull();
});
