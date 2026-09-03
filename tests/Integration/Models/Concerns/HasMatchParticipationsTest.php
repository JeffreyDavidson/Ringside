<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

test('competitor and referee relationships share the canonical past event constraint', function () {
    $pastMatch = EventMatch::factory()
        ->forEvent(Event::factory()->past()->create())
        ->create();
    $scheduledMatch = EventMatch::factory()
        ->forEvent(Event::factory()->scheduled()->create())
        ->create();
    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    $referee = Referee::factory()->create();

    foreach ([$pastMatch, $scheduledMatch] as $match) {
        $side = MatchSide::factory()->for($match, 'match')->create();

        foreach ([$wrestler, $tagTeam] as $competitor) {
            MatchCompetitor::factory()->create([
                'match_id' => $match->id,
                'match_side_id' => $side->id,
                'competitor_type' => $competitor->getMorphClass(),
                'competitor_id' => $competitor->id,
            ]);
        }

        $match->referees()->attach($referee);
    }

    expect($wrestler->previousMatches()->pluck('events_matches.id')->all())
        ->toBe([$pastMatch->id])
        ->and($tagTeam->previousMatches()->pluck('events_matches.id')->all())
        ->toBe([$pastMatch->id])
        ->and($referee->previousMatches()->pluck('events_matches.id')->all())
        ->toBe([$pastMatch->id]);
});
