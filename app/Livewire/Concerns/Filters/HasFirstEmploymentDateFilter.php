<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Filters;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;

trait HasFirstEmploymentDateFilter
{
    protected function getDefaultFirstEmploymentDateFilter(): Filter
    {
        return DateRangeFilter::make('Employment Date')
            ->config([
                'allowInput' => true,
                'altFormat' => 'F j, Y',
                'ariaDateFormat' => 'F j, Y',
                'dateFormat' => 'Y-m-d',
                'placeholder' => 'Enter Date Range',
                'locale' => 'en',
            ])
            ->setFilterPillValues([0 => 'minDate', 1 => 'maxDate'])
            ->filter(function (Builder $query, array $dateRange) {
                $query
                    ->whereDate('wrestler_employments.started_at', '>=', $dateRange['minDate'])
                    ->whereDate('wrestler_employments.ended_at', '<=', $dateRange['maxDate']);
            });
    }
}
