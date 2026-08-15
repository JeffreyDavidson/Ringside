<?php

declare(strict_types=1);

use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;

it('belongs to a match and owns its competitors', function () {
    $side = new MatchSide();

    expect($side->match())->toBeInstanceOf(BelongsTo::class)
        ->and($side->competitors())->toBeInstanceOf(HasMany::class)
        ->and($side->getFillable())->toBe(['match_id', 'position']);
});

it('persists a positioned side for a match', function () {
    $match = EventMatch::factory()->create();
    $side = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
    $competitor = MatchCompetitor::factory()->create([
        'match_id' => $match->id,
        'match_side_id' => $side->id,
    ]);

    expect($side->match->is($match))->toBeTrue()
        ->and($side->competitors->firstOrFail()->getKey())->toBe($competitor->getKey());
});

it('enforces unique side positions within a match', function () {
    $match = EventMatch::factory()->create();
    MatchSide::factory()->for($match, 'match')->create(['position' => 1]);

    expect(fn () => MatchSide::factory()->for($match, 'match')->create(['position' => 1]))
        ->toThrow(QueryException::class);
});

it('requires a competitor side to belong to the same match', function () {
    $match = EventMatch::factory()->create();
    $otherMatch = EventMatch::factory()->create();
    $otherSide = MatchSide::factory()->for($otherMatch, 'match')->create(['position' => 1]);

    expect(fn () => MatchCompetitor::factory()->create([
        'match_id' => $match->id,
        'match_side_id' => $otherSide->id,
    ]))->toThrow(QueryException::class);
});

it('allows a competitor to appear only once in a match', function () {
    $match = EventMatch::factory()->create();
    $firstSide = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
    $secondSide = MatchSide::factory()->for($match, 'match')->create(['position' => 2]);
    $competitor = MatchCompetitor::factory()->create([
        'match_id' => $match->id,
        'match_side_id' => $firstSide->id,
    ]);

    expect(fn () => MatchCompetitor::factory()->create([
        'match_id' => $match->id,
        'match_side_id' => $secondSide->id,
        'competitor_type' => $competitor->competitor_type,
        'competitor_id' => $competitor->competitor_id,
    ]))->toThrow(QueryException::class);
});
