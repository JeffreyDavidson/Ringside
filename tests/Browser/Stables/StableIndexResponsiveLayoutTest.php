<?php

declare(strict_types=1);

use App\Models\Roster\Stables\Stable;

test('stable index filters and table fit narrow and wide viewports', function (): void {
    // Arrange
    Stable::factory()->active()->create(['name' => 'The Four Horsemen']);
    $this->actingAs(administrator());

    // Act
    $page = visit(route('stables.index'));
    $page->resize(390, 844);

    // Assert
    $page
        ->assertSee('The Four Horsemen')
        ->assertVisible('#stables-status')
        ->assertScript('document.querySelector("[data-test=stables-table]").getBoundingClientRect().right <= innerWidth')
        ->resize(320, 740)
        ->assertSee('The Four Horsemen')
        ->assertScript('document.querySelector("[data-test=stables-table]").getBoundingClientRect().right <= innerWidth')
        ->assertScript('document.documentElement.scrollWidth <= innerWidth')
        ->resize(768, 1024)
        ->assertScript('document.querySelector("[data-test=stables-table]").getBoundingClientRect().right <= innerWidth')
        ->assertScript('document.documentElement.scrollWidth <= innerWidth')
        ->resize(390, 844)
        ->click('Filters')
        ->assertVisible('#stables-date-from')
        ->assertVisible('#stables-date-to')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=stables-date-range-grid]")).gridTemplateColumns.split(" ").length === 2')
        ->resize(320, 740)
        ->assertScript('getComputedStyle(document.querySelector("[data-test=stables-date-range-grid]")).gridTemplateColumns.split(" ").length === 1')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->resize(390, 844)
        ->screenshot(fullPage: false, filename: 'stables-index-mobile')
        ->click('Filters')
        ->resize(1440, 900)
        ->screenshot(fullPage: false, filename: 'stables-index-desktop')
        ->assertNoJavascriptErrors()
        ->assertNoAccessibilityIssues();
});
