<?php

declare(strict_types=1);

use App\Models\Events\Event;
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

test('event index filters and table fit a narrow viewport', function (): void {
    // Arrange
    $venue = Venue::factory()->create(['name' => 'Riverside Hall']);
    Event::factory()->scheduled()->atVenue($venue)->create(['name' => 'Summer Showdown']);
    $this->actingAs(administrator());

    // Act
    $page = visit(route('events.index'));
    $page->resize(390, 844);

    // Assert
    $page
        ->assertSee('Summer Showdown')
        ->assertSee('Riverside Hall')
        ->assertScript('document.querySelector("#events-venue").closest("[data-test=table-toolbar]") !== null')
        ->assertVisible('#events-status')
        ->assertVisible('#events-venue')
        ->assertScript('document.querySelector("#events-venue").closest("details") === null')
        ->assertScript('document.querySelector("[data-test=events-table]").getBoundingClientRect().right <= innerWidth')
        ->resize(320, 740)
        ->assertSee('Summer Showdown')
        ->assertScript('document.querySelector("[data-test=events-table]").getBoundingClientRect().right <= innerWidth')
        ->assertScript('document.documentElement.scrollWidth <= innerWidth')
        ->resize(768, 1024)
        ->assertScript('document.querySelector("[data-test=events-table]").getBoundingClientRect().right <= innerWidth')
        ->assertScript('document.documentElement.scrollWidth <= innerWidth')
        ->resize(390, 844)
        ->click('Filters')
        ->assertVisible('#events-date-from')
        ->assertVisible('#events-date-to')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=events-date-range-grid]")).gridTemplateColumns.split(" ").length === 2')
        ->resize(320, 740)
        ->assertScript('getComputedStyle(document.querySelector("[data-test=events-date-range-grid]")).gridTemplateColumns.split(" ").length === 1')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->screenshot(fullPage: false, filename: 'events-index-mobile')
        ->resize(1440, 900)
        ->screenshot(fullPage: false, filename: 'events-index-desktop')
        ->assertNoJavascriptErrors();
});
