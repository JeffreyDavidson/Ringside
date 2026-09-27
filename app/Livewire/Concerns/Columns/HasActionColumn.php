<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Columns;

use App\Livewire\Table\Column;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
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
            ->label(fn (Model $row, Column $column): Factory|View => view(
                'components.tables.columns.action-column',
                $this->getActionColumnViewData($row),
            ))
            ->html()
            ->excludeFromColumnSelect();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getActionColumnViewData(Model $row): array
    {
        $rowId = $row->getKey();

        if (! is_int($rowId) && ! is_string($rowId)) {
            throw new LogicException('Table actions require a persisted model identifier.');
        }

        return [
            'path' => $this->routeBasePath,
            'rowId' => $rowId,
            'resourceName' => $this->resourceName,
            'canDelete' => method_exists($this, 'delete') && Gate::allows('delete', $row),
        ];
    }
}
