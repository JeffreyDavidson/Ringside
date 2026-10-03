<?php

declare(strict_types=1);

test('the app starts and the sidebar still toggles when browser storage is blocked', function (): void {
    // Arrange
    $this->actingAs(administrator());
    $page = visit(route('dashboard'));
    $page->resize(1440, 900);
    $page->page()->context()->addInitScript(<<<'JS'
        for (const method of ['getItem', 'setItem', 'removeItem']) {
            Storage.prototype[method] = () => {
                throw new DOMException('Storage is disabled', 'SecurityError');
            };
        }
        JS);

    // Act
    $page->navigate(route('dashboard'))
        ->click('button[aria-label="Collapse sidebar"]');

    // Assert
    $page->assertScript('window.Livewire !== undefined && window.Alpine !== undefined')
        ->assertAttribute('button[aria-label="Expand sidebar"]', 'aria-expanded', 'false')
        ->assertNoJavascriptErrors();
});
