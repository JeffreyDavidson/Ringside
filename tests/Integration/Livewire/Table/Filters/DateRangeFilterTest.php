<?php

declare(strict_types=1);

use App\Livewire\Table\Filters\DateRangeFilter;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;

describe('date range filter application', function (): void {
    test('incomplete ranges leave the query unchanged', function (mixed $range): void {
        // Arrange
        $user = User::factory()->create();
        $query = (new User())->newQuery();
        $filter = DateRangeFilter::make('Created At')->filter(
            fn (): never => throw new LogicException('Incomplete ranges must not invoke the callback.'),
        );

        // Act
        $filter->apply($query, $range);
        $users = $query->get();

        // Assert
        expect($users->modelKeys())->toBe([$user->id]);
    })->with([
        'null' => [null],
        'string' => ['2026-08-01'],
        'empty array' => [[]],
        'missing minimum' => [['maxDate' => '2026-08-31']],
        'missing maximum' => [['minDate' => '2026-08-01']],
        'empty minimum' => [['minDate' => '', 'maxDate' => '2026-08-31']],
        'empty maximum' => [['minDate' => '2026-08-01', 'maxDate' => '']],
    ]);

    test('complete ranges pass both bounds to the query callback', function (): void {
        // Arrange
        $users = User::factory()->count(4)->sequence(
            ['created_at' => '2026-07-31 23:59:59'],
            ['created_at' => '2026-08-01 00:00:00'],
            ['created_at' => '2026-08-31 23:59:59'],
            ['created_at' => '2026-09-01 00:00:00'],
        )->create();
        $query = (new User())->newQuery();
        $filter = DateRangeFilter::make('Created At')->filter(
            /**
             * @param  Builder<User>  $query
             * @param  array{minDate: string, maxDate: string}  $range
             */
            function (Builder $query, array $range): void {
                $query->whereBetween('created_at', [$range['minDate'], $range['maxDate']]);
            },
        );

        // Act
        $filter->apply($query, [
            'minDate' => '2026-08-01 00:00:00',
            'maxDate' => '2026-08-31 23:59:59',
        ]);
        $matches = $query->orderBy('id')->get();

        // Assert
        expect($matches->modelKeys())->toBe($users->slice(1, 2)->values()->modelKeys());
    });

    test('complete ranges leave queries unchanged without a callback', function (): void {
        // Arrange
        $user = User::factory()->create();
        $query = (new User())->newQuery();
        $filter = DateRangeFilter::make('Created At');

        // Act
        $filter->apply($query, ['minDate' => '2026-08-01', 'maxDate' => '2026-08-31']);
        $users = $query->get();

        // Assert
        expect($users->modelKeys())->toBe([$user->id]);
    });
});
