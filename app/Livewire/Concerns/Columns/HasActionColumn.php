<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Columns;

use App\Livewire\Table\Column;
use Illuminate\Contracts\View\Factory;

/**
 * Provides action column functionality for Livewire table components.
 *
 * This trait adds the ability to include an action column in tables with
 * view, edit, and delete links based on user permissions.
 */
trait HasActionColumn
{
    /**
     * Get the default action column for the table.
     */
    protected function getDefaultActionColumn(): Column
    {
        return Column::make(__('core.actions'))
            ->label(fn ($row, Column $column): Factory|\Illuminate\Contracts\View\View => view('components.tables.columns.action-column', [
                'path' => $this->routeBasePath,
                'rowId' => $row->id,
            ]))
            ->html()
            ->excludeFromColumnSelect();
    }
}
