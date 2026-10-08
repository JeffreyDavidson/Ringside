<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;

test('the first tab stop skips the navigation to the main content', function (): void {
    // Arrange
    actingAs(administrator());
    $page = visit(route('wrestlers.index'));
    $page->resize(1440, 900);
    $page->assertScript('document.querySelector("[data-test=skip-link]") === document.querySelector("a[href], button, input, select, textarea, [tabindex]:not([tabindex=\\"-1\\"])")')
        ->assertAttribute('[data-test=skip-link]', 'href', '#main-content')
        ->assertAttribute('main#main-content', 'tabindex', '-1');

    // Act
    $page->keys('[data-test=skip-link]', 'Enter');

    // Assert
    $page->assertScript('document.activeElement === document.querySelector("main#main-content")')
        ->assertNoJavascriptErrors();
});

test('the skip link becomes visible when it receives focus', function (): void {
    // Arrange
    actingAs(administrator());
    $page = visit(route('dashboard'));
    $page->resize(375, 812);

    // Act
    $page->script('document.querySelector("[data-test=skip-link]").focus()');

    // Assert
    $page->assertScript('(() => { const box = document.querySelector("[data-test=skip-link]").getBoundingClientRect(); return box.width > 40 && box.height > 20 && box.top >= 0 && box.left >= 0; })()')
        ->assertNoAccessibilityIssues();
});
