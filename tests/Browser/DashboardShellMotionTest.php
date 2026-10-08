<?php

declare(strict_types=1);

use Pest\Browser\Api\PendingAwaitablePage;

use function Pest\Laravel\actingAs;

/**
 * Click a sidebar toggle and assert that each element starts a running animation while the menu icon stays pinned.
 *
 * A single check right after the click can miss a 0.6 second animation on a slow runner, so the script clicks and then
 * keeps watching until every element has been seen animating (or three seconds pass), checking on each pass that the
 * menu icon never slides away from the centre of the collapsed rail.
 *
 * @param  array<int, string>  $selectors
 */
function assertClickStartsMotion(PendingAwaitablePage $page, string $button, array $selectors): void
{
    $button = json_encode($button, JSON_THROW_ON_ERROR);
    $selectors = json_encode($selectors, JSON_THROW_ON_ERROR);

    $page->assertScript(<<<JS
        () => new Promise((resolve) => {
            const pending = new Set({$selectors});
            const deadline = Date.now() + 3000;
            let pinned = true;
            const watch = () => {
                const icon = document.querySelector('[data-test=sidebar-menu-icon]').getBoundingClientRect();
                const sidebar = document.querySelector('aside').getBoundingClientRect();
                pinned = pinned && Math.abs(icon.left + icon.width / 2 - sidebar.left - 44) < 0.5;
                for (const selector of pending) {
                    const element = document.querySelector(selector);
                    if (element?.getAnimations().some((animation) => animation.playState === 'running')) {
                        pending.delete(selector);
                    }
                }
                if (pending.size === 0 || Date.now() > deadline) {
                    resolve(pinned && pending.size === 0);
                } else {
                    setTimeout(watch, 10);
                }
            };
            document.querySelector({$button}).click();
            watch();
        })
        JS);
}

test('dashboard shell renders at its expanded offset and content reflows without sliding', function (): void {
    actingAs(administrator());

    $page = visit(route('dashboard'));
    $page->resize(1440, 1000);

    $page
        ->assertSee('Overview')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=sidebar-tooltip]")).display === "none"')
        ->assertScript('document.querySelector("[data-test=app-shell-wrapper]").style.getPropertyValue("--shell-sidebar-width") === "var(--sidebar-default-width)"')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=app-shell-wrapper]")).paddingLeft === "288px"')
        ->assertScript('getComputedStyle(document.querySelector(".sidebar-brand-full")).opacity === "1"')
        ->assertScript('getComputedStyle(document.querySelector(".sidebar-brand-short")).opacity === "0"')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=sidebar-menu-label]")).transitionDuration === "0.6s"')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=sidebar-menu-heading-label]")).opacity === "1"')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=sidebar-menu-heading-collapsed]")).opacity === "0"')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=sidebar-menu-heading-label]")).transitionDuration === "0.6s"')
        ->assertScript('getComputedStyle(document.querySelector(".sidebar-toggle-icon")).transitionDuration === "0.3s"')
        ->assertScript('getComputedStyle(document.querySelector(".sidebar-toggle-icon")).transitionTimingFunction === "cubic-bezier(0.4, 0, 0.2, 1)"')
        ->assertScript('getComputedStyle(document.querySelector("button[aria-label=\"Collapse sidebar\"]")).cursor === "pointer"')
        ->assertScript('getComputedStyle(document.querySelector(".sidebar-brand-short")).fontSize === "24px"')
        ->assertScript('document.querySelector("[data-sidebar-tooltip][data-tooltip=Overview]").getAttribute("title") === "Overview"')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=app-shell-wrapper]")).transitionProperty === "padding"')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=app-shell-wrapper]")).transitionDuration === "0.6s"')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=app-shell-header]")).left === "288px"')
        ->assertScript('document.querySelector("[data-test=app-shell-header]").getAnimations().length === 0');

    assertClickStartsMotion($page, 'button[aria-label="Collapse sidebar"]', ['[data-test=app-shell-wrapper]', '.sidebar-brand-short', '[data-test=sidebar-menu-label]']);
    waitForAnimationsToSettle($page);

    $page
        ->assertScript('document.querySelector("[data-test=app-shell-wrapper]").style.getPropertyValue("--shell-sidebar-width") === "var(--sidebar-collapsed-width)"')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=app-shell-wrapper]")).paddingLeft === "88px"')
        ->assertScript('getComputedStyle(document.querySelector(".sidebar-brand-full")).opacity === "0"')
        ->assertScript('getComputedStyle(document.querySelector(".sidebar-brand-short")).opacity === "1"')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=sidebar-menu-heading-label]")).opacity === "0"')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=sidebar-menu-heading-collapsed]")).opacity === "1"')
        ->assertScript('getComputedStyle(document.querySelector(".sidebar-toggle-icon")).transform === "matrix(-1, 0, 0, -1, 0, 0)"')
        ->assertScript('Math.abs((() => { const icon = document.querySelector("[data-test=sidebar-menu-icon]").getBoundingClientRect(); const sidebar = document.querySelector("aside").getBoundingClientRect(); return icon.left + icon.width / 2 - sidebar.left - 44; })()) < 0.5')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=app-shell-header]")).left === "88px"')
        ->hover('[data-sidebar-tooltip][data-tooltip="Overview"]')
        ->assertVisible('[data-test=sidebar-tooltip]');
    waitForScript($page, 'document.querySelector("[data-test=sidebar-tooltip]").textContent.trim() === "Overview"');

    $page->hover('[data-sidebar-tooltip][data-tooltip="User management"]');
    waitForScript($page, 'document.querySelector("[data-test=sidebar-tooltip]").textContent.trim() === "User management"');

    $page->hover('[data-sidebar-tooltip][data-tooltip="Account menu"]');
    waitForScript($page, 'document.querySelector("[data-test=sidebar-tooltip]").textContent.trim() === "Account menu"');

    assertClickStartsMotion($page, 'button[aria-label="Expand sidebar"]', ['[data-test=app-shell-wrapper]', '[data-test=sidebar-menu-label]']);
    waitForAnimationsToSettle($page);

    $page
        ->assertScript('getComputedStyle(document.querySelector("[data-test=app-shell-wrapper]")).paddingLeft === "288px"')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=sidebar-tooltip]")).display === "none"')
        ->assertScript('getComputedStyle(document.querySelector(".sidebar-toggle-icon")).transform === "matrix(1, 0, 0, 1, 0, 0)"')
        ->assertScript('Math.abs((() => { const icon = document.querySelector("[data-test=sidebar-menu-icon]").getBoundingClientRect(); const label = document.querySelector("[data-test=sidebar-menu-label]").getBoundingClientRect(); return label.left - icon.right - 12; })()) < 0.5')
        ->assertNoJavascriptErrors();
});

test('sidebar expanded state persists through page navigation', function (): void {
    actingAs(administrator());

    $page = visit(route('dashboard'));
    $page->resize(1440, 1000);
    $page->script('document.querySelector("button[aria-label=\"Expand sidebar\"]")?.click()');
    waitForAnimationsToSettle($page);
    $page->script('localStorage.removeItem("ringside.sidebar.expanded")');
    $page->click('button[aria-label="Collapse sidebar"]');
    waitForScript($page, 'document.querySelector("aside").dataset.collapsed === "true"');
    waitForAnimationsToSettle($page);
    $page->assertScript('localStorage.getItem("ringside.sidebar.expanded") === "false"');

    $page->click('[data-sidebar-tooltip][data-tooltip="Events"]');
    $page
        ->assertScript('location.pathname === "/events"')
        ->assertScript('document.querySelector("aside").dataset.collapsed === "true"')
        ->assertScript('getComputedStyle(document.querySelector("aside")).width === "88px"')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=app-shell-wrapper]")).paddingLeft === "88px"')
        ->assertScript('document.querySelector("aside").getAnimations().length === 0')
        ->assertScript('document.querySelector("[data-test=app-shell-wrapper]").getAnimations().length === 0')
        ->assertScript('document.querySelector("[data-sidebar-tooltip][data-tooltip=Events]").hasAttribute("title") === false')
        ->assertScript('localStorage.getItem("ringside.sidebar.expanded") === "false"');

    $page->click('button[aria-label="Expand sidebar"]');
    waitForScript($page, 'document.querySelector("aside").hasAttribute("data-collapsed") === false');
    waitForAnimationsToSettle($page);
    $page->click('[data-sidebar-tooltip][data-tooltip="Overview"]');
    $page
        ->assertScript('location.pathname === "/dashboard"')
        ->assertScript('document.querySelector("aside").hasAttribute("data-collapsed") === false')
        ->assertScript('localStorage.getItem("ringside.sidebar.expanded") === "true"')
        ->assertNoJavascriptErrors();
});
