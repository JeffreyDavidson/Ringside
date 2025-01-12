<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Columns;

use Rappasoft\LaravelLivewireTables\Views\Column;

trait HasFirstActivationDateColumn
{
    protected function getDefaultFirstActivationDateColumn(): Column
    {
        return Column::make(__('activations.started_at'), 'start_date')
            ->label(fn ($row, Column $column) => $row->firstActivation?->started_at->format('Y-m-d') ?? 'TBD');
    }
}
