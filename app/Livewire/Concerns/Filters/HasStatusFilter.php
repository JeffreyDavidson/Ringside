<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Filters;

use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

/** @phpstan-ignore-next-line trait.unused */
trait HasStatusFilter
{
    /**
     * @param  array<string, string>  $statuses
     **/
    protected function getDefaultStatusFilter(array $statuses): Filter
    {
        return SelectFilter::make('Status', 'status')
            ->options(['' => 'All'] + $statuses)
            ->filter(function (Builder $builder, string $value): void {
                $builder->where('status', $value);
            });
    }
}
