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
        ->assertScript('getComputedStyle(document.querySelector(".sidebar-brand-short")).fontSize === "24px"')
        ->assertScript('document.querySelector("[data-sidebar-tooltip][data-tooltip=Overview]").hasAttribute("title") === false')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=app-shell-wrapper]")).transitionProperty === "padding"')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=app-shell-wrapper]")).transitionDuration === "0.6s"')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=app-shell-header]")).left === "288px"')
        ->assertScript('document.querySelector("[data-test=app-shell-header]").getAnimations().length === 0')
        ->click('button[aria-label="Collapse sidebar"]')
        ->assertScript('document.querySelector("[data-test=app-shell-wrapper]").getAnimations().some(animation => animation.playState === "running")')
        ->assertScript('document.querySelector(".sidebar-brand-short").getAnimations().some(animation => animation.playState === "running")')
        ->assertScript('document.querySelector("[data-test=sidebar-menu-icon]").getAnimations().some(animation => animation.playState === "running")')
        ->assertScript('document.querySelector("[data-test=sidebar-menu-label]").getAnimations().some(animation => animation.playState === "running")')
        ->wait(0.7)
        ->assertScript('document.querySelector("[data-test=app-shell-wrapper]").style.getPropertyValue("--shell-sidebar-width") === "var(--sidebar-collapsed-width)"')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=app-shell-wrapper]")).paddingLeft === "88px"')
        ->assertScript('getComputedStyle(document.querySelector(".sidebar-brand-full")).opacity === "0"')
        ->assertScript('getComputedStyle(document.querySelector(".sidebar-brand-short")).opacity === "1"')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=sidebar-menu-heading-label]")).opacity === "0"')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=sidebar-menu-heading-collapsed]")).opacity === "1"')
        ->assertScript('Math.abs((() => { const icon = document.querySelector("[data-test=sidebar-menu-icon]").getBoundingClientRect(); const sidebar = document.querySelector("aside").getBoundingClientRect(); return icon.left + icon.width / 2 - (sidebar.left + sidebar.width / 2); })()) < 0.5')
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
        ->assertScript('document.querySelector("[data-test=sidebar-menu-icon]").getAnimations().some(animation => animation.playState === "running")')
        ->assertScript('document.querySelector("[data-test=sidebar-menu-label]").getAnimations().some(animation => animation.playState === "running")')
        ->wait(0.7)
        ->assertScript('getComputedStyle(document.querySelector("[data-test=app-shell-wrapper]")).paddingLeft === "288px"')
        ->assertScript('Math.abs((() => { const icon = document.querySelector("[data-test=sidebar-menu-icon]").getBoundingClientRect(); const label = document.querySelector("[data-test=sidebar-menu-label]").getBoundingClientRect(); return label.left - icon.right - 12; })()) < 0.5')
        ->assertNoJavascriptErrors();
});
