<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Columns;

use Rappasoft\LaravelLivewireTables\Views\Column;

trait HasStatusColumn
{
    protected function getDefaultStatusColumn(): Column
    {
        return Column::make(__('core.status'), 'status')
            ->view('components.tables.columns.status-column');
    }
}
