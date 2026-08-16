<?php

declare(strict_types=1);

use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Wrestlers\Wrestler;

it('groups competitor models by ordered side position', function () {
    $match = EventMatch::factory()->create();
    $firstSide = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
    $secondSide = MatchSide::factory()->for($match, 'match')->create(['position' => 2]);
    $partners = Wrestler::factory()->count(2)->create();
    $opponent = Wrestler::factory()->create();

    MatchCompetitor::factory()->create([
        'match_id' => $match->id,
        'match_side_id' => $secondSide->id,
        'competitor_type' => $opponent->getMorphClass(),
        'competitor_id' => $opponent->id,
    ]);

    foreach ($partners as $partner) {
        MatchCompetitor::factory()->create([
            'match_id' => $match->id,
            'match_side_id' => $firstSide->id,
            'competitor_type' => $partner->getMorphClass(),
            'competitor_id' => $partner->id,
        ]);
    }

    $competitorsBySide = $match->competitors()
        ->with(['side', 'competitor'])
        ->get()
        ->competitorModelsBySidePosition();

    expect($competitorsBySide->keys()->all())->toBe([1, 2])
        ->and($competitorsBySide->get(1)?->pluck('id')->all())->toBe($partners->pluck('id')->all())
        ->and($competitorsBySide->get(2)?->pluck('id')->all())->toBe([$opponent->id]);
});
