<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Columns;

use Rappasoft\LaravelLivewireTables\Views\Column;

trait HasActionColumn
{
    protected function getDefaultActionColumn(): Column
    {
        return Column::make(__('core.actions'))
            ->setColumnLabelStatusDisabled()
            ->label(
                fn ($row, Column $column) => view('components.tables.columns.action-column')->with(
                    [
                        'rowId' => $row->id,
                        'path' => $this->routeBasePath,
                        'links' => $this->actionLinksToDisplay,
                    ]
                )
            )->html();
    }
}
