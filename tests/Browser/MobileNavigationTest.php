<?php

declare(strict_types=1);

const OPEN_NAVIGATION = 'button[aria-label="Open navigation"]';

beforeEach(function (): void {
    $this->actingAs(administrator());
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
    $page->resize(375, 812);

    // Act
    $page->click(OPEN_NAVIGATION)
        ->wait(0.2);

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
    $page->keys('aside a[aria-label="Wrestlers"]', 'Escape')
        ->wait(0.2);

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
    $page->resize(375, 812);
    $page->click(OPEN_NAVIGATION)
        ->wait(0.2);

    // Act
    $page->resize(1440, 900)
        ->wait(0.2);

    // Assert
    $page->assertScript('document.querySelector("[data-test=mobile-navigation]").hasAttribute("role") === false')
        ->assertScript('document.querySelector("[data-test=app-shell-wrapper]").inert === false')
        ->assertScript('document.querySelector("aside").checkVisibility({ visibilityProperty: true })')
        ->assertNoJavascriptErrors();
});
