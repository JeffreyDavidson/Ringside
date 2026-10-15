<?php

declare(strict_types=1);

use App\Models\Events\Event;

use function Pest\Laravel\actingAs;

test('table status dots keep their distinct green from success feedback', function (): void {
    // Arrange
    Event::factory()->scheduled()->create(['name' => 'Theme color showcase']);
    actingAs(administrator());

    // Act
    $page = visit(route('events.index'));

    // Assert
    $page->assertSee('Theme color showcase')
        ->assertScript('document.querySelector(".bg-status-positive") !== null && getComputedStyle(document.querySelector(".bg-status-positive")).backgroundColor === "rgb(23, 198, 83)"');

    // Act
    $page->script('window.dispatchEvent(new CustomEvent("flash-message", {detail: {type: "status", message: "Theme feedback saved."}}))');

    // Assert
    $page->assertSee('Theme feedback saved.')
        ->assertScript('getComputedStyle(document.querySelector(".border-success")).borderTopColor === "rgb(95, 211, 141)"')
        ->assertNoJavaScriptErrors();
});

test('primary actions and invalid fields keep their distinct rendered colors', function (): void {
    // Act
    $page = visit(route('login'));

    // Assert
    $page->assertScript('getComputedStyle(document.querySelector("[data-test=sign-in]")).backgroundColor === "rgb(203, 32, 40)"');

    // Act
    $page->hover('@sign-in');

    // Assert
    waitForScript($page, 'getComputedStyle(document.querySelector("[data-test=sign-in]")).backgroundColor === "rgb(185, 28, 36)"');

    // Act
    $page->press('@sign-in');

    // Assert
    $page->assertSee('The email field is required')
        ->assertScript('getComputedStyle(document.querySelector(".text-error")).color === "rgb(255, 163, 191)"')
        ->assertNoJavaScriptErrors();
});
