<?php

declare(strict_types=1);

use App\Livewire\Table\Columns\LinkColumn;

describe('link column construction', function (): void {
    test('make preserves the requested column type', function (): void {
        // Act
        $column = LinkColumn::make('Wrestler');

        // Assert
        expect($column)
            ->toBeInstanceOf(LinkColumn::class)
            ->and($column->getField())->toBe('wrestler');
    });
});

describe('link column rendering', function (): void {
    test('escapes titles when the location is unavailable', function (?string $location): void {
        // Arrange
        $column = LinkColumn::make('Name')
            ->title(fn (string $row): string => $row)
            ->location(fn (): ?string => $location);

        // Act
        $value = $column->resolveValue('<script>alert(1)</script> & Name');

        // Assert
        expect($value)->toBe('&lt;script&gt;alert(1)&lt;/script&gt; &amp; Name');
    })->with([
        'null location' => [null],
        'empty location' => [''],
    ]);

    test('escapes titles when no location callback is configured', function (): void {
        // Arrange
        $column = LinkColumn::make('Name')->title(fn (): string => '<b>Name</b>');

        // Act
        $value = $column->resolveValue(null);

        // Assert
        expect($value)->toBe('&lt;b&gt;Name&lt;/b&gt;');
    });

    test('escapes linked titles and location attributes', function (): void {
        // Arrange
        $column = LinkColumn::make('Name')
            ->title(fn (string $row): string => $row)
            ->location(fn (): string => '/items?name="quoted"&page=1');

        // Act
        $value = $column->resolveValue('<b>Name</b>');

        // Assert
        expect($value)->toBe('<a href="/items?name=&quot;quoted&quot;&amp;page=1">&lt;b&gt;Name&lt;/b&gt;</a>');
    });
});
