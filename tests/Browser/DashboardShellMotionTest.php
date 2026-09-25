<?php

declare(strict_types=1);

test('dashboard shell renders at its expanded offset and content reflows without sliding', function (): void {
    $this->actingAs(administrator());

    $page = visit(route('dashboard'));
    $page->resize(1440, 1000);

    $page
        ->assertSee('Overview')
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
        ->assertScript('document.querySelector("[data-test=app-shell-header]").getAnimations().length === 0')
        ->click('button[aria-label="Collapse sidebar"]')
        ->assertScript('document.querySelector("[data-test=app-shell-wrapper]").getAnimations().some(animation => animation.playState === "running")')
        ->assertScript('document.querySelector(".sidebar-brand-short").getAnimations().some(animation => animation.playState === "running")')
        ->assertScript('Math.abs((() => { const icon = document.querySelector("[data-test=sidebar-menu-icon]").getBoundingClientRect(); const sidebar = document.querySelector("aside").getBoundingClientRect(); return icon.left + icon.width / 2 - sidebar.left - 44; })()) < 0.5')
        ->assertScript('document.querySelector("[data-test=sidebar-menu-label]").getAnimations().some(animation => animation.playState === "running")')
        ->wait(0.7)
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
        ->wait(0.25)
        ->assertVisible('[data-test=sidebar-tooltip]')
        ->assertScript('document.querySelector("[data-test=sidebar-tooltip]").textContent.trim() === "Overview"')
        ->hover('[data-sidebar-tooltip][data-tooltip="User management"]')
        ->assertScript('document.querySelector("[data-test=sidebar-tooltip]").textContent.trim() === "User management"')
        ->hover('[data-sidebar-tooltip][data-tooltip="Account menu"]')
        ->assertScript('document.querySelector("[data-test=sidebar-tooltip]").textContent.trim() === "Account menu"')
        ->click('button[aria-label="Expand sidebar"]')
        ->assertScript('document.querySelector("[data-test=app-shell-wrapper]").getAnimations().some(animation => animation.playState === "running")')
        ->assertScript('Math.abs((() => { const icon = document.querySelector("[data-test=sidebar-menu-icon]").getBoundingClientRect(); const sidebar = document.querySelector("aside").getBoundingClientRect(); return icon.left + icon.width / 2 - sidebar.left - 44; })()) < 0.5')
        ->assertScript('document.querySelector("[data-test=sidebar-menu-label]").getAnimations().some(animation => animation.playState === "running")')
        ->wait(0.7)
        ->assertScript('getComputedStyle(document.querySelector("[data-test=app-shell-wrapper]")).paddingLeft === "288px"')
        ->assertScript('getComputedStyle(document.querySelector(".sidebar-toggle-icon")).transform === "matrix(1, 0, 0, 1, 0, 0)"')
        ->assertScript('Math.abs((() => { const icon = document.querySelector("[data-test=sidebar-menu-icon]").getBoundingClientRect(); const label = document.querySelector("[data-test=sidebar-menu-label]").getBoundingClientRect(); return label.left - icon.right - 12; })()) < 0.5')
        ->assertNoJavascriptErrors();
});

test('sidebar expanded state persists through page navigation', function (): void {
    $this->actingAs(administrator());

    $page = visit(route('dashboard'));
    $page->resize(1440, 1000);
    $page->script('document.querySelector("button[aria-label=\"Expand sidebar\"]")?.click()');
    $page->wait(0.7);
    $page->script('localStorage.removeItem("ringside.sidebar.expanded")');
    $page->click('button[aria-label="Collapse sidebar"]');
    $page->wait(0.7);
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
    $page->wait(0.7);
    $page->click('[data-sidebar-tooltip][data-tooltip="Overview"]');
    $page
        ->assertScript('location.pathname === "/dashboard"')
        ->assertScript('document.querySelector("aside").hasAttribute("data-collapsed") === false')
        ->assertScript('localStorage.getItem("ringside.sidebar.expanded") === "true"')
        ->assertNoJavascriptErrors();
});
