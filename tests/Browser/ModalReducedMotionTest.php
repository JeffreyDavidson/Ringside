<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Wrestlers\Wrestler;

test('modals open without animating when reduced motion is requested', function (): void {
    // Arrange
    $event = Event::factory()->past()->create();
    EventMatch::factory()
        ->for($event)
        ->withCompetitors(Wrestler::factory()->count(2)->create()->all())
        ->create();
    $this->actingAs(administrator());
    $page = visit(route('events.show', $event), ['reducedMotion' => 'reduce']);
    $page->resize(1440, 900);

    // Act
    $page->press('@match-result-action')
        ->waitForText('Record Match Result');

    // Assert
    $page->assertScript('matchMedia("(prefers-reduced-motion: reduce)").matches')
        ->assertScript('document.querySelector("#modal-container").getAnimations({ subtree: true }).length === 0')
        ->assertScript('getComputedStyle(document.querySelector("#modal-container")).transitionProperty === "none"')
        ->assertNoJavascriptErrors();
});
