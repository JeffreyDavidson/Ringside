<?php

declare(strict_types=1);

use Pest\Browser\Api\PendingAwaitablePage;

use function Pest\Laravel\actingAs;

const OPEN_NAVIGATION = 'button[aria-label="Open navigation"]';

/**
 * Wait until the hamburger reports the drawer as open or closed and its transition has finished.
 */
function waitForNavigationState(PendingAwaitablePage $page, bool $open): void
{
    $expanded = $open ? 'true' : 'false';

    waitForSettledScript($page, 'document.querySelector(\''.OPEN_NAVIGATION.'\').getAttribute("aria-expanded") === "'.$expanded.'"');
}

beforeEach(function (): void {
    actingAs(administrator());
});

test('the closed mobile navigation stays out of the tab order', function (): void {
    // Arrange
    $page = visit(route('wrestlers.index'));

    // Act
    $page->resize(375, 812);

    // Assert
    $page->assertAttribute(OPEN_NAVIGATION, 'aria-expanded', 'false')
        ->assertScript('document.getElementById(document.querySelector(\''.OPEN_NAVIGATION.'\').getAttribute("aria-controls")) !== null')
        ->assertScript('document.querySelector("aside").checkVisibility({ visibilityProperty: true }) === false')
        ->assertScript('[...document.querySelectorAll("aside a[href], aside button")].every((control) => { control.focus(); return document.activeElement !== control; })')
        ->assertNoJavascriptErrors();
});

test('the open mobile navigation is a modal dialog that traps focus and closes with escape', function (): void {
    // Arrange
    $page = visit(route('wrestlers.index'));
    resizeAndSettle($page, 375, 812);

    // Act
    $page->click(OPEN_NAVIGATION);
    waitForNavigationState($page, true);

    // Assert
    $page->assertAttribute(OPEN_NAVIGATION, 'aria-expanded', 'true')
        ->assertAttribute('[data-test=mobile-navigation]', 'role', 'dialog')
        ->assertAttribute('[data-test=mobile-navigation]', 'aria-modal', 'true')
        ->assertAttribute('[data-test=mobile-navigation]', 'aria-label', 'Main navigation')
        ->assertScript('document.activeElement.closest("aside") !== null')
        ->assertScript('document.querySelector("[data-test=app-shell-wrapper]").inert === true')
        ->keys('[data-test=profile-menu]', 'Tab')
        ->assertScript('document.activeElement.closest("aside") !== null')
        ->assertNoAccessibilityIssues();

    // Act
    $page->keys('aside a[aria-label="Wrestlers"]', 'Escape');
    waitForNavigationState($page, false);

    // Assert
    $page->assertAttribute(OPEN_NAVIGATION, 'aria-expanded', 'false')
        ->assertScript('document.activeElement === document.querySelector(\''.OPEN_NAVIGATION.'\')')
        ->assertScript('document.querySelector("[data-test=mobile-navigation]").hasAttribute("role") === false')
        ->assertScript('document.querySelector("[data-test=app-shell-wrapper]").inert === false')
        ->assertNoJavascriptErrors();
});

test('widening the window closes the mobile navigation', function (): void {
    // Arrange
    $page = visit(route('wrestlers.index'));
    resizeAndSettle($page, 375, 812);
    $page->click(OPEN_NAVIGATION);
    waitForNavigationState($page, true);

    // Act
    resizeAndSettle($page, 1440, 900);

    // Assert
    $page->assertScript('document.querySelector("[data-test=mobile-navigation]").hasAttribute("role") === false')
        ->assertScript('document.querySelector("[data-test=app-shell-wrapper]").inert === false')
        ->assertScript('document.querySelector("aside").checkVisibility({ visibilityProperty: true })')
        ->assertNoJavascriptErrors();
});
