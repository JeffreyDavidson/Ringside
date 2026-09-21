<?php

declare(strict_types=1);

use App\Builders\Matches\MatchSideBuilder;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchSide;

test('match sides use the typed builder', function () {
    expect(MatchSide::query())->toBeInstanceOf(MatchSideBuilder::class);
});

test('match side relationships are ordered by their position', function () {
    // Arrange
    $eventMatch = EventMatch::factory()->create();
    $otherMatch = EventMatch::factory()->create();

    $thirdSide = MatchSide::factory()->for($eventMatch, 'match')->create(['position' => 3]);
    $firstSide = MatchSide::factory()->for($eventMatch, 'match')->create(['position' => 1]);
    $secondSide = MatchSide::factory()->for($eventMatch, 'match')->create(['position' => 2]);
    $otherSide = MatchSide::factory()->for($otherMatch, 'match')->create(['position' => 1]);

    // Act
    $sides = $eventMatch->sides();
    $sideIds = $sides->pluck('id');
    $otherSides = $otherMatch->sides();
    $otherSideIds = $otherSides->pluck('id');

    // Assert
    expect($sideIds->all())->toBe([
        $firstSide->id,
        $secondSide->id,
        $thirdSide->id,
    ])->and($otherSideIds->all())->toBe([$otherSide->id]);
});
