<?php

declare(strict_types=1);

use App\Repositories\Concerns\ManagesInjury;
use App\Repositories\Support\BaseRepository;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

/**
 * Unit tests for ManagesInjury trait in complete isolation.
 *
 * UNIT TEST SCOPE:
 * - Trait method behavior without business model dependencies
 * - Injury record creation and management
 * - Injury recovery tracking and end dates
 * - Date handling and consistency
 *
 * This test ensures the ManagesInjury trait is completely agnostic and reusable
 * across any repository that manages injurable entities.
 *
 * @see \App\Repositories\Concerns\ManagesInjury
 */
describe('ManagesInjury Trait', function () {
    beforeEach(function () {
        testTime()->freeze();

        // Create anonymous repository class for trait testing
        $this->repository = new class extends BaseRepository {
            use ManagesInjury;
        };
    });

    describe('createInjury method', function () {
        test('creates injury record', function () {
            // Arrange
            $injuryDate = Carbon::now();

            // Mock the injuries relationship
            $injuriesMock = \Mockery::mock(HasMany::class);
            $injuriesMock->shouldReceive('create')
                ->once()
                ->with(['started_at' => $injuryDate->toDateTimeString()])
                ->andReturn((object) ['id' => 1, 'started_at' => $injuryDate, 'ended_at' => null]);

            /** @var \App\Models\Contracts\Injurable $model */
            $model = \Mockery::mock(\App\Models\Contracts\Injurable::class);
            $model->shouldReceive('injuries')->andReturn($injuriesMock);

            // Act
            $this->repository->createInjury($model, $injuryDate);

            // Assert - Mock expectations verified automatically
        });

        test('sets injury start date correctly', function () {
            // Arrange
            $injuryDate = Carbon::parse('2024-01-15 10:00:00');

            // Mock the injuries relationship
            $injuriesMock = \Mockery::mock(HasMany::class);
            $injuriesMock->shouldReceive('create')
                ->once()
                ->with(['started_at' => $injuryDate->toDateTimeString()])
                ->andReturn((object) ['started_at' => $injuryDate]);

            /** @var \App\Models\Contracts\Injurable $model */
            $model = \Mockery::mock(\App\Models\Contracts\Injurable::class);
            $model->shouldReceive('injuries')->andReturn($injuriesMock);

            // Act
            $this->repository->createInjury($model, $injuryDate);

            // Assert - Mock expectations verified automatically
        });

        test('creates injury with null end date by default', function () {
            // Arrange
            $injuryDate = Carbon::now();

            // Mock the injuries relationship
            $injuriesMock = \Mockery::mock(HasMany::class);
            $injuriesMock->shouldReceive('create')
                ->once()
                ->with(['started_at' => $injuryDate->toDateTimeString()])
                ->andReturn((object) ['started_at' => $injuryDate, 'ended_at' => null]);

            /** @var \App\Models\Contracts\Injurable $model */
            $model = \Mockery::mock(\App\Models\Contracts\Injurable::class);
            $model->shouldReceive('injuries')->andReturn($injuriesMock);

            // Act & Assert
            $this->repository->createInjury($model, $injuryDate);
        });
    });

    describe('endInjury method', function () {
        test('ends current injury when it exists', function () {
            // Arrange
            $recoveryDate = Carbon::now();

            $currentInjury = \Mockery::mock();
            $currentInjury->shouldReceive('update')
                ->once()
                ->with(['ended_at' => $recoveryDate->toDateTimeString()]);

            $currentInjuryQuery = \Mockery::mock(HasOne::class);
            $currentInjuryQuery->shouldReceive('first')
                ->once()
                ->andReturn($currentInjury);

            /** @var \App\Models\Contracts\Injurable $model */
            $model = \Mockery::mock(\App\Models\Contracts\Injurable::class);
            $model->shouldReceive('currentInjury')->andReturn($currentInjuryQuery);

            // Act
            $this->repository->endInjury($model, $recoveryDate);

            // Assert - Mock expectations verified automatically
        });

        test('does nothing when no current injury exists', function () {
            // Arrange
            $recoveryDate = Carbon::now();

            $currentInjuryQuery = \Mockery::mock(HasOne::class);
            $currentInjuryQuery->shouldReceive('first')
                ->once()
                ->andReturn(null);

            /** @var \App\Models\Contracts\Injurable $model */
            $model = \Mockery::mock(\App\Models\Contracts\Injurable::class);
            $model->shouldReceive('currentInjury')->andReturn($currentInjuryQuery);

            // Act
            $this->repository->endInjury($model, $recoveryDate);

            // Assert - No update should be called (verified by mock)
        });

        test('sets recovery date correctly', function () {
            // Arrange
            $recoveryDate = Carbon::parse('2024-12-31 15:30:00');

            $currentInjury = \Mockery::mock();
            $currentInjury->shouldReceive('update')
                ->once()
                ->with(['ended_at' => $recoveryDate->toDateTimeString()]);

            $currentInjuryQuery = \Mockery::mock(HasOne::class);
            $currentInjuryQuery->shouldReceive('first')
                ->once()
                ->andReturn($currentInjury);

            /** @var \App\Models\Contracts\Injurable $model */
            $model = \Mockery::mock(\App\Models\Contracts\Injurable::class);
            $model->shouldReceive('currentInjury')->andReturn($currentInjuryQuery);

            // Act
            $this->repository->endInjury($model, $recoveryDate);

            // Assert - Mock expectations verified automatically
        });
    });

    describe('trait behavior verification', function () {
        test('trait methods exist and are callable', function () {
            // Assert
            expect(method_exists($this->repository, 'createInjury'))->toBeTrue();
            expect(method_exists($this->repository, 'endInjury'))->toBeTrue();
        });

        test('trait is model agnostic', function () {
            // Arrange
            $injuryDate = Carbon::now();

            // Create two different mock models
            /** @var \App\Models\Contracts\Injurable $model1 */
            $model1 = \Mockery::mock(\App\Models\Contracts\Injurable::class);
            $injuries1 = \Mockery::mock(HasMany::class);
            $injuries1->shouldReceive('create')->once()->andReturn((object) []);
            $model1->shouldReceive('injuries')->andReturn($injuries1);

            /** @var \App\Models\Contracts\Injurable $model2 */
            $model2 = \Mockery::mock(\App\Models\Contracts\Injurable::class);
            $injuries2 = \Mockery::mock(HasMany::class);
            $injuries2->shouldReceive('create')->once()->andReturn((object) []);
            $model2->shouldReceive('injuries')->andReturn($injuries2);

            // Act - Use same repository instance with different models
            $this->repository->createInjury($model1, $injuryDate);
            $this->repository->createInjury($model2, $injuryDate);

            // Assert - Mock expectations verified (both models were used)
        });
    });

    describe('trait integration safety', function () {
        test('trait methods handle null gracefully', function () {
            // Arrange
            $recoveryDate = Carbon::now();

            $currentInjuryQuery = \Mockery::mock(HasOne::class);
            $currentInjuryQuery->shouldReceive('first')->andReturn(null);

            /** @var \App\Models\Contracts\Injurable $model */
            $model = \Mockery::mock(\App\Models\Contracts\Injurable::class);
            $model->shouldReceive('currentInjury')->andReturn($currentInjuryQuery);

            // Act & Assert - Should not throw exception
            expect(fn() => $this->repository->endInjury($model, $recoveryDate))->not->toThrow(Exception::class);
        });

        test('trait is reusable across multiple repository instances', function () {
            // Arrange
            $anotherRepository = new class extends BaseRepository {
                use ManagesInjury;
            };

            $injuryDate = Carbon::now();
            /** @var \App\Models\Contracts\Injurable $model */
            $model = \Mockery::mock(\App\Models\Contracts\Injurable::class);
            $injuries = \Mockery::mock(HasMany::class);
            $injuries->shouldReceive('create')->twice()->andReturn((object) []);
            $model->shouldReceive('injuries')->andReturn($injuries);

            // Act - Use trait methods from different repository instances
            $this->repository->createInjury($model, $injuryDate);
            $anotherRepository->createInjury($model, $injuryDate);

            // Assert - Both repositories can use the trait
            expect(method_exists($anotherRepository, 'createInjury'))->toBeTrue();
        });
    });

    afterEach(function () {
        \Mockery::close();
    });
});
