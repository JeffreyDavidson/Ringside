<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Columns;

use App\Livewire\Table\Column;
use App\Support\ModelKey;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

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
        return [
            'path' => $this->routeBasePath,
            'rowId' => ModelKey::of($row),
            'resourceName' => $this->resourceName,
            'canDelete' => method_exists($this, 'delete') && Gate::allows('delete', $row),
        ];
    }
}
