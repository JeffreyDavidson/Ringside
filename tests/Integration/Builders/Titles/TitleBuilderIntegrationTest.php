<?php

declare(strict_types=1);

use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Titles\Title;
use Illuminate\Support\Facades\Date;

describe('TitleBuilder Query Scopes', function () {
    describe('activity state scopes', function () {
        test('future activated titles can be retrieved', function () {
            Title::factory()->undebuted()->create(['name' => 'Undebuted Title']);
            Title::factory()->active()->create(['name' => 'Active Title']);
            Title::factory()->inactive()->create(['name' => 'Inactive Title']);
            Title::factory()->retired()->create(['name' => 'Retired Title']);
            Title::factory()->withFutureDebut()->create(['name' => 'Future Debut Title']);

            $futureActivatedTitle = Title::factory()->withFutureActivation()->create();

            $futureActivatedTitles = Title::query()->withPendingDebut()->get();

            expect($futureActivatedTitles->pluck('id'))->toContain($futureActivatedTitle->id);
        });

    });

    describe('basic activity scopes', function () {
        test('undebuted scope returns titles without activity periods', function () {
            $undebutedTitle = Title::factory()->undebuted()->create(['name' => 'Undebuted Title']);
            $activeTitle = Title::factory()->active()->create(['name' => 'Active Title']);
            $inactiveTitle = Title::factory()->inactive()->create(['name' => 'Inactive Title']);
            Title::factory()->retired()->create(['name' => 'Retired Title']);
            $futureDebutTitle = Title::factory()->withFutureDebut()->create(['name' => 'Future Debut Title']);

            $undebutedTitles = Title::query()->undebuted()->get();

            expect($undebutedTitles->pluck('id'))->toContain($undebutedTitle->id)
                ->and($undebutedTitles->pluck('id'))->not->toContain($activeTitle->id)
                ->and($undebutedTitles->pluck('id'))->not->toContain($inactiveTitle->id)
                ->and($undebutedTitles->pluck('id'))->not->toContain($futureDebutTitle->id);
        });

        test('active scope returns titles with current activity periods', function () {
            $undebutedTitle = Title::factory()->undebuted()->create(['name' => 'Undebuted Title']);
            $activeTitle = Title::factory()->active()->create(['name' => 'Active Title']);
            $inactiveTitle = Title::factory()->inactive()->create(['name' => 'Inactive Title']);
            Title::factory()->retired()->create(['name' => 'Retired Title']);
            Title::factory()->withFutureDebut()->create(['name' => 'Future Debut Title']);

            $activeTitles = Title::query()->active()->get();

            expect($activeTitles->pluck('id'))->toContain($activeTitle->id)
                ->and($activeTitles->pluck('id'))->not->toContain($undebutedTitle->id)
                ->and($activeTitles->pluck('id'))->not->toContain($inactiveTitle->id);
        });

        test('inactive scope returns titles with past but no current activity', function () {
            $undebutedTitle = Title::factory()->undebuted()->create(['name' => 'Undebuted Title']);
            $activeTitle = Title::factory()->active()->create(['name' => 'Active Title']);
            $inactiveTitle = Title::factory()->inactive()->create(['name' => 'Inactive Title']);
            Title::factory()->retired()->create(['name' => 'Retired Title']);
            Title::factory()->withFutureDebut()->create(['name' => 'Future Debut Title']);

            $inactiveTitles = Title::query()->inactive()->get();

            expect($inactiveTitles->pluck('id'))->toContain($inactiveTitle->id)
                ->and($inactiveTitles->pluck('id'))->not->toContain($activeTitle->id)
                ->and($inactiveTitles->pluck('id'))->not->toContain($undebutedTitle->id);
        });

        test('withPendingDebut scope returns titles with future activity', function () {
            $undebutedTitle = Title::factory()->undebuted()->create(['name' => 'Undebuted Title']);
            $activeTitle = Title::factory()->active()->create(['name' => 'Active Title']);
            Title::factory()->inactive()->create(['name' => 'Inactive Title']);
            Title::factory()->retired()->create(['name' => 'Retired Title']);
            $futureDebutTitle = Title::factory()->withFutureDebut()->create(['name' => 'Future Debut Title']);

            $pendingTitles = Title::query()->withPendingDebut()->get();

            expect($pendingTitles->pluck('id'))->toContain($futureDebutTitle->id)
                ->and($pendingTitles->pluck('id'))->not->toContain($activeTitle->id)
                ->and($pendingTitles->pluck('id'))->not->toContain($undebutedTitle->id);
        });
    });

    describe('scope method chaining', function () {
        test('can chain scopes with additional filters', function () {
            Title::factory()->undebuted()->create(['name' => 'Undebuted Title']);
            $activeTitle = Title::factory()->active()->create(['name' => 'Active Title']);
            Title::factory()->inactive()->create(['name' => 'Inactive Title']);
            Title::factory()->retired()->create(['name' => 'Retired Title']);
            Title::factory()->withFutureDebut()->create(['name' => 'Future Debut Title']);

            $filteredTitles = Title::query()
                ->active()
                ->where('name', 'like', '%Active%')
                ->get();

            expect($filteredTitles->pluck('id'))->toContain($activeTitle->id);
        });
    });

    describe('activity history filtering', function () {
        test('undebuted scope excludes deleted titles and every title with activity history', function () {
            $undebutedTitle = Title::factory()->undebuted()->create(['name' => 'Undebuted Title']);
            Title::factory()->active()->create(['name' => 'Active Title']);
            Title::factory()->inactive()->create(['name' => 'Inactive Title']);
            Title::factory()->retired()->create(['name' => 'Retired Title']);
            Title::factory()->withFutureDebut()->create(['name' => 'Future Debut Title']);

            // Arrange
            Title::factory()->undebuted()->trashed()->create();

            // Act
            $query = Title::query();
            $query->undebuted();
            $titles = $query->get();

            // Assert
            expect($titles->modelKeys())->toBe([$undebutedTitle->id]);
        });

        test('active scope returns a reactivated title once despite previous activity periods', function () {
            Title::factory()->undebuted()->create(['name' => 'Undebuted Title']);
            $activeTitle = Title::factory()->active()->create(['name' => 'Active Title']);
            Title::factory()->inactive()->create(['name' => 'Inactive Title']);
            Title::factory()->retired()->create(['name' => 'Retired Title']);
            Title::factory()->withFutureDebut()->create(['name' => 'Future Debut Title']);

            // Arrange
            ActivityPeriod::factory()
                ->for($activeTitle, 'activeable')
                ->started(Date::now()->subMonths(2))
                ->ended(Date::now()->subMonth())
                ->create();

            // Act
            $query = Title::query();
            $query->active();
            $titles = $query->get();

            // Assert
            expect($titles->modelKeys())->toBe([$activeTitle->id]);
        });

    });

    describe('scope edge cases', function () {
        test('scopes work with soft deleted titles', function () {
            Title::factory()->undebuted()->create(['name' => 'Undebuted Title']);
            Title::factory()->active()->create(['name' => 'Active Title']);
            Title::factory()->inactive()->create(['name' => 'Inactive Title']);
            Title::factory()->retired()->create(['name' => 'Retired Title']);
            Title::factory()->withFutureDebut()->create(['name' => 'Future Debut Title']);

            $deletedTitle = Title::factory()->active()->create();
            $deletedTitle->delete();

            $activeTitles = Title::query()->active()->get();
            expect($activeTitles->pluck('id'))->not->toContain($deletedTitle->id);

            $trashedActiveTitles = Title::onlyTrashed()->active()->get();
            expect($trashedActiveTitles->pluck('id'))->toContain($deletedTitle->id);
        });

        test('scopes handle empty database gracefully', function () {
            Title::factory()->undebuted()->create(['name' => 'Undebuted Title']);
            Title::factory()->active()->create(['name' => 'Active Title']);
            Title::factory()->inactive()->create(['name' => 'Inactive Title']);
            Title::factory()->retired()->create(['name' => 'Retired Title']);
            Title::factory()->withFutureDebut()->create(['name' => 'Future Debut Title']);

            Title::query()->delete();

            expect(Title::query()->active()->count())->toBe(0)
                ->and(Title::query()->undebuted()->count())->toBe(0);
        });
    });

    describe('scope return types and fluency', function () {
        test('all scopes return static for proper chaining', function () {
            Title::factory()->undebuted()->create(['name' => 'Undebuted Title']);
            Title::factory()->active()->create(['name' => 'Active Title']);
            Title::factory()->inactive()->create(['name' => 'Inactive Title']);
            Title::factory()->retired()->create(['name' => 'Retired Title']);
            Title::factory()->withFutureDebut()->create(['name' => 'Future Debut Title']);

            $builder = Title::query();

            expect($builder->undebuted())->toBeInstanceOf($builder::class)
                ->and($builder->active())->toBeInstanceOf($builder::class)
                ->and($builder->inactive())->toBeInstanceOf($builder::class)
                ->and($builder->withPendingDebut())->toBeInstanceOf($builder::class);
        });

        test('scopes maintain query builder functionality', function () {
            Title::factory()->undebuted()->create(['name' => 'Undebuted Title']);
            $activeTitle = Title::factory()->active()->create(['name' => 'Active Title']);
            Title::factory()->inactive()->create(['name' => 'Inactive Title']);
            Title::factory()->retired()->create(['name' => 'Retired Title']);
            Title::factory()->withFutureDebut()->create(['name' => 'Future Debut Title']);

            // Arrange
            Title::factory()->singles()->active()->create(['name' => 'Zonal Title']);
            $nationalTitle = Title::factory()->singles()->active()->create(['name' => 'National Title']);
            Title::factory()->singles()->active()->trashed()->create(['name' => 'A Deleted Title']);

            // Act
            $query = Title::query();
            $query->active();
            $query->select(['id', 'name']);
            $query->orderBy('name');
            $query->limit(2);
            $titles = $query->get();

            // Assert
            expect($titles->modelKeys())->toBe([$activeTitle->id, $nationalTitle->id])
                ->and($titles->pluck('name')->all())->toBe(['Active Title', 'National Title'])
                ->and($titles->firstOrFail()->getAttributes())->toHaveKeys(['id', 'name'])
                ->toHaveCount(2);
        });
    });
});
