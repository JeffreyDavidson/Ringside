<?php

declare(strict_types=1);

use App\Models\Roster\Referees\Referee;

test('referee index filters and table fit narrow and wide viewports', function (): void {
    // Arrange
    Referee::factory()->employed()->create([
        'first_name' => 'Earl',
        'last_name' => 'Hebner',
    ]);
    $this->actingAs(administrator());

    // Act
    $page = visit(route('referees.index'));
    $page->resize(390, 844);

    // Assert
    $page
        ->assertSee('Earl Hebner')
        ->assertVisible('#referees-status')
        ->assertScript('document.querySelector("[data-test=referees-table]").getBoundingClientRect().right <= innerWidth')
        ->screenshot(fullPage: false, filename: 'referees-index-mobile-review')
        ->click('Filters')
        ->assertVisible('#referees-date-from')
        ->assertVisible('#referees-date-to')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->click('Filters')
        ->resize(1440, 900)
        ->assertVisible('[data-test=referees-status-filters]')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->screenshot(fullPage: false, filename: 'referees-index-desktop-review')
        ->assertNoJavascriptErrors()
        ->assertNoAccessibilityIssues();
});
