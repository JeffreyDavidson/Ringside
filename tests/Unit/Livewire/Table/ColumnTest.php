<?php

declare(strict_types=1);

use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\ArrayColumn;
use App\Livewire\Table\Columns\LinkColumn;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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

test('array columns resolve and format items with focused callbacks', function () {
    $row = collect(['first', 'second']);

    $column = ArrayColumn::make('Items')
        ->data(fn (Collection $row): Collection => $row)
        ->outputFormat(fn (string $item): string => Str::upper($item))
        ->separator(' | ');

    expect($column->resolveValue($row))->toBe('FIRST | SECOND');
});
