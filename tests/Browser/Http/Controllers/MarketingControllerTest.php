<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;

test('visitors can explore the page, read answers and sign in', function (int $width, int $height): void {
    // Act
    $page = visit(route('home'));
    $page->resize($width, $height);

    // Assert
    $page->assertSee(__('marketing.hero.first'))
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues(3);

    // Act
    $page->click(__('marketing.explore'));

    // Assert
    $page->assertScript('window.location.hash === "#roster"');

    // Act
    $page->click('Does Ringside handle ticket sales?');

    // Assert
    $page->assertSee(__('marketing.faq.items.2.answer'));

    // Act
    $page->click('.header-action');

    // Assert
    $page->assertPathIs('/login')
        ->assertPresent('input[name="email"]');
})->with([
    'desktop' => [1440, 1000],
    'phone' => [390, 844],
    'small phone' => [320, 740],
]);

test('signed in visitors can return to their dashboard from a narrow screen', function (): void {
    // Arrange
    actingAs(basicUser());

    // Act
    $page = visit(route('home'));
    $page->resize(320, 740);

    // Assert
    $page->assertScript('document.documentElement.scrollWidth <= window.innerWidth');

    // Act
    $page->click('.header-action');

    // Assert
    $page->assertPathIs('/dashboard');
});
