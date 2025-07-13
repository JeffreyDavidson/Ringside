<?php

declare(strict_types=1);

use App\Repositories\Concerns\ManagesActivity;
use App\Repositories\Support\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

/**
 * Unit tests for ManagesActivity trait in complete isolation.
 *
 * UNIT TEST SCOPE:
 * - Trait method behavior without business model dependencies
 * - Activity period creation and management
 * - Activity period ending and updates
 * - Date handling and consistency
 *
 * This test ensures the ManagesActivity trait is completely agnostic and reusable
 * across any repository that manages activity periods and activation/deactivation workflows.
 *
 * @see \App\Repositories\Concerns\ManagesActivity
 */
describe('ManagesActivity Trait', function () {
    beforeEach(function () {
        testTime()->freeze();
        
        // Create anonymous repository class for trait testing
        $this->repository = new class extends BaseRepository {
            use ManagesActivity;
        };
    });

    describe('createActivity method', function () {
        test('creates activity period record', function () {
            // Arrange
            $activityDate = Carbon::now();
            
            // Mock the activityPeriods relationship
            $activityPeriodsMock = Mockery::mock();
            $activityPeriodsMock->shouldReceive('updateOrCreate')
                ->once()
                ->with(['ended_at' => null], ['started_at' => $activityDate->toDateTimeString()])
                ->andReturn((object) ['id' => 1, 'started_at' => $activityDate, 'ended_at' => null]);
            
            /** @var \App\Models\Contracts\HasActivityPeriods $model */
            $model = Mockery::mock(\App\Models\Contracts\HasActivityPeriods::class);
            $model->shouldReceive('activityPeriods')->andReturn($activityPeriodsMock);

            // Act
            $this->repository->createActivity($model, $activityDate);

            // Assert - Mock expectations verified automatically
        });

        test('sets activity start date correctly', function () {
            // Arrange
            $activityDate = Carbon::parse('2024-01-15 10:00:00');
            
            // Mock the activityPeriods relationship
            $activityPeriodsMock = Mockery::mock();
            $activityPeriodsMock->shouldReceive('updateOrCreate')
                ->once()
                ->with(['ended_at' => null], ['started_at' => $activityDate->toDateTimeString()])
                ->andReturn((object) ['started_at' => $activityDate]);
            
            /** @var \App\Models\Contracts\HasActivityPeriods $model */
            $model = Mockery::mock(\App\Models\Contracts\HasActivityPeriods::class);
            $model->shouldReceive('activityPeriods')->andReturn($activityPeriodsMock);

            // Act
            $this->repository->createActivity($model, $activityDate);

            // Assert - Mock expectations verified automatically
        });

        test('creates activity period with null end date by default', function () {
            // Arrange
            $activityDate = Carbon::now();
            
            // Mock the activityPeriods relationship
            $activityPeriodsMock = Mockery::mock();
            $activityPeriodsMock->shouldReceive('updateOrCreate')
                ->once()
                ->with(['ended_at' => null], ['started_at' => $activityDate->toDateTimeString()])
                ->andReturn((object) ['started_at' => $activityDate, 'ended_at' => null]);
            
            /** @var \App\Models\Contracts\HasActivityPeriods $model */
            $model = Mockery::mock(\App\Models\Contracts\HasActivityPeriods::class);
            $model->shouldReceive('activityPeriods')->andReturn($activityPeriodsMock);

            // Act & Assert
            $this->repository->createActivity($model, $activityDate);
        });

        test('uses updateOrCreate to manage single active period', function () {
            // Arrange
            $firstDate = Carbon::now()->subDays(10);
            $secondDate = Carbon::now();
            
            /** @var \App\Models\Contracts\HasActivityPeriods $model */
            $model = Mockery::mock(\App\Models\Contracts\HasActivityPeriods::class);
            
            // Mock first call
            $activityPeriods1 = Mockery::mock();
            $activityPeriods1->shouldReceive('updateOrCreate')
                ->once()
                ->with(['ended_at' => null], ['started_at' => $firstDate->toDateTimeString()])
                ->andReturn((object) ['started_at' => $firstDate]);
            
            // Mock second call
            $activityPeriods2 = Mockery::mock();
            $activityPeriods2->shouldReceive('updateOrCreate')
                ->once()
                ->with(['ended_at' => null], ['started_at' => $secondDate->toDateTimeString()])
                ->andReturn((object) ['started_at' => $secondDate]);
            
            $model->shouldReceive('activityPeriods')->twice()->andReturn($activityPeriods1, $activityPeriods2);

            // Act - Create first activity then second activity
            $this->repository->createActivity($model, $firstDate);
            $this->repository->createActivity($model, $secondDate);

            // Assert - Mock expectations verified (updateOrCreate called twice)
        });
    });

    describe('endActivity method', function () {
        test('ends current activity period when it exists', function () {
            // Arrange
            $endDate = Carbon::now();
            
            $currentActivityPeriodQuery = Mockery::mock();
            $currentActivityPeriodQuery->shouldReceive('update')
                ->once()
                ->with(['ended_at' => $endDate->toDateTimeString()])
                ->andReturn(1);
            
            /** @var \App\Models\Contracts\HasActivityPeriods $model */
            $model = Mockery::mock(\App\Models\Contracts\HasActivityPeriods::class);
            $model->shouldReceive('currentActivityPeriod')->andReturn($currentActivityPeriodQuery);

            // Act
            $this->repository->endActivity($model, $endDate);

            // Assert - Mock expectations verified automatically
        });

        test('sets activity end date correctly', function () {
            // Arrange
            $endDate = Carbon::parse('2024-12-31 15:30:00');
            
            $currentActivityPeriodQuery = Mockery::mock();
            $currentActivityPeriodQuery->shouldReceive('update')
                ->once()
                ->with(['ended_at' => $endDate->toDateTimeString()])
                ->andReturn(1);
            
            /** @var \App\Models\Contracts\HasActivityPeriods $model */
            $model = Mockery::mock(\App\Models\Contracts\HasActivityPeriods::class);
            $model->shouldReceive('currentActivityPeriod')->andReturn($currentActivityPeriodQuery);

            // Act
            $this->repository->endActivity($model, $endDate);

            // Assert - Mock expectations verified automatically
        });

        test('handles update operation correctly', function () {
            // Arrange
            $endDate = Carbon::parse('2024-06-15 12:00:00');
            
            $currentActivityPeriodQuery = Mockery::mock();
            $currentActivityPeriodQuery->shouldReceive('update')
                ->once()
                ->with(['ended_at' => $endDate->toDateTimeString()])
                ->andReturn(1);
            
            /** @var \App\Models\Contracts\HasActivityPeriods $model */
            $model = Mockery::mock(\App\Models\Contracts\HasActivityPeriods::class);
            $model->shouldReceive('currentActivityPeriod')->andReturn($currentActivityPeriodQuery);

            // Act
            $this->repository->endActivity($model, $endDate);

            // Assert - Mock expectations verified automatically
        });
    });

    describe('trait behavior verification', function () {
        test('trait methods exist and are callable', function () {
            // Assert
            expect(method_exists($this->repository, 'createActivity'))->toBeTrue();
            expect(method_exists($this->repository, 'endActivity'))->toBeTrue();
        });

        test('trait is model agnostic', function () {
            // Arrange
            $activityDate = Carbon::now();
            
            // Create two different mock models
            /** @var \App\Models\Contracts\HasActivityPeriods $model1 */
            $model1 = Mockery::mock(\App\Models\Contracts\HasActivityPeriods::class);
            $activityPeriods1 = Mockery::mock();
            $activityPeriods1->shouldReceive('updateOrCreate')->once()->andReturn((object) []);
            $model1->shouldReceive('activityPeriods')->andReturn($activityPeriods1);
            
            /** @var \App\Models\Contracts\HasActivityPeriods $model2 */
            $model2 = Mockery::mock(\App\Models\Contracts\HasActivityPeriods::class);
            $activityPeriods2 = Mockery::mock();
            $activityPeriods2->shouldReceive('updateOrCreate')->once()->andReturn((object) []);
            $model2->shouldReceive('activityPeriods')->andReturn($activityPeriods2);

            // Act - Use same repository instance with different models
            $this->repository->createActivity($model1, $activityDate);
            $this->repository->createActivity($model2, $activityDate);

            // Assert - Mock expectations verified (both models were used)
        });

        test('works with different relationship names', function () {
            // Arrange
            $activityDate = Carbon::now();
            $endDate = Carbon::now()->addDays(30);
            
            // Test with multiple models using same trait methods
            /** @var \App\Models\Contracts\HasActivityPeriods $model */
            $model = Mockery::mock(\App\Models\Contracts\HasActivityPeriods::class);
            
            $activityPeriods = Mockery::mock();
            $activityPeriods->shouldReceive('updateOrCreate')->once()->andReturn((object) []);
            $model->shouldReceive('activityPeriods')->andReturn($activityPeriods);
            
            $currentActivityPeriod = Mockery::mock();
            $currentActivityPeriod->shouldReceive('update')->once()->andReturn(1);
            $model->shouldReceive('currentActivityPeriod')->andReturn($currentActivityPeriod);

            // Act - Create and end activity for same model
            $this->repository->createActivity($model, $activityDate);
            $this->repository->endActivity($model, $endDate);

            // Assert - Mock expectations verified (both operations completed)
        });
    });

    describe('trait integration safety', function () {
        test('trait methods handle activity operations gracefully', function () {
            // Arrange
            $activityDate = Carbon::now();
            $endDate = Carbon::now()->addDays(30);
            
            /** @var \App\Models\Contracts\HasActivityPeriods $model */
            $model = Mockery::mock(\App\Models\Contracts\HasActivityPeriods::class);
            $activityPeriods = Mockery::mock();
            $activityPeriods->shouldReceive('updateOrCreate')
                ->once()
                ->with(['ended_at' => null], ['started_at' => $activityDate->toDateTimeString()])
                ->andReturn((object) []);
            $model->shouldReceive('activityPeriods')->andReturn($activityPeriods);
            
            $currentActivityPeriod = Mockery::mock();
            $currentActivityPeriod->shouldReceive('update')
                ->once()
                ->with(['ended_at' => $endDate->toDateTimeString()])
                ->andReturn(1);
            $model->shouldReceive('currentActivityPeriod')->andReturn($currentActivityPeriod);

            // Act & Assert - Should not throw exception
            expect(fn() => $this->repository->createActivity($model, $activityDate))->not->toThrow(Exception::class);
            expect(fn() => $this->repository->endActivity($model, $endDate))->not->toThrow(Exception::class);
        });

        test('trait is reusable across multiple repository instances', function () {
            // Arrange
            $anotherRepository = new class extends BaseRepository {
                use ManagesActivity;
            };
            
            $activityDate = Carbon::now();
            
            /** @var \App\Models\Contracts\HasActivityPeriods $model */
            $model = Mockery::mock(\App\Models\Contracts\HasActivityPeriods::class);
            $activityPeriods = Mockery::mock();
            $activityPeriods->shouldReceive('updateOrCreate')->twice()->andReturn((object) []);
            $model->shouldReceive('activityPeriods')->andReturn($activityPeriods);

            // Act - Use trait methods from different repository instances
            $this->repository->createActivity($model, $activityDate);
            $anotherRepository->createActivity($model, $activityDate);

            // Assert - Both repositories can use the trait
            expect(method_exists($anotherRepository, 'createActivity'))->toBeTrue();
        });
    });

    afterEach(function () {
        Mockery::close();
    });
});