<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Columns;

use App\Livewire\Table\Column;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use LogicException;

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
            ->label(function (Model $row, Column $column): Factory|View {
                $rowId = $row->getKey();

                if (! is_int($rowId) && ! is_string($rowId)) {
                    throw new LogicException('Table actions require a persisted model identifier.');
                }

                return view('components.tables.columns.action-column', [
                    'path' => $this->routeBasePath,
                    'rowId' => $rowId,
                    'resourceName' => $this->resourceName,
                ]);
            })
            ->html()
            ->excludeFromColumnSelect();
    }
}
