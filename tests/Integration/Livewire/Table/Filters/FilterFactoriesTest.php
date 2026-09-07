<?php

declare(strict_types=1);

use App\Livewire\Components\Tables\Filters\FirstActivityPeriodFilter;
use App\Livewire\Components\Tables\Filters\FirstEmploymentFilter;
use App\Livewire\Components\Tables\Filters\RelatedPeriodDateRangeFilter;
use App\Models\Lifecycle\Employment;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Date;

describe('table filter factories', function (): void {
    test('related period filter factories preserve their requested types', function (RelatedPeriodDateRangeFilter $filter, string $filterClass, string $key) {
        // Act
        $configuredFilter = $filter->setFields('periods', 'periods.started_at', 'periods.ended_at');

        // Assert
        expect($filter)
            ->toBeInstanceOf(RelatedPeriodDateRangeFilter::class)
            ->and($filter::class)->toBe($filterClass)
            ->and($filter->getKey())->toBe($key)
            ->and($configuredFilter)->toBe($filter);
    })->with([
        [FirstActivityPeriodFilter::make('Activity Period'), FirstActivityPeriodFilter::class, 'activity_period'],
        [FirstEmploymentFilter::make('Employment Period'), FirstEmploymentFilter::class, 'employment_period'],
    ]);

    test('related period filters include every period overlapping the selected range without loading history collections', function () {
        // Arrange
        $startsWithinRange = Wrestler::factory()
            ->has(Employment::factory()->started(Date::parse('2024-06-15')), 'employments')
            ->create();
        $endsWithinRange = Wrestler::factory()
            ->has(
                Employment::factory()
                    ->started(Date::parse('2024-05-15'))
                    ->ended(Date::parse('2024-06-15')),
                'employments',
            )
            ->create();
        $spansRange = Wrestler::factory()
            ->has(
                Employment::factory()
                    ->started(Date::parse('2024-05-15'))
                    ->ended(Date::parse('2024-07-15')),
                'employments',
            )
            ->create();
        $continuesThroughRange = Wrestler::factory()
            ->has(Employment::factory()->started(Date::parse('2024-05-15'))->current(), 'employments')
            ->create();
        $endsBeforeRange = Wrestler::factory()
            ->has(
                Employment::factory()
                    ->started(Date::parse('2024-05-01'))
                    ->ended(Date::parse('2024-05-31 23:59:59')),
                'employments',
            )
            ->create();
        $startsAfterRange = Wrestler::factory()
            ->has(Employment::factory()->started(Date::parse('2024-07-01'))->current(), 'employments')
            ->create();

        $filter = FirstEmploymentFilter::make('Employment Period')
            ->setFields('employments', 'employments.started_at', 'employments.ended_at');
        $query = Wrestler::query();

        // Act
        $filter->apply($query, [
            'minDate' => '2024-06-01',
            'maxDate' => '2024-06-30',
        ]);

        $wrestlers = $query
            ->orderBy('id')
            ->get();

        // Assert
        expect($wrestlers->modelKeys())->toBe([
            $startsWithinRange->id,
            $endsWithinRange->id,
            $spansRange->id,
            $continuesThroughRange->id,
        ])->not->toContain($endsBeforeRange->id, $startsAfterRange->id)
            ->and($wrestlers->every(fn (Wrestler $wrestler): bool => ! $wrestler->relationLoaded('employments')))->toBeTrue();
    });

});
