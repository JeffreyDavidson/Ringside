<?php

declare(strict_types=1);
use App\Enums\Matches\MatchFinish;
use App\Enums\Tournaments\EntrantKind;

use function Pest\Laravel\actingAs;

it('keeps the rounds readable on phones and contains bracket scrolling', function (): void {
    $world = tournamentFixture(capacity: 8);
    $tournament = $world['tournament'];
    bookTournamentNode($world, $tournament->nodes()->firstOrFail());
    actingAs(administrator());
    $page = visit(route('tournaments.show', $tournament));
    $page->resize(1440, 1000)->assertSee('Autumn Cup')->assertSee('Round 1')->assertNoJavaScriptErrors()->screenshot(fullPage: true, filename: 'tournament-desktop-rounds');
    $page->press('Bracket')->assertSee('Scroll within the bracket')->assertScript('document.querySelectorAll("[data-bracket-connector]").length===6')->assertNoJavaScriptErrors()->screenshot(fullPage: true, filename: 'tournament-desktop-bracket');
    $page->script('document.querySelector("#main-content").scrollTop=500');
    $page->screenshot(fullPage: false, filename: 'tournament-desktop-bracket-lower');
    $page->script('document.querySelector("#main-content").scrollTop=0');
    $page->resize(390, 844)->press('Rounds')->assertDontSee('Scroll within the bracket')->assertSee('Round 1')->assertScript('document.documentElement.scrollWidth <= window.innerWidth')->screenshot(fullPage: true, filename: 'tournament-mobile-rounds');
    $page->press('Bracket')->assertSee('Scroll within the bracket')->assertScript('document.documentElement.scrollWidth <= window.innerWidth')->screenshot(fullPage: true, filename: 'tournament-mobile-bracket');
    $page->script('document.querySelector("#main-content").scrollTop=500');
    $page->screenshot(fullPage: false, filename: 'tournament-mobile-bracket-lower');
    $page->script('document.querySelector("#main-content").scrollTop=0');
    $page->resize(320, 812)->assertScript('document.documentElement.scrollWidth <= window.innerWidth')->assertNoJavaScriptErrors();
});

it('keeps sixteen long tag-team names readable and the bracket contained on phones', function (): void {
    $world = tournamentFixture(capacity: 16, kind: EntrantKind::TagTeam);
    $t = $world['tournament'];

    foreach ($t->entries()->get() as $entry) {
        $entry->competitor()->firstOrFail()->update(['name' => 'The North Atlantic Championship Alliance '.$entry->seed]);
    }
    bookTournamentNode($world, $t->nodes()->firstOrFail());
    actingAs(administrator());
    $page = visit(route('tournaments.show', $t))->resize(390, 844)->assertSee('The North Atlantic Championship Alliance 1')->assertSee('Round 4')->assertScript('document.documentElement.scrollWidth <= window.innerWidth')->assertNoJavaScriptErrors()->screenshot(fullPage: false, filename: 'tournament-mobile-teams');
    $page->press('Bracket')->assertSee('Scroll within the bracket')->assertScript('document.querySelectorAll("[data-bracket-connector]").length===14')->assertScript('document.documentElement.scrollWidth <= window.innerWidth')->screenshot(fullPage: false, filename: 'tournament-mobile-teams-bracket');
    $page->resize(320, 812)->assertScript('document.documentElement.scrollWidth <= window.innerWidth')->assertNoJavaScriptErrors()->screenshot(fullPage: false, filename: 'tournament-small-teams-bracket');
});

it('records and corrects tournament results through the ordinary result modal', function (): void {
    $world = tournamentFixture();
    $t = $world['tournament'];
    $match = bookTournamentNode($world, $t->nodes()->firstOrFail());
    $winner = $match->sides()->firstOrFail();
    actingAs(administrator());
    $page = visit(route('tournaments.show', $t));
    $page->resize(390, 844);
    $page->press('Record result')->waitForText('Record Match Result');
    waitForModalReady($page);
    $page->select('#finish', MatchFinish::Pinfall->value)->select('#winningSideId', $winner->id)->press('@save-result');
    waitForModalToClose($page);
    $page->waitForText('Advanced')->assertSee('Pinfall')->assertNoJavascriptErrors();
    expect($match->refresh()->winning_side_id)->toBe($winner->id);
    $page->press('Correct result')->waitForText('Correct Match Result');
    waitForModalReady($page);
    $page->select('#finish', MatchFinish::TimeLimitDraw->value)->press('@save-result');
    waitForModalToClose($page);
    $page->waitForText('Book rematch')->assertNoJavascriptErrors();
    expect($match->refresh()->match_finish)->toBe(MatchFinish::TimeLimitDraw)->and($match->winning_side_id)->toBeNull();
});

it('shows unavailable entries and meaningful list progress in the phone interface', function (): void {
    $world = tournamentFixture();
    $t = $world['tournament'];
    $node = $t->nodes()->where('round', 1)->where('position', 2)->firstOrFail();
    $match = bookTournamentNode($world, $node);
    recordTournamentWinner($match, $t);
    $t->entries()->where('seed', 1)->firstOrFail()->competitor()->firstOrFail()->delete();
    actingAs(administrator());
    visit(route('tournaments.show', $t))->resize(390, 844)->assertSee('No longer bookable')->assertScript('document.documentElement.scrollWidth <= window.innerWidth')->assertNoJavascriptErrors()->screenshot(fullPage: false, filename: 'tournament-mobile-unavailable');
    visit(route('tournaments.index'))->resize(390, 844)->assertSee('4 entrants · 1 recorded result')->assertScript('document.documentElement.scrollWidth <= window.innerWidth')->assertNoJavascriptErrors()->screenshot(fullPage: false, filename: 'tournament-mobile-index');
});

it('labels actual byes and permits booking only the resolved final', function (): void {
    $world = tournamentFixture(count: 2);
    $t = $world['tournament'];
    $final = $t->nodes()->where('round', 2)->firstOrFail();
    bookTournamentNode($world, $final);
    actingAs(administrator());
    $page = visit(route('tournaments.show', $t))->resize(390, 844)->assertSee('Bye')->assertSee('Awaiting result')->assertScript('document.documentElement.scrollWidth <= window.innerWidth')->assertNoJavascriptErrors();
    $page->script('document.querySelector("#main-content").scrollTop=420');
    $page->screenshot(fullPage: false, filename: 'tournament-mobile-byes');
    expect($world['event']->matches()->count())->toBe(1)->and($t->links()->count())->toBe(1);
});
