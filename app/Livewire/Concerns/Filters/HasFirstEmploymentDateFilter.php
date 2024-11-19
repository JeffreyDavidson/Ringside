<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Filters;

use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;

trait HasFirstEmploymentDateFilter
{
    protected function getDefaultFirstEmploymentDateFilter(): DateRangeFilter
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
            ->setFilterPillValues([0 => 'minDate', 1 => 'maxDate']);
    }
}
