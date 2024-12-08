<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Filters;

use Rappasoft\LaravelLivewireTables\Views\Filter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;

trait HasFirstActivationDateFilter
{
    protected function getDefaultFirstActivationmDateFilter(): Filter
    {
        return DateRangeFilter::make('Activation Date')
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
