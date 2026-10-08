<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;

test('venue address fields use responsive columns without horizontal overflow', function (): void {
    actingAs(administrator());

    $page = visit(route('venues.index'));
    $page->resize(390, 844);

    $page
        ->assertScript('document.querySelector("[data-test=table-metadata]").closest("[data-test=table-toolbar]") !== null')
        ->click('Add Venue')
        ->assertSeeIn('#modal-title', 'Add Venue')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=venue-address-grid]")).gridTemplateColumns.split(" ").length === 1')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->resize(900, 1000)
        ->assertScript('getComputedStyle(document.querySelector("[data-test=venue-address-grid]")).gridTemplateColumns.split(" ").length === 2')
        ->resize(1440, 1000)
        ->assertScript('getComputedStyle(document.querySelector("[data-test=venue-address-grid]")).gridTemplateColumns.split(" ").length === 3')
        ->assertNoJavascriptErrors();
});
