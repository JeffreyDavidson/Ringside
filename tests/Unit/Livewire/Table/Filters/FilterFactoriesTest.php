<?php

declare(strict_types=1);

use App\Livewire\Components\Tables\Filters\FirstActivityPeriodFilter;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Components\Tables\Filters\RelatedPeriodDateRangeFilter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Lifecycle\Employment;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Date;

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

test('related period filters constrain parents without loading history collections', function () {
    $matchingWrestler = Wrestler::factory()
        ->has(Employment::factory()->started(Date::parse('2024-06-15')), 'employments')
        ->create();
    Wrestler::factory()
        ->has(Employment::factory()->started(Date::parse('2024-05-15')), 'employments')
        ->create();

    $filter = FirstEmploymentFilter::make('Employment Period')
        ->setFields('employments', 'employments.started_at', 'employments.ended_at');
    $query = Wrestler::query();

    $filter->apply($query, [
        'minDate' => '2024-06-01',
        'maxDate' => '2024-06-30',
    ]);

    $wrestlers = $query->get();

    expect($wrestlers->modelKeys())->toBe([$matchingWrestler->id])
        ->and($wrestlers->firstOrFail()->relationLoaded('employments'))->toBeFalse();
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
