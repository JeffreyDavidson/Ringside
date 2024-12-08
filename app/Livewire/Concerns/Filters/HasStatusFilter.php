<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Filters;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

trait HasStatusFilter
{
    /**
     * @param  array<int, mixed>  $statuses
     **/
    protected function getDefaultStatusFilter(array $statuses): Filter
    {
        return SelectFilter::make('Status', 'status')
            ->options(['' => 'All'] + $statuses)
            ->filter(function (Builder $builder, string $value) {
                $builder->where('status', $value);
            });
    }
}
