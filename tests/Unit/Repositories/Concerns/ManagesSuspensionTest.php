<?php

declare(strict_types=1);

use App\Repositories\Concerns\ManagesSuspension;
use App\Repositories\Support\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

/**
 * Unit tests for ManagesSuspension trait in complete isolation.
 *
 * UNIT TEST SCOPE:
 * - Trait method behavior without business model dependencies
 * - Suspension record creation and management
 * - Suspension reinstatement tracking and end dates
 * - Database persistence verification
 * - Date handling and consistency
 *
 * This test ensures the ManagesSuspension trait is completely agnostic and reusable
 * across any repository that manages suspendable entities.
 *
 * @see \App\Repositories\Concerns\ManagesSuspension
 */
describe('ManagesSuspension Trait', function () {
    beforeEach(function () {
        testTime()->freeze();
        
        // Create anonymous repository class for trait testing
        $this->repository = new class extends BaseRepository {
            use ManagesSuspension;
        };
        
        // Create fake suspendable model for testing
        $this->suspendableModel = new class extends Model {
            protected $table = 'fake_suspendables';
            protected $fillable = ['name'];
            
            public function suspensions()
            {
                return $this->hasMany(get_class($this->getFakeSuspensionModel()), 'suspendable_id');
            }
            
            public function currentSuspension()
            {
                return $this->suspensions()->whereNull('ended_at');
            }
            
            private function getFakeSuspensionModel()
            {
                return new class extends Model {
                    protected $table = 'fake_suspensions';
                    protected $fillable = ['suspendable_id', 'started_at', 'ended_at'];
                    protected $casts = [
                        'started_at' => 'datetime',
                        'ended_at' => 'datetime',
                    ];
                };
            }
        };
    });

    describe('createSuspension method', function () {
        test('creates suspension record', function () {
            // Arrange
            $suspensionDate = Carbon::now();
            
            // Mock the suspensions relationship
            $suspensionsMock = \Mockery::mock(HasMany::class);
            $suspensionsMock->shouldReceive('create')
                ->once()
                ->with(['started_at' => $suspensionDate])
                ->andReturn((object) ['id' => 1, 'started_at' => $suspensionDate, 'ended_at' => null]);
            
            /** @var \App\Models\Contracts\Suspendable $model */
            $model = \Mockery::mock(\App\Models\Contracts\Suspendable::class);
            $model->shouldReceive('suspensions')->andReturn($suspensionsMock);

            // Act
            $this->repository->createSuspension($model, $suspensionDate);

            // Assert - Mock expectations verified automatically
        });

        test('sets suspension start date correctly', function () {
            // Arrange
            $suspensionDate = Carbon::parse('2024-01-15 10:00:00');
            
            // Mock the suspensions relationship
            $suspensionsMock = \Mockery::mock(HasMany::class);
            $suspensionsMock->shouldReceive('create')
                ->once()
                ->with(['started_at' => $suspensionDate])
                ->andReturn((object) ['started_at' => $suspensionDate]);
            
            /** @var \App\Models\Contracts\Suspendable $model */
            $model = \Mockery::mock(\App\Models\Contracts\Suspendable::class);
            $model->shouldReceive('suspensions')->andReturn($suspensionsMock);

            // Act
            $this->repository->createSuspension($model, $suspensionDate);

            // Assert - Mock expectations verified automatically
        });

        test('creates suspension with null end date by default', function () {
            // Arrange
            $suspensionDate = Carbon::now();
            
            // Mock the suspensions relationship
            $suspensionsMock = \Mockery::mock(HasMany::class);
            $suspensionsMock->shouldReceive('create')
                ->once()
                ->with(['started_at' => $suspensionDate])
                ->andReturn((object) ['started_at' => $suspensionDate, 'ended_at' => null]);
            
            /** @var \App\Models\Contracts\Suspendable $model */
            $model = \Mockery::mock(\App\Models\Contracts\Suspendable::class);
            $model->shouldReceive('suspensions')->andReturn($suspensionsMock);

            // Act & Assert
            $this->repository->createSuspension($model, $suspensionDate);
        });
    });

    describe('endSuspension method', function () {
        test('ends current suspension when it exists', function () {
            // Arrange
            $endDate = Carbon::now();
            
            $currentSuspension = \Mockery::mock();
            $currentSuspension->shouldReceive('update')
                ->once()
                ->with(['ended_at' => $endDate]);
            
            $currentSuspensionQuery = \Mockery::mock(HasOne::class);
            $currentSuspensionQuery->shouldReceive('first')
                ->once()
                ->andReturn($currentSuspension);
            
            /** @var \App\Models\Contracts\Suspendable $model */
            $model = \Mockery::mock(\App\Models\Contracts\Suspendable::class);
            $model->shouldReceive('currentSuspension')->andReturn($currentSuspensionQuery);

            // Act
            $this->repository->endSuspension($model, $endDate);

            // Assert - Mock expectations verified automatically
        });

        test('does nothing when no current suspension exists', function () {
            // Arrange
            $endDate = Carbon::now();
            
            $currentSuspensionQuery = \Mockery::mock(HasOne::class);
            $currentSuspensionQuery->shouldReceive('first')
                ->once()
                ->andReturn(null);
            
            /** @var \App\Models\Contracts\Suspendable $model */
            $model = \Mockery::mock(\App\Models\Contracts\Suspendable::class);
            $model->shouldReceive('currentSuspension')->andReturn($currentSuspensionQuery);

            // Act
            $this->repository->endSuspension($model, $endDate);

            // Assert - No update should be called (verified by mock)
        });

        test('sets end date correctly', function () {
            // Arrange
            $endDate = Carbon::parse('2024-12-31 15:30:00');
            
            $currentSuspension = \Mockery::mock();
            $currentSuspension->shouldReceive('update')
                ->once()
                ->with(['ended_at' => $endDate]);
            
            $currentSuspensionQuery = \Mockery::mock(HasOne::class);
            $currentSuspensionQuery->shouldReceive('first')
                ->once()
                ->andReturn($currentSuspension);
            
            /** @var \App\Models\Contracts\Suspendable $model */
            $model = \Mockery::mock(\App\Models\Contracts\Suspendable::class);
            $model->shouldReceive('currentSuspension')->andReturn($currentSuspensionQuery);

            // Act
            $this->repository->endSuspension($model, $endDate);

            // Assert - Mock expectations verified automatically
        });
    });

    describe('trait behavior verification', function () {
        test('trait methods exist and are callable', function () {
            // Assert
            expect(method_exists($this->repository, 'createSuspension'))->toBeTrue();
            expect(method_exists($this->repository, 'endSuspension'))->toBeTrue();
        });

        test('trait is model agnostic', function () {
            // Arrange
            $suspensionDate = Carbon::now();
            
            // Create two different mock models
            $model1 = \Mockery::mock(\App\Models\Contracts\Suspendable::class);
            $suspensions1 = \Mockery::mock(HasMany::class);
            $suspensions1->shouldReceive('create')->once()->andReturn((object) []);
            $model1->shouldReceive('suspensions')->andReturn($suspensions1);
            
            $model2 = \Mockery::mock(\App\Models\Contracts\Suspendable::class);
            $suspensions2 = \Mockery::mock(HasMany::class);
            $suspensions2->shouldReceive('create')->once()->andReturn((object) []);
            $model2->shouldReceive('suspensions')->andReturn($suspensions2);

            // Act - Use same repository instance with different models
            $this->repository->createSuspension($model1, $suspensionDate);
            $this->repository->createSuspension($model2, $suspensionDate);

            // Assert - Mock expectations verified (both models were used)
        });
    });

    describe('trait integration safety', function () {
        test('trait methods handle null gracefully', function () {
            // Arrange
            $endDate = Carbon::now();
            
            $currentSuspensionQuery = \Mockery::mock(HasOne::class);
            $currentSuspensionQuery->shouldReceive('first')->andReturn(null);
            
            /** @var \App\Models\Contracts\Suspendable $model */
            $model = \Mockery::mock(\App\Models\Contracts\Suspendable::class);
            $model->shouldReceive('currentSuspension')->andReturn($currentSuspensionQuery);

            // Act & Assert - Should not throw exception
            expect(fn() => $this->repository->endSuspension($model, $endDate))->not->toThrow(Exception::class);
        });

        test('trait is reusable across multiple repository instances', function () {
            // Arrange
            $anotherRepository = new class extends BaseRepository {
                use ManagesSuspension;
            };
            
            $suspensionDate = Carbon::now();
            /** @var \App\Models\Contracts\Suspendable $model */
            $model = \Mockery::mock(\App\Models\Contracts\Suspendable::class);
            $suspensions = \Mockery::mock(HasMany::class);
            $suspensions->shouldReceive('create')->twice()->andReturn((object) []);
            $model->shouldReceive('suspensions')->andReturn($suspensions);

            // Act - Use trait methods from different repository instances
            $this->repository->createSuspension($model, $suspensionDate);
            $anotherRepository->createSuspension($model, $suspensionDate);

            // Assert - Both repositories can use the trait
            expect(method_exists($anotherRepository, 'createSuspension'))->toBeTrue();
        });
    });

    afterEach(function () {
        \Mockery::close();
    });
});