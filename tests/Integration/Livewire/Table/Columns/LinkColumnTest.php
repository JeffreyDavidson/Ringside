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
