<?php

declare(strict_types=1);

use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\LinkColumn;

test('make preserves the requested column type', function () {
    $column = LinkColumn::make('Wrestler');

    expect($column)
        ->toBeInstanceOf(LinkColumn::class)
        ->and($column->getField())->toBe('wrestler');
});

test('view columns render their configured view', function () {
    $column = Column::make('Divider')->view('components.auth.form-divider');

    expect($column->resolveValue(null))
        ->toContain('items-center')
        ->toContain('Or');
});
