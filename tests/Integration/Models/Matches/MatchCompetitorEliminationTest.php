<?php

declare(strict_types=1);

use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use Illuminate\Database\QueryException;

function createMatchEntrant(EventMatch $match, int $position): MatchCompetitor
{
    $side = MatchSide::factory()->for($match, 'match')->create(['position' => $position]);

    return MatchCompetitor::factory()->create([
        'match_id' => $match->id,
        'match_side_id' => $side->id,
    ]);
}

it('records entry and elimination order with the eliminating competitor', function () {
    $match = EventMatch::factory()->create();
    $eliminator = createMatchEntrant($match, 1);
    $eliminated = createMatchEntrant($match, 2);

    $eliminator->forceFill(['entry_order' => 1])->save();
    $eliminated->forceFill([
        'entry_order' => 2,
        'elimination_order' => 1,
        'eliminated_by_match_competitor_id' => $eliminator->id,
    ])->save();
    $eliminated->refresh();
    $eliminator->refresh();

    expect($eliminated->eliminatedBy?->is($eliminator))->toBeTrue()
        ->and($eliminator->eliminations->sole()->is($eliminated))->toBeTrue();
});

it('keeps entry and elimination order unique within a match', function (string $attribute) {
    $match = EventMatch::factory()->create();
    createMatchEntrant($match, 1)->forceFill([$attribute => 1])->save();
    $otherEntrant = createMatchEntrant($match, 2);

    expect(fn () => $otherEntrant->forceFill([$attribute => 1])->save())
        ->toThrow(QueryException::class);
})->with(['entry_order', 'elimination_order']);

it('retains elimination history when the eliminating competitor is removed', function () {
    $match = EventMatch::factory()->create();
    $eliminator = createMatchEntrant($match, 1);
    $eliminated = createMatchEntrant($match, 2);
    $eliminated->forceFill([
        'elimination_order' => 1,
        'eliminated_by_match_competitor_id' => $eliminator->id,
    ])->save();

    $eliminator->delete();
    $eliminated->refresh();

    expect($eliminated->eliminated_by_match_competitor_id)->toBeNull();
});
