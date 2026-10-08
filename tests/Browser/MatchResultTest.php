<?php

declare(strict_types=1);

use App\Enums\MatchFinish;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;

/**
 * @return array{
 *     event: Event,
 *     match: EventMatch,
 *     winningSide: MatchSide,
 * }
 */
function matchResultFixtures(): array
{
    $event = Event::factory()->past()->create();
    $match = EventMatch::factory()->for($event)->create();
    $firstWrestler = Wrestler::factory()->create(['name' => 'First Competitor']);
    $secondWrestler = Wrestler::factory()->create(['name' => 'Second Competitor']);

    foreach ([$firstWrestler, $secondWrestler] as $index => $wrestler) {
        $side = MatchSide::factory()->for($match, 'match')->create([
            'position' => $index + 1,
        ]);

        MatchCompetitor::factory()->create([
            'match_id' => $match->id,
            'match_side_id' => $side->id,
            'competitor_type' => $wrestler->getMorphClass(),
            'competitor_id' => $wrestler->id,
        ]);
    }

    $winningSide = $match->sides()->firstOrFail();
    actingAs(administrator());

    return [
        'event' => $event,
        'match' => $match,
        'winningSide' => $winningSide,
    ];
}

test('administrator can record a match result', function () {
    ['event' => $event, 'winningSide' => $winningSide, 'match' => $match] = matchResultFixtures();

    $page = visit(route('events.show', $event));

    $page->press('@match-result-action')
        ->waitForText('Record Match Result')
        ->select('#finish', MatchFinish::Pinfall->value)
        ->select('#winningSideId', $winningSide->id)
        ->press('@save-result')
        ->waitForText('Correct Result')
        ->assertSee('First Competitor by Pinfall')
        ->assertNoJavascriptErrors();

    expect($match->refresh()->match_finish)->toBe(MatchFinish::Pinfall)
        ->and($match->winning_side_id)->toBe($winningSide->id);
});

test('administrator can correct a match result', function () {
    ['match' => $match, 'winningSide' => $winningSide, 'event' => $event] = matchResultFixtures();

    $match->update([
        'match_finish' => MatchFinish::Pinfall,
        'winning_side_id' => $winningSide->id,
    ]);

    $page = visit(route('events.show', $event));

    $page->press('@match-result-action')
        ->waitForText('Correct Match Result')
        ->select('#finish', MatchFinish::TimeLimitDraw->value)
        ->press('@save-result')
        ->waitForText('Time Limit Draw')
        ->assertScript('!document.querySelector("#modal-container").checkVisibility()');
    waitForModalToClose($page);
    $page->assertNoJavascriptErrors();

    expect($match->refresh()->match_finish)->toBe(MatchFinish::TimeLimitDraw)
        ->and($match->winning_side_id)->toBeNull();
});
