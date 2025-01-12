<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
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
                $query->withWhereHas('employments', function ($query) use ($dateRange) {
                    $query
                        ->where(function (Builder $query) use ($dateRange) {
                            $query->whereBetween('wrestlers_employments.started_at', [Carbon::createFromFormat('Y-m-d', $dateRange['minDate'])->startOfDay(), Carbon::createFromFormat('Y-m-d', $dateRange['maxDate'])->endOfDay()]);
                        })
                        ->orWhere(function (Builder $query) use ($dateRange) {
                            $query->whereBetween('wrestlers_employments.ended_at', [Carbon::createFromFormat('Y-m-d', $dateRange['minDate'])->startOfDay(), Carbon::createFromFormat('Y-m-d', $dateRange['maxDate'])->endOfDay()]);
                        });
                });
            });
    }
}
