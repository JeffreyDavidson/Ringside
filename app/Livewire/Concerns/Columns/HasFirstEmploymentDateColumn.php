<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Columns;

use Rappasoft\LaravelLivewireTables\Views\Column;

trait HasFirstEmploymentDateColumn
{
    protected function getDefaultFirstEmploymentDateColumn(): Column
    {
        return Column::make(__('employments.started_at'), 'start_date')
            ->label(fn ($row, Column $column) => $row->firstEmployment?->started_at->format('Y-m-d') ?? 'TBD');
    }
}
