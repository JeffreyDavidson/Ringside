<?php

declare(strict_types=1);

use App\Livewire\Components\Tables\Filters\FirstActivityPeriodFilter;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Components\Tables\Filters\RelatedPeriodDateRangeFilter;
use App\Livewire\Table\Filters\SelectFilter;

test('related period filter factories preserve their requested types', function (RelatedPeriodDateRangeFilter $filter, string $filterClass, string $key) {
    expect($filter)
        ->toBeInstanceOf(RelatedPeriodDateRangeFilter::class)
        ->and($filter::class)->toBe($filterClass)
        ->and($filter->getKey())->toBe($key)
        ->and($filter->setFields('periods', 'periods.started_at', 'periods.ended_at'))->toBe($filter);
})->with([
    [FirstActivityPeriodFilter::make('Activity Period'), FirstActivityPeriodFilter::class, 'activity_period'],
    [FirstEmploymentFilter::make('Employment Period'), FirstEmploymentFilter::class, 'employment_period'],
]);

test('select filter factory creates a configured filter', function () {
    $filter = SelectFilter::make('Status')->options([
        'active' => 'Active',
    ]);

    expect($filter->getKey())->toBe('status')
        ->and($filter->getOptions())->toBe([
            'active' => 'Active',
        ]);
});
