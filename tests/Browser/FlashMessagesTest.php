<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;

test('flash messages are readable and announced through permanent live regions', function (string $type, string $region): void {
    // Arrange
    actingAs(administrator());
    $page = visit(route('dashboard'));
    $page->resize(1440, 900);
    $page->assertPresent("[data-test=flash-{$region}-region]")
        ->assertScript("document.querySelector('[data-test=flash-{$region}-region]').textContent.trim() === ''");

    // Act
    $page->script("window.dispatchEvent(new CustomEvent('flash-message', { detail: { type: '{$type}', message: 'Ringside saved the change.' } }))");

    // Assert
    $page->assertSee('Ringside saved the change.');
    waitForScript($page, "document.querySelector('[data-test=flash-{$region}-region]').textContent.trim() === 'Ringside saved the change.'");
    $page
        ->assertScript("document.querySelector('[data-test=flash-{$region}-region]').textContent.trim() === 'Ringside saved the change.'")
        ->assertNoJavascriptErrors()
        ->assertNoAccessibilityIssues();
})->with([
    'status' => ['status', 'status'],
    'error' => ['error', 'alert'],
]);

test('a repeated flash message is announced again', function (): void {
    // Arrange
    actingAs(administrator());
    $page = visit(route('dashboard'));
    $page->resize(1440, 900);
    $dispatch = "window.dispatchEvent(new CustomEvent('flash-message', { detail: { type: 'status', message: 'Wrestler has been hired.' } }))";
    $page->script($dispatch);
    waitForScript($page, 'document.querySelector("[data-test=flash-status-region]").textContent.trim() === "Wrestler has been hired."');
    $page->script('window.__ringsideAnnouncements = []; new MutationObserver(() => window.__ringsideAnnouncements.push(document.querySelector("[data-test=flash-status-region]").textContent.trim())).observe(document.querySelector("[data-test=flash-status-region]"), { childList: true, characterData: true, subtree: true })');

    // Act
    $page->script($dispatch);

    // Assert
    waitForScript($page, 'window.__ringsideAnnouncements.at(-1) === "Wrestler has been hired." && window.__ringsideAnnouncements.includes("")');
    $page->assertNoJavascriptErrors();
});
