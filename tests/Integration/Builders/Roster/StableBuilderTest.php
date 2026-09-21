<?php

declare(strict_types=1);

use App\Enums\Stables\StableStatus;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

test('established stables can be retrieved', function () {
    // Arrange
    $activeStable = Stable::factory()->active()->create();
    Stable::factory()->withFutureActivation()->create();
    Stable::factory()->inactive()->create();
    Stable::factory()->retired()->create();
    Stable::factory()->unactivated()->create();
    Stable::factory()->active()->trashed()->create();

    // Act
    $query = Stable::query();
    $query->established();
    $activeStables = $query->get();

    // Assert
    expect($activeStables->modelKeys())->toBe([$activeStable->id]);
});

test('future established stables can be retrieved', function () {
    // Arrange
    Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    Stable::factory()->inactive()->create();
    Stable::factory()->retired()->create();
    Stable::factory()->unactivated()->create();
    Stable::factory()->withFutureActivation()->trashed()->create();

    // Act
    $query = Stable::query();
    $query->withFutureEstablishment();
    $futureActivatedStables = $query->get();

    // Assert
    expect($futureActivatedStables->modelKeys())->toBe([$futureActivatedStable->id]);
});

test('disbanded stables can be retrieved', function () {
    // Arrange
    Stable::factory()->active()->create();
    Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    Stable::factory()->retired()->create();
    Stable::factory()->unactivated()->create();
    Stable::factory()->inactive()->trashed()->create();
    $pendingReestablishmentStable = Stable::factory()
        ->has(
            ActivityPeriod::factory()
                ->started(now()->subDays(4))
                ->ended(now()->subDays(2)),
            'activityPeriods',
        )
        ->has(ActivityPeriod::factory()->started(now()->addDays(2)), 'activityPeriods')
        ->create();

    // Act
    $query = Stable::query();
    $query->disbanded();
    $inactiveStables = $query->get();

    // Assert
    expect($inactiveStables->modelKeys())->toBe([$inactiveStable->id]);
});

test('unestablished stables can be retrieved', function () {
    // Arrange
    Stable::factory()->active()->create();
    Stable::factory()->withFutureActivation()->create();
    Stable::factory()->inactive()->create();
    Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();
    Stable::factory()->unactivated()->trashed()->create();

    // Act
    $query = Stable::query();
    $query->unestablished();
    $unactivatedStables = $query->get();

    // Assert
    expect($unactivatedStables->modelKeys())->toBe([$unactivatedStable->id]);
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
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $otherMember = Wrestler::factory()->create();
    $olderStable = Stable::factory()->create();
    $previousStable = Stable::factory()->create();
    $currentStable = Stable::factory()->create();
    $deletedStable = Stable::factory()->trashed()->create();
    $unrelatedStable = Stable::factory()->create();
    $olderStable->wrestlers()->attach($wrestler, [
        'joined_at' => '2025-01-01 12:00:00',
        'left_at' => '2025-02-01 12:00:00',
    ]);
    $previousStable->wrestlers()->attach($wrestler, [
        'joined_at' => '2025-03-01 12:00:00',
        'left_at' => '2025-04-01 12:00:00',
    ]);
    $deletedStable->wrestlers()->attach($wrestler, [
        'joined_at' => '2025-05-01 12:00:00',
        'left_at' => '2025-06-01 12:00:00',
    ]);
    $unrelatedStable->wrestlers()->attach($otherMember, [
        'joined_at' => '2025-07-01 12:00:00',
        'left_at' => '2025-08-01 12:00:00',
    ]);
    $currentStable->wrestlers()->attach($wrestler, [
        'joined_at' => now(),
    ]);

    // Act
    $query = Stable::query();
    $query->previousForWrestlerId($wrestler->id);
    $stables = $query->get();

    // Assert
    expect($stables->modelKeys())->toBe([$previousStable->id, $olderStable->id])
        ->and($stables->pluck('joined_at')->all())->toBe(['2025-03-01 12:00:00', '2025-01-01 12:00:00'])
        ->and($stables->pluck('left_at')->all())->toBe(['2025-04-01 12:00:00', '2025-02-01 12:00:00']);
});

test('previous stables can be retrieved for a tag team', function () {
    // Arrange
    $tagTeam = TagTeam::factory()->create();
    $otherMember = TagTeam::factory()->create();
    $olderStable = Stable::factory()->create();
    $previousStable = Stable::factory()->create();
    $currentStable = Stable::factory()->create();
    $deletedStable = Stable::factory()->trashed()->create();
    $unrelatedStable = Stable::factory()->create();
    $olderStable->tagTeams()->attach($tagTeam, [
        'joined_at' => '2025-01-01 12:00:00',
        'left_at' => '2025-02-01 12:00:00',
    ]);
    $previousStable->tagTeams()->attach($tagTeam, [
        'joined_at' => '2025-03-01 12:00:00',
        'left_at' => '2025-04-01 12:00:00',
    ]);
    $deletedStable->tagTeams()->attach($tagTeam, [
        'joined_at' => '2025-05-01 12:00:00',
        'left_at' => '2025-06-01 12:00:00',
    ]);
    $unrelatedStable->tagTeams()->attach($otherMember, [
        'joined_at' => '2025-07-01 12:00:00',
        'left_at' => '2025-08-01 12:00:00',
    ]);
    $currentStable->tagTeams()->attach($tagTeam, [
        'joined_at' => now(),
    ]);

    // Act
    $query = Stable::query();
    $query->previousForTagTeamId($tagTeam->id);
    $stables = $query->get();

    // Assert
    expect($stables->modelKeys())->toBe([$previousStable->id, $olderStable->id])
        ->and($stables->pluck('joined_at')->all())->toBe(['2025-03-01 12:00:00', '2025-01-01 12:00:00'])
        ->and($stables->pluck('left_at')->all())->toBe(['2025-04-01 12:00:00', '2025-02-01 12:00:00']);
});
