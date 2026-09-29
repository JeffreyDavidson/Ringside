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

    test('escapes plain items that have no output format', function (): void {
        // Arrange
        $column = ArrayColumn::make('Items')
            ->data(fn (Collection $row): Collection => $row)
            ->separator(' | ');

        // Act
        $value = $column->resolveValue(collect(['<script>alert(1)</script>', 'Tom & Jerry']));

        // Assert
        expect($value)->toBe('&lt;script&gt;alert(1)&lt;/script&gt; | Tom &amp; Jerry');
    });

    test('escapes the empty value', function (bool $withData): void {
        // Arrange
        $column = ArrayColumn::make('Items')
            ->emptyValue('<script>alert(1)</script>');

        if ($withData) {
            $column->data(fn (Collection $row): Collection => $row);
        }

        // Act
        $value = $column->resolveValue(collect());

        // Assert
        expect($value)->toBe('&lt;script&gt;alert(1)&lt;/script&gt;');
    })->with([
        'without a data callback' => [false],
        'with an empty collection' => [true],
    ]);

    test('leaves output format callbacks responsible for their own html', function (): void {
        // Arrange
        $column = ArrayColumn::make('Items')
            ->data(fn (Collection $row): Collection => $row)
            ->outputFormat(fn (string $item): string => "<strong>{$item}</strong>");

        // Act
        $value = $column->resolveValue(collect(['Alpha']));

        // Assert
        expect($value)->toBe('<strong>Alpha</strong>');
    });

    test('keeps the separator as developer supplied html', function (): void {
        // Arrange
        $column = ArrayColumn::make('Items')
            ->data(fn (Collection $row): Collection => $row)
            ->separator('<br />');

        // Act
        $value = $column->resolveValue(collect(['Alpha', 'Bravo']));

        // Assert
        expect($value)->toBe('Alpha<br />Bravo');
    });

    test('rejects plain items that cannot be rendered as text', function (): void {
        // Arrange
        $column = ArrayColumn::make('Items')
            ->data(fn (Collection $row): Collection => $row);

        // Act / Assert
        expect(fn () => $column->resolveValue(collect([['nested']])))
            ->toThrow(LogicException::class, 'Array column items without an output format must be strings.');
    });

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
