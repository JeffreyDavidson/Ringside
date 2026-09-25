<?php

declare(strict_types=1);

use App\Models\Events\Venue;

test('event schedule fields stack on narrow screens and share a row on wider screens', function (): void {
    Venue::factory()->create();
    $this->actingAs(administrator());

    $page = visit(route('events.index'));
    $page->resize(390, 844);

    $page
        ->click('Add Event')
        ->assertSee('Create Event')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=event-schedule-grid]")).gridTemplateColumns.split(" ").length === 1')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->resize(1440, 1000)
        ->assertScript('getComputedStyle(document.querySelector("[data-test=event-schedule-grid]")).gridTemplateColumns.split(" ").length === 2')
        ->assertNoJavascriptErrors();
});
