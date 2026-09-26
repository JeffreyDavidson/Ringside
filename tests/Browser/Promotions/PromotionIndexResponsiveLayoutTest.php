<?php

declare(strict_types=1);

use App\Models\Promotions\Promotion;

test('promotion table exposes horizontal overflow on narrow screens', function (): void {
    // Arrange
    Promotion::factory()->create(['name' => 'Ringside Local Demo']);
    $this->actingAs(administrator());

    // Act
    $page = visit(route('promotions.index'));
    $page->resize(390, 844);

    // Assert
    $page
        ->assertSee('Ringside Local Demo')
        ->assertSee('Scroll horizontally to view all columns')
        ->assertAttribute('[data-test="resource-table-scroll"]', 'tabindex', '0')
        ->assertScript('(() => { const region = document.querySelector("[data-test=resource-table-scroll]"); const maxScrollLeft = region.scrollWidth - region.clientWidth; region.scrollLeft = maxScrollLeft; const canScroll = region.scrollLeft > 0; region.scrollLeft = 0; return canScroll; })()')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->screenshot(fullPage: false, filename: 'promotions-index-mobile-table')
        ->resize(1440, 900)
        ->assertScript('getComputedStyle(document.querySelector("#promotions-table-scroll-hint")).display === "none"')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth');
});
