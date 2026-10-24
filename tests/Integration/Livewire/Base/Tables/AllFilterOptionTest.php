<?php

declare(strict_types=1);

use App\Livewire\Events\Tables\Main as EventsMain;
use App\Livewire\Stables\Tables\Main as StablesMain;
use App\Livewire\Table\DataTableComponent;
use App\Livewire\Table\Filters\SelectFilter;
use App\Livewire\Titles\Tables\Main as TitlesMain;
use Illuminate\Support\Facades\Lang;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

dataset('tables with an all option', [
    'stables status' => [StablesMain::class, 'status'],
    'titles status' => [TitlesMain::class, 'status'],
    'titles type' => [TitlesMain::class, 'type'],
    'events venue' => [EventsMain::class, 'venue'],
]);

it('translates the All option of a table filter', function (string $component, string $filterKey): void {
    // Arrange
    actingAs(administrator());
    Lang::addLines(['core.all' => 'Todos'], 'en');

    $table = livewire($component)->instance();
    $filters = $table instanceof DataTableComponent ? $table->filters() : [];

    // Act
    $options = collect($filters)
        ->filter(fn (mixed $filter): bool => $filter instanceof SelectFilter && $filter->getKey() === $filterKey)
        ->map(fn (SelectFilter $filter): array => $filter->getOptions())
        ->first();

    // Assert
    expect($options)->toHaveKey('', 'Todos');
})->with('tables with an all option');
