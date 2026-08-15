<?php

declare(strict_types=1);

use App\Builders\Matches\MatchSideBuilder;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchSide;

test('match sides use the typed builder', function () {
    expect(MatchSide::query())->toBeInstanceOf(MatchSideBuilder::class);
});

test('match sides can be ordered by their position', function () {
    $eventMatch = EventMatch::factory()->create();

    $thirdSide = MatchSide::factory()->for($eventMatch, 'match')->create(['position' => 3]);
    $firstSide = MatchSide::factory()->for($eventMatch, 'match')->create(['position' => 1]);
    $secondSide = MatchSide::factory()->for($eventMatch, 'match')->create(['position' => 2]);

    expect(MatchSide::query()
        ->whereBelongsTo($eventMatch, 'match')
        ->inPositionOrder()
        ->pluck('id')
        ->all())->toBe([
            $firstSide->id,
            $secondSide->id,
            $thirdSide->id,
        ])->and($eventMatch->sides()->pluck('id')->all())->toBe([
            $firstSide->id,
            $secondSide->id,
            $thirdSide->id,
        ]);
});
