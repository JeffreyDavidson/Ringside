<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Roster\Referees\Referee;
use App\Models\Users\User;

const LOWEST_TABLE_FIELD_BORDER_CONTRAST = <<<'JS'
() => {
    const channels = (color) => color.match(/[\d.]+/g).slice(0, 3).map(Number);
    const luminance = (color) => {
        const [red, green, blue] = channels(color).map((value) => {
            const channel = value / 255;

            return channel <= 0.03928 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4;
        });

        return 0.2126 * red + 0.7152 * green + 0.0722 * blue;
    };
    const backgroundBehind = (element) => {
        for (let node = element.parentElement; node; node = node.parentElement) {
            const background = getComputedStyle(node).backgroundColor;

            if (! background.startsWith('rgba') || ! background.endsWith(', 0)')) {
                return background;
            }
        }

        return getComputedStyle(document.body).backgroundColor;
    };
    const fields = [
        ...[...document.querySelectorAll('main [role=searchbox]')].map((input) => input.parentElement),
        ...document.querySelectorAll('main select, main input[type=date]'),
    ].filter((field) => field.checkVisibility());

    if (fields.length === 0) {
        return 0;
    }

    return Math.min(...fields.map((field) => {
        const [lighter, darker] = [luminance(getComputedStyle(field).borderTopColor), luminance(backgroundBehind(field))].sort((a, b) => b - a);

        return (lighter + 0.05) / (darker + 0.05);
    }));
}
JS;

test('table search, filter and paging fields have borders with at least 3:1 contrast', function (string $route, Closure $arrange, ?string $filtersToggle): void {
    // Arrange
    $arrange();
    $this->actingAs(administrator());
    $page = visit(route($route));
    $page->resize(390, 844)
        ->waitForText(__('core.rows_per_page'));

    // Act
    if ($filtersToggle !== null) {
        $page->click($filtersToggle);
    }

    // Assert
    expect($page->script(LOWEST_TABLE_FIELD_BORDER_CONTRAST))->toBeGreaterThanOrEqual(3);
})->with([
    'referees' => ['referees.index', fn () => Referee::factory()->employed()->create(), 'Filters'],
    'events' => ['events.index', fn () => Event::factory()->create(), null],
    'users' => ['users.index', fn () => User::factory()->create(), null],
]);
