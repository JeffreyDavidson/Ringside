<?php

declare(strict_types=1);

use App\Models\Promotions\Promotion;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableTagTeam;
use App\Models\Roster\Stables\StableWrestler;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

test('joinableToStable keeps only wrestlers that are bookable and not in another stable', function () {
    // Arrange
    $joinable = Wrestler::factory()->bookable()->create();
    Wrestler::factory()->unemployed()->create();
    Wrestler::factory()->retired()->create();
    Wrestler::factory()->suspended()->create();
    Wrestler::factory()->injured()->create();
    $member = Wrestler::factory()->bookable()->create();
    StableWrestler::query()->create([
        'stable_id' => Stable::factory()->create()->id,
        'wrestler_id' => $member->id,
        'joined_at' => now()->subMonth(),
    ]);

    // Act
    $wrestlers = Wrestler::query()->joinableToStable()->pluck('id');

    // Assert
    expect($wrestlers->all())->toBe([$joinable->id]);
});

test('joinableToStable keeps only tag teams that are employed, available and not in another stable', function () {
    // Arrange
    $joinable = TagTeam::factory()->bookable()->create();
    TagTeam::factory()->unemployed()->create();
    TagTeam::factory()->retired()->create();
    TagTeam::factory()->suspended()->create();
    $member = TagTeam::factory()->bookable()->create();
    StableTagTeam::query()->create([
        'stable_id' => Stable::factory()->create()->id,
        'tag_team_id' => $member->id,
        'joined_at' => now()->subMonth(),
    ]);

    // Act
    $tagTeams = TagTeam::query()->joinableToStable()->pluck('id');

    // Assert
    expect($tagTeams->all())->toBe([$joinable->id]);
});

test('joinableToStable accepts a former member of a stable', function () {
    // Arrange
    $former = Wrestler::factory()->bookable()->create();
    StableWrestler::query()->create([
        'stable_id' => Stable::factory()->create()->id,
        'wrestler_id' => $former->id,
        'joined_at' => now()->subMonths(3),
        'left_at' => now()->subMonth(),
    ]);

    // Act
    $wrestlers = Wrestler::query()->joinableToStable()->pluck('id');

    // Assert
    expect($wrestlers->all())->toBe([$former->id]);
});

test('mergeCandidatesFor lists the other active unretired stables of the same promotion', function () {
    // Arrange
    $stable = Stable::factory()->active()->create();
    $candidate = Stable::factory()->active()->create();
    Stable::factory()->inactive()->create();
    Stable::factory()->retired()->create();
    Stable::factory()->active()->create(['promotion_id' => Promotion::factory()->create()->id]);

    // Act
    $candidates = Stable::query()->mergeCandidatesFor($stable)->pluck('id');

    // Assert
    expect($candidates->all())->toBe([$candidate->id]);
});
