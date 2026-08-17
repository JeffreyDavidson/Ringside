<?php

declare(strict_types=1);

use App\Livewire\Components\Tables\Filters\FirstActivityPeriodFilter;
use App\Livewire\Table\Filters\SelectFilter;

test('date range filter factory preserves the requested filter type', function () {
    $filter = FirstActivityPeriodFilter::make('Activity Period');

    expect($filter)
        ->toBeInstanceOf(FirstActivityPeriodFilter::class)
        ->and($filter->getKey())->toBe('activity_period');
});

test('select filter factory creates a configured filter', function () {
    $filter = SelectFilter::make('Status')->options([
        'active' => 'Active',
    ]);

    expect($filter->getKey())->toBe('status')
        ->and($filter->getOptions())->toBe([
            'active' => 'Active',
        ]);
});
