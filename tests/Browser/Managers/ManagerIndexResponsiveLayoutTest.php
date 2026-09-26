<?php

declare(strict_types=1);

use App\Models\Roster\Managers\Manager;

test('manager index filters and table fit narrow and wide viewports', function (): void {
    // Arrange
    Manager::factory()->employed()->create([
        'first_name' => 'Bobby',
        'last_name' => 'Heenan',
    ]);
    $this->actingAs(administrator());

    // Act
    $page = visit(route('managers.index'));
    $page->resize(390, 844);

    // Assert
    $page
        ->assertSee('Bobby Heenan')
        ->assertVisible('#managers-status')
        ->assertScript('document.querySelector("[data-test=managers-table]").getBoundingClientRect().right <= innerWidth')
        ->resize(320, 740)
        ->assertSee('Bobby Heenan')
        ->assertScript('document.querySelector("[data-test=managers-table]").getBoundingClientRect().right <= innerWidth')
        ->assertScript('document.documentElement.scrollWidth <= innerWidth')
        ->resize(768, 1024)
        ->assertScript('document.querySelector("[data-test=managers-table]").getBoundingClientRect().right <= innerWidth')
        ->assertScript('document.documentElement.scrollWidth <= innerWidth')
        ->resize(390, 844)
        ->screenshot(fullPage: false, filename: 'managers-index-mobile-review')
        ->click('Filters')
        ->assertVisible('#managers-date-from')
        ->assertVisible('#managers-date-to')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->click('Filters')
        ->resize(1440, 900)
        ->assertVisible('[data-test=managers-status-filters]')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->screenshot(fullPage: false, filename: 'managers-index-desktop-review')
        ->assertNoJavascriptErrors()
        ->assertNoAccessibilityIssues();
});

test('manager empty-state help uses the available width on desktop', function (): void {
    $this->actingAs(administrator());

    $page = visit(route('managers.index'));
    $page->resize(1440, 900);

    $page
        ->assertSee('Add a manager to build your roster and represent your talent.')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=managers-empty-state] p")).maxWidth === "none"')
        ->assertScript('document.querySelector("[data-test=managers-empty-state] p").getBoundingClientRect().height === parseFloat(getComputedStyle(document.querySelector("[data-test=managers-empty-state] p")).lineHeight)');
});
