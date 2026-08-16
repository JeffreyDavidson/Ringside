<?php

declare(strict_types=1);

use App\Enums\MatchFinish;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Roster\Wrestlers\Wrestler;

beforeEach(function () {
    $this->event = Event::factory()->create();
    $this->match = EventMatch::factory()->for($this->event)->create();
    $firstWrestler = Wrestler::factory()->create(['name' => 'First Competitor']);
    $secondWrestler = Wrestler::factory()->create(['name' => 'Second Competitor']);

    foreach ([$firstWrestler, $secondWrestler] as $index => $wrestler) {
        $side = MatchSide::factory()->for($this->match, 'match')->create([
            'position' => $index + 1,
        ]);

        MatchCompetitor::factory()->create([
            'match_id' => $this->match->id,
            'match_side_id' => $side->id,
            'competitor_type' => $wrestler->getMorphClass(),
            'competitor_id' => $wrestler->id,
        ]);
    }

    $this->winningSide = $this->match->sides()->firstOrFail();
    $this->actingAs(administrator());
});

test('administrator can record a match result', function () {
    $page = visit(route('events.show', $this->event));

    $page->press('@match-result-action')
        ->waitForText('Record Match Result')
        ->select('#finish', MatchFinish::Pinfall->value)
        ->select('#winningSideId', $this->winningSide->id)
        ->press('@save-result')
        ->waitForText('Correct Result')
        ->assertSee('First Competitor by Pinfall')
        ->assertNoJavascriptErrors();

    expect($this->match->refresh()->match_finish)->toBe(MatchFinish::Pinfall)
        ->and($this->match->winning_side_id)->toBe($this->winningSide->id);
});

test('administrator can correct a match result', function () {
    $this->match->update([
        'match_finish' => MatchFinish::Pinfall,
        'winning_side_id' => $this->winningSide->id,
    ]);

    $page = visit(route('events.show', $this->event));

    $page->press('@match-result-action')
        ->waitForText('Correct Match Result')
        ->select('#finish', MatchFinish::TimeLimitDraw->value)
        ->press('@save-result')
        ->waitForText('Time Limit Draw')
        ->assertNoJavascriptErrors();

    expect($this->match->refresh()->match_finish)->toBe(MatchFinish::TimeLimitDraw)
        ->and($this->match->winning_side_id)->toBeNull();
});
