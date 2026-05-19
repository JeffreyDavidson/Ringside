<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\Titles\Title;
use App\Models\Titles\TitleActivityPeriod;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

describe('HasActivityPeriods Trait Unit Tests', function () {
    beforeEach(function () {
        // Use the real Title model which implements the trait
        $this->model = Title::factory()->create();
    });

    describe('basic relationships', function () {
        test('activityPeriods relationship returns correct type', function () {
            $model = $this->model;
            expect($model->activityPeriods())->toBeInstanceOf(HasMany::class);
        });

        test('model can have activity periods', function () {
            $model = $this->model;
            $activityPeriod = TitleActivityPeriod::factory()->create([
                'title_id' => $model->id,
                'started_at' => now()->subMonth(),
                'ended_at' => null,
            ]);
            $model->refresh();
            expect($model->activityPeriods->pluck('id'))->toContain($activityPeriod->id);
        });

        test('activations alias relationship works', function () {
            $model = $this->model;
            expect($model->activations())->toBeInstanceOf(HasMany::class);
        });
    });

    describe('current activity period', function () {
        test('model can have current activity period', function () {
            $model = $this->model;
            $current = TitleActivityPeriod::factory()->create([
                'title_id' => $model->id,
                'started_at' => now()->subWeek(),
                'ended_at' => null,
            ]);
            $model->load('currentActivityPeriod');
            expect($model->currentActivityPeriod)->not->toBeNull();
            expect($model->currentActivityPeriod->ended_at)->toBeNull();
        });

        test('model without current activity period returns null', function () {
            $model = $this->model;
            // No current period
            $model->load('currentActivityPeriod');
            expect($model->currentActivityPeriod)->toBeNull();
        });

        test('currentActivityPeriod relationship returns correct type', function () {
            $model = $this->model;
            expect($model->currentActivityPeriod())->toBeInstanceOf(HasOne::class);
        });
    });

    describe('future activity period', function () {
        test('model can have future activity period', function () {
            $model = $this->model;
            $future = TitleActivityPeriod::factory()->create([
                'title_id' => $model->id,
                'started_at' => now()->addWeek(),
                'ended_at' => null,
            ]);
            $model->load('futureActivityPeriod');
            expect($model->futureActivityPeriod)->not->toBeNull();
            expect($model->futureActivityPeriod->started_at->gt(now()))->toBeTrue();
        });

        test('model without future activity period returns null', function () {
            $model = $this->model;
            $model->load('futureActivityPeriod');
            expect($model->futureActivityPeriod)->toBeNull();
        });

        test('futureActivityPeriod relationship returns correct type', function () {
            $model = $this->model;
            expect($model->futureActivityPeriod())->toBeInstanceOf(HasOne::class);
        });
    });

    describe('previous activity periods', function () {
        test('model can have previous activity periods', function () {
            $model = $this->model;
            $previous = TitleActivityPeriod::factory()->create([
                'title_id' => $model->id,
                'started_at' => now()->subYear(),
                'ended_at' => now()->subMonth(),
            ]);
            $model->load('previousActivityPeriods');
            expect($model->previousActivityPeriods)->toHaveCount(1);
            expect($model->previousActivityPeriods->pluck('id'))->toContain($previous->id);
        });

        test('previousActivityPeriods relationship returns correct type', function () {
            $model = $this->model;
            expect($model->previousActivityPeriods())->toBeInstanceOf(HasMany::class);
        });

        test('previousActivityPeriod relationship returns correct type', function () {
            $model = $this->model;
            expect($model->previousActivityPeriod())->toBeInstanceOf(HasOne::class);
        });

        test('model can have previous activity period', function () {
            $model = $this->model;
            $previous = TitleActivityPeriod::factory()->create([
                'title_id' => $model->id,
                'started_at' => now()->subYear(),
                'ended_at' => now()->subMonth(),
            ]);
            $model->load('previousActivityPeriod');
            expect($model->previousActivityPeriod)->not->toBeNull();
            expect($model->previousActivityPeriod->ended_at)->not->toBeNull();
        });
    });

    describe('first activity period', function () {
        test('firstActivityPeriod relationship returns correct type', function () {
            $model = $this->model;
            expect($model->firstActivityPeriod())->toBeInstanceOf(HasOne::class);
        });

        test('model can have first activity period', function () {
            $model = $this->model;
            $first = TitleActivityPeriod::factory()->create([
                'title_id' => $model->id,
                'started_at' => now()->subYear(),
                'ended_at' => now()->subMonth(),
            ]);
            $second = TitleActivityPeriod::factory()->create([
                'title_id' => $model->id,
                'started_at' => now()->subWeek(),
                'ended_at' => null,
            ]);
            $model->load('firstActivityPeriod');
            expect($model->firstActivityPeriod)->not->toBeNull();
            expect($model->firstActivityPeriod->id)->toBe($first->id);
        });
    });

    describe('status checking methods', function () {
        test('hasActivityPeriods returns true when model has periods', function () {
            $model = $this->model;
            TitleActivityPeriod::factory()->create([
                'title_id' => $model->id,
                'started_at' => now()->subWeek(),
                'ended_at' => null,
            ]);
            expect($model->hasActivityPeriods())->toBeTrue();
        });

        test('hasActivityPeriods returns false when model has no periods', function () {
            $model = $this->model;
            expect($model->hasActivityPeriods())->toBeFalse();
        });

        test('isCurrentlyActive returns true when model has current period', function () {
            $model = $this->model;
            TitleActivityPeriod::factory()->create([
                'title_id' => $model->id,
                'started_at' => now()->subWeek(),
                'ended_at' => null,
            ]);
            expect($model->isCurrentlyActive())->toBeTrue();
        });

        test('isCurrentlyActive returns false when model has no current period', function () {
            $model = $this->model;
            expect($model->isCurrentlyActive())->toBeFalse();
        });

        test('hasFutureActivity returns true when model has future period', function () {
            $model = $this->model;
            TitleActivityPeriod::factory()->create([
                'title_id' => $model->id,
                'started_at' => now()->addWeek(),
                'ended_at' => null,
            ]);
            expect($model->hasFutureActivity())->toBeTrue();
        });

        test('hasFutureActivity returns false when model has no future period', function () {
            $model = $this->model;
            expect($model->hasFutureActivity())->toBeFalse();
        });

        test('isNotCurrentlyActive returns true when model is not active', function () {
            $model = Title::factory()->unactivated()->create();
            expect($model->isNotCurrentlyActive())->toBeTrue();
        });

        test('isNotCurrentlyActive returns false when model is active', function () {
            $model = $this->model;
            TitleActivityPeriod::factory()->create([
                'title_id' => $model->id,
                'started_at' => now()->subWeek(),
                'ended_at' => null,
            ]);
            expect($model->isNotCurrentlyActive())->toBeFalse();
        });

        test('isUnactivated returns true when model has no periods', function () {
            $model = Title::factory()->unactivated()->create();
            expect($model->isUnactivated())->toBeTrue();
        });

        test('isUnactivated returns false when model has periods', function () {
            $model = $this->model;
            TitleActivityPeriod::factory()->create([
                'title_id' => $model->id,
                'started_at' => now()->subWeek(),
                'ended_at' => null,
            ]);
            expect($model->isUnactivated())->toBeFalse();
        });

        test('isInactive returns true when model is not currently active', function () {
            $model = Title::factory()->unactivated()->create();
            expect($model->isInactive())->toBeTrue();
        });

        test('isInactive returns false when model is currently active', function () {
            $model = $this->model;
            TitleActivityPeriod::factory()->create([
                'title_id' => $model->id,
                'started_at' => now()->subWeek(),
                'ended_at' => null,
            ]);
            expect($model->isInactive())->toBeFalse();
        });
    });

    describe('date checking methods', function () {
        test('wasActiveOn returns true when current period started on date', function () {
            $model = Title::factory()->unactivated()->create();
            $startDate = now()->subWeek();
            TitleActivityPeriod::factory()->create([
                'title_id' => $model->id,
                'started_at' => $startDate,
                'ended_at' => null,
            ]);
            $model->refresh();
            expect($model->wasActiveOn($startDate))->toBeTrue();
        });

        test('wasActiveOn returns false when current period started on different date', function () {
            $model = $this->model;
            TitleActivityPeriod::factory()->create([
                'title_id' => $model->id,
                'started_at' => now()->subWeek(),
                'ended_at' => null,
            ]);
            expect($model->wasActiveOn(now()->subMonth()))->toBeFalse();
        });

        test('wasActiveBefore returns true when current period started before date', function () {
            $model = $this->model;
            TitleActivityPeriod::factory()->create([
                'title_id' => $model->id,
                'started_at' => now()->subMonth(),
                'ended_at' => null,
            ]);
            expect($model->wasActiveBefore(now()->subWeek()))->toBeTrue();
        });

        test('wasActiveBefore returns false when current period started after date', function () {
            $model = $this->model;
            TitleActivityPeriod::factory()->create([
                'title_id' => $model->id,
                'started_at' => now()->subWeek(),
                'ended_at' => null,
            ]);
            expect($model->wasActiveBefore(now()->subMonth()))->toBeFalse();
        });
    });

    describe('utility methods', function () {
        test('getFormattedFirstActivity returns TBD when no periods exist', function () {
            $model = $this->model;
            expect($model->getFormattedFirstActivity())->toBe('TBD');
        });

        test('getFormattedFirstActivity returns formatted date when periods exist', function () {
            $model = $this->model;
            $startDate = Carbon::parse('2024-01-15');
            TitleActivityPeriod::factory()->create([
                'title_id' => $model->id,
                'started_at' => $startDate,
                'ended_at' => null,
            ]);
            expect($model->getFormattedFirstActivity())->toBe('2024-01-15');
        });
    });

    describe('complex scenarios', function () {
        test('model with multiple activity periods handles current correctly', function () {
            $model = $this->model;
            // Past period
            TitleActivityPeriod::factory()->create([
                'title_id' => $model->id,
                'started_at' => now()->subYear(),
                'ended_at' => now()->subMonth(),
            ]);
            // Current period
            $current = TitleActivityPeriod::factory()->create([
                'title_id' => $model->id,
                'started_at' => now()->subWeek(),
                'ended_at' => null,
            ]);
            $model->load(['activityPeriods', 'currentActivityPeriod']);
            expect($model->activityPeriods)->toHaveCount(2);
            expect($model->currentActivityPeriod)->not->toBeNull();
            expect($model->currentActivityPeriod->ended_at)->toBeNull();
        });

        test('model can exist without activity periods', function () {
            $model = $this->model;
            expect($model->activityPeriods()->count())->toBe(0);
            expect($model->currentActivityPeriod)->toBeNull();
        });

        test('model maintains relationship integrity when activity periods are deleted', function () {
            $model = $this->model;
            $activityPeriod = TitleActivityPeriod::factory()->create([
                'title_id' => $model->id,
                'started_at' => now()->subWeek(),
                'ended_at' => null,
            ]);
            $model->refresh();

            expect($model->activityPeriods->pluck('id'))->toContain($activityPeriod->id);

            $activityPeriod->delete();
            $model->refresh();

            expect($model->activityPeriods()->count())->toBe(0);
        });

        test('model can be associated with new activity periods after creation', function () {
            $model = $this->model;
            expect($model->activityPeriods)->toBeEmpty();

            $activityPeriod = TitleActivityPeriod::factory()->create(['title_id' => $model->id]);
            $model->refresh();

            expect($model->activityPeriods->pluck('id'))->toContain($activityPeriod->id);
        });
    });
});
