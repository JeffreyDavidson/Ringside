<?php

declare(strict_types=1);

use App\Livewire\Table\Columns\ArrayColumn;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

describe('array column values', function (): void {
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
});
