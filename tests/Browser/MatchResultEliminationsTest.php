<?php

declare(strict_types=1);

use App\Enums\MatchFinish;
use App\Enums\MatchType;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->event = Event::factory()->past()->create();
    $this->match = EventMatch::factory()
        ->for($this->event)
        ->withCompetitors(Wrestler::factory()->count(3)->sequence(
            ['name' => 'Alpha Contender'],
            ['name' => 'Bravo Contender'],
            ['name' => 'Charlie Contender'],
        )->create()->all())
        ->create(['match_type' => MatchType::BattleRoyal]);
    actingAs(administrator());
});

test('the eliminations table is readable on the dark modal', function (): void {
    // Arrange
    $page = visit(route('events.show', $this->event));
    $page->resize(1440, 900);

    // Act
    $page->press('@match-result-action')
        ->waitForText('Record Match Result');
    waitForModalReady($page);
    $page->fill('input[aria-label="Elimination order for Alpha Contender"]', '3');

    // Assert
    $page->assertValue('input[aria-label="Elimination order for Alpha Contender"]', '3')
        ->assertScript('getComputedStyle(document.querySelector("input[aria-label=\"Elimination order for Alpha Contender\"]")).backgroundColor !== "rgb(255, 255, 255)"')
        ->assertNoJavascriptErrors()
        ->assertNoAccessibilityIssues();
});

test('elimination order errors are linked to their inputs', function (): void {
    // Arrange
    $page = visit(route('events.show', $this->event));
    $page->resize(1440, 900);

    // Act
    $page->press('@match-result-action')
        ->waitForText('Record Match Result');
    waitForModalReady($page);
    $page
        ->select('#finish', MatchFinish::Pinfall->value)
        ->fill('input[aria-label="Elimination order for Alpha Contender"]', '1')
        ->fill('input[aria-label="Elimination order for Bravo Contender"]', '1')
        ->press('@save-result')
        ->waitForText('duplicate value');

    // Assert
    $page->assertAttribute('input[aria-label="Elimination order for Alpha Contender"]', 'aria-invalid', 'true')
        ->assertScript('(() => { const input = document.querySelector("input[aria-label=\"Elimination order for Alpha Contender\"]"); const message = document.getElementById(input.getAttribute("aria-describedby")); return message !== null && message.textContent.includes("duplicate value"); })()')
        ->assertNoJavascriptErrors()
        ->assertNoAccessibilityIssues();
});

test('a rejected result is announced in a readable alert', function (): void {
    // Arrange
    $winningSide = $this->match->sides()->orderBy('position')->firstOrFail();
    $page = visit(route('events.show', $this->event));
    $page->resize(1440, 900);

    // Act
    $page->press('@match-result-action')
        ->waitForText('Record Match Result');
    waitForModalReady($page);
    $page
        ->select('#finish', MatchFinish::Stipulation->value)
        ->select('#winningSideId', (string) $winningSide->id)
        ->press('@save-result');

    // Assert
    $page->assertVisible('#modal-container [role="alert"]')
        ->assertScript('document.querySelector("#modal-container [role=alert]").textContent.trim().length > 0')
        ->assertNoJavascriptErrors()
        ->assertNoAccessibilityIssues();
});
