<?php

declare(strict_types=1);

use App\Livewire\Table\Columns\ArrayColumn;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

describe('array column values', function (): void {
    test('uses the placeholder when no data callback is configured', function (): void {
        // Arrange
        $column = ArrayColumn::make('Items')->emptyValue('None assigned');

        // Act
        $value = $column->resolveValue(null);

        // Assert
        expect($value)->toBe('None assigned');
    });

    test('uses the placeholder for an empty collection without invoking item callbacks', function (): void {
        // Arrange
        $column = ArrayColumn::make('Items')
            ->data(fn (Collection $row): Collection => $row)
            ->outputFormat(fn (): never => throw new LogicException('Empty columns must not format items.'))
            ->emptyValue('None assigned');

        // Act
        $value = $column->resolveValue(collect());

        // Assert
        expect($value)->toBe('None assigned');
    });

    test('joins unformatted items with the configured separator', function (string $separator, string $expected): void {
        // Arrange
        $column = ArrayColumn::make('Items')
            ->data(fn (Collection $row): Collection => $row)
            ->separator($separator);

        // Act
        $value = $column->resolveValue(collect(['Alpha', 'Bravo']));

        // Assert
        expect($value)->toBe($expected);
    })->with([
        'comma separated' => [', ', 'Alpha, Bravo'],
        'custom delimiter' => [' | ', 'Alpha | Bravo'],
    ]);

    test('array columns resolve and format items with focused callbacks', function (): void {
        // Arrange
        $row = collect(['first', 'second']);
        $column = ArrayColumn::make('Items')
            ->data(fn (Collection $row): Collection => $row)
            ->outputFormat(fn (string $item): string => Str::upper($item))
            ->separator(' | ');

        // Act
        $value = $column->resolveValue($row);

        // Assert
        expect($value)->toBe('FIRST | SECOND');
    });

    test('array columns render escaped links from title and location callbacks', function (): void {
        // Arrange
        $row = collect(['<script>alert(1)</script>']);
        $column = ArrayColumn::make('Items')
            ->data(fn (Collection $row): Collection => $row)
            ->link(
                title: fn (string $item): string => $item,
                location: fn (string $item): string => '/items/'.rawurlencode($item),
            );

        // Act
        $value = $column->resolveValue($row);

        // Assert
        expect($value)
            ->toBe('<a href="/items/%3Cscript%3Ealert%281%29%3C%2Fscript%3E">&lt;script&gt;alert(1)&lt;/script&gt;</a>');
    });

    test('rejects non-string link callback results', function (mixed $title, mixed $location): void {
        // Arrange
        $column = ArrayColumn::make('Items')
            ->data(fn (Collection $row): Collection => $row)
            ->link(
                title: fn (): mixed => $title,
                location: fn (): mixed => $location,
            );

        // Act / Assert
        expect(fn () => $column->resolveValue(collect(['item'])))
            ->toThrow(LogicException::class, 'Array column link callbacks must return strings.');
    })->with([
        'invalid title' => [42, '/items/1'],
        'invalid location' => ['Item', null],
        'both invalid' => [[], false],
    ]);
});
