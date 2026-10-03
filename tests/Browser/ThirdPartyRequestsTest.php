<?php

declare(strict_types=1);

test('loading the application keeps every request on the application host', function (): void {
    // Arrange
    $this->actingAs(administrator());

    // Act
    $page = visit(route('dashboard'));

    // Assert
    $page->assertScript('async () => { await document.fonts.ready; const urls = [...performance.getEntriesByType("resource").map((entry) => entry.name), ...[...document.querySelectorAll("link[href], script[src], img[src], iframe[src]")].map((element) => element.href || element.src)]; return urls.length > 0 && urls.every((url) => new URL(url, location.href).host === location.host); }')
        ->assertScript('async () => { await document.fonts.ready; return [...document.fonts].some((font) => font.family.replaceAll("\"", "") === "Inter" && font.status === "loaded"); }')
        ->assertNoJavascriptErrors();
});
