<?php

declare(strict_types=1);

use App\Repositories\Concerns\ManagesDates;
use App\Repositories\Support\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

/**
 * Unit tests for ManagesDates trait in complete isolation.
 *
 * UNIT TEST SCOPE:
 * - Trait method behavior without business model dependencies
 * - Period creation with start dates
 * - Period ending with end dates
 * - Active period checking
 * - Period duration calculations
 * - Date handling and consistency
 *
 * This test ensures the ManagesDates trait is completely agnostic and reusable
 * across any repository that handles time-based relationships and temporal workflows.
 *
 * @see \App\Repositories\Concerns\ManagesDates
 */
describe('ManagesDates Trait', function () {
    beforeEach(function () {
        testTime()->freeze();

        // Create anonymous repository class for trait testing
        $this->repository = new class extends BaseRepository {
            use ManagesDates;

            // Expose protected methods for testing
            public function testStartPeriod($model, $relationship, $startDate, $additionalData = [])
            {
                return $this->startPeriod($model, $relationship, $startDate, $additionalData);
            }

            public function testEndCurrentPeriod($model, $currentRelationship, $endDate)
            {
                return $this->endCurrentPeriod($model, $currentRelationship, $endDate);
            }

            public function testHasActivePeriod($model, $currentRelationship)
            {
                return $this->hasActivePeriod($model, $currentRelationship);
            }

            public function testGetCurrentPeriodDuration($model, $currentRelationship)
            {
                return $this->getCurrentPeriodDuration($model, $currentRelationship);
            }
        };
    });

    describe('startPeriod method', function () {
        test('creates period with start date', function () {
            // Arrange
            $startDate = Carbon::now();
            $expectedData = ['started_at' => $startDate];

            // Mock the relationship
            $relationshipMock = Mockery::mock(HasMany::class);
            $relationshipMock->shouldReceive('create')
                ->once()
                ->with($expectedData)
                ->andReturn(new class extends Model {
                    public $id = 1;
                    public $started_at;
                    public function __construct() {
                        $this->started_at = Carbon::now();
                    }
                });

            $model = Mockery::mock(Model::class);
            $model->shouldReceive('employments')->andReturn($relationshipMock);

            // Act
            $result = $this->repository->testStartPeriod($model, 'employments', $startDate);

            // Assert
            expect($result)->toBeInstanceOf(Model::class);
        });

        test('sets start date correctly', function () {
            // Arrange
            $startDate = Carbon::parse('2024-01-15 10:00:00');
            $expectedData = ['started_at' => $startDate];

            // Mock the relationship
            $relationshipMock = Mockery::mock(HasMany::class);
            $relationshipMock->shouldReceive('create')
                ->once()
                ->with($expectedData)
                ->andReturn(new class($startDate) extends Model {
                    public $started_at;
                    public function __construct($startDate) {
                        $this->started_at = $startDate;
                    }
                });

            $model = Mockery::mock(Model::class);
            $model->shouldReceive('periods')->andReturn($relationshipMock);

            // Act
            $result = $this->repository->testStartPeriod($model, 'periods', $startDate);

            // Assert
            expect($result->started_at)->toBe($startDate);
        });

        test('accepts additional data for period record', function () {
            // Arrange
            $startDate = Carbon::now();
            $additionalData = ['notes' => 'Special contract terms', 'type' => 'temporary'];
            $expectedData = array_merge(['started_at' => $startDate], $additionalData);

            // Mock the relationship
            $relationshipMock = Mockery::mock(HasMany::class);
            $relationshipMock->shouldReceive('create')
                ->once()
                ->with($expectedData)
                ->andReturn(new class extends Model {
                    public $notes;
                    public $type;
                    public function __construct() {
                        $this->notes = 'Special contract terms';
                        $this->type = 'temporary';
                    }
                });

            $model = Mockery::mock(Model::class);
            $model->shouldReceive('employments')->andReturn($relationshipMock);

            // Act
            $result = $this->repository->testStartPeriod($model, 'employments', $startDate, $additionalData);

            // Assert
            expect($result->notes)->toBe('Special contract terms');
            expect($result->type)->toBe('temporary');
        });

        test('works with different relationship names', function () {
            // Arrange
            $startDate = Carbon::now();
            $relationshipNames = ['employments', 'memberships', 'activations', 'subscriptions'];

            foreach ($relationshipNames as $relationshipName) {
                $model = Mockery::mock(Model::class);
                $relationship = Mockery::mock(HasMany::class);
                $relationship->shouldReceive('create')->once()->andReturn(new class extends Model {
                    public $id = 1;
                });
                $model->shouldReceive($relationshipName)->andReturn($relationship);

                // Act
                $this->repository->testStartPeriod($model, $relationshipName, $startDate);
            }

            // Assert - All relationships were called (verified by mocks)
        });
    });

    describe('endCurrentPeriod method', function () {
        test('ends current period when it exists', function () {
            // Arrange
            $endDate = Carbon::now();

            $currentPeriod = Mockery::mock();
            $currentPeriod->shouldReceive('update')
                ->once()
                ->with(['ended_at' => $endDate])
                ->andReturn(true);

            $currentRelationshipQuery = Mockery::mock(HasOne::class);
            $currentRelationshipQuery->shouldReceive('first')
                ->once()
                ->andReturn($currentPeriod);

            $model = Mockery::mock(Model::class);
            $model->shouldReceive('currentEmployment')->andReturn($currentRelationshipQuery);

            // Act
            $result = $this->repository->testEndCurrentPeriod($model, 'currentEmployment', $endDate);

            // Assert
            expect($result)->toBeTrue();
        });

        test('returns false when no current period exists', function () {
            // Arrange
            $endDate = Carbon::now();

            $currentRelationshipQuery = Mockery::mock(HasOne::class);
            $currentRelationshipQuery->shouldReceive('first')
                ->once()
                ->andReturn(null);

            $model = Mockery::mock(Model::class);
            $model->shouldReceive('currentEmployment')->andReturn($currentRelationshipQuery);

            // Act
            $result = $this->repository->testEndCurrentPeriod($model, 'currentEmployment', $endDate);

            // Assert
            expect($result)->toBeFalse();
        });

        test('sets end date correctly', function () {
            // Arrange
            $endDate = Carbon::parse('2024-12-31 15:30:00');

            $currentPeriod = Mockery::mock();
            $currentPeriod->shouldReceive('update')
                ->once()
                ->with(['ended_at' => $endDate])
                ->andReturn(true);

            $currentRelationshipQuery = Mockery::mock(HasOne::class);
            $currentRelationshipQuery->shouldReceive('first')
                ->once()
                ->andReturn($currentPeriod);

            $model = Mockery::mock(Model::class);
            $model->shouldReceive('currentMembership')->andReturn($currentRelationshipQuery);

            // Act
            $result = $this->repository->testEndCurrentPeriod($model, 'currentMembership', $endDate);

            // Assert
            expect($result)->toBeTrue();
        });
    });

    describe('hasActivePeriod method', function () {
        test('returns true when active period exists', function () {
            // Arrange
            $currentRelationshipQuery = Mockery::mock(HasOne::class);
            $currentRelationshipQuery->shouldReceive('exists')
                ->once()
                ->andReturn(true);

            $model = Mockery::mock(Model::class);
            $model->shouldReceive('currentEmployment')->andReturn($currentRelationshipQuery);

            // Act
            $result = $this->repository->testHasActivePeriod($model, 'currentEmployment');

            // Assert
            expect($result)->toBeTrue();
        });

        test('returns false when no active period exists', function () {
            // Arrange
            $currentRelationshipQuery = Mockery::mock(HasOne::class);
            $currentRelationshipQuery->shouldReceive('exists')
                ->once()
                ->andReturn(false);

            $model = Mockery::mock(Model::class);
            $model->shouldReceive('currentEmployment')->andReturn($currentRelationshipQuery);

            // Act
            $result = $this->repository->testHasActivePeriod($model, 'currentEmployment');

            // Assert
            expect($result)->toBeFalse();
        });

        test('works with different relationship names', function () {
            // Arrange
            $relationshipNames = ['currentEmployment', 'currentMembership', 'currentActivation'];

            foreach ($relationshipNames as $index => $relationshipName) {
                $model = Mockery::mock(Model::class);
                $relationship = Mockery::mock(HasMany::class);
                $relationship->shouldReceive('exists')->once()->andReturn($index % 2 === 0);
                $model->shouldReceive($relationshipName)->andReturn($relationship);

                // Act
                $result = $this->repository->testHasActivePeriod($model, $relationshipName);

                // Assert
                expect($result)->toBe($index % 2 === 0);
            }
        });
    });

    describe('getCurrentPeriodDuration method', function () {
        test('calculates duration correctly for active period', function () {
            // Arrange
            $startDate = Carbon::now()->subDays(30);

            $currentPeriod = (object) ['started_at' => $startDate];

            $currentRelationshipQuery = Mockery::mock(HasOne::class);
            $currentRelationshipQuery->shouldReceive('first')
                ->once()
                ->andReturn($currentPeriod);

            $model = Mockery::mock(Model::class);
            $model->shouldReceive('currentEmployment')->andReturn($currentRelationshipQuery);

            // Act
            $duration = $this->repository->testGetCurrentPeriodDuration($model, 'currentEmployment');

            // Assert
            expect($duration)->toBe(30);
        });

        test('returns null when no current period exists', function () {
            // Arrange
            $currentRelationshipQuery = Mockery::mock(HasOne::class);
            $currentRelationshipQuery->shouldReceive('first')
                ->once()
                ->andReturn(null);

            $model = Mockery::mock(Model::class);
            $model->shouldReceive('currentEmployment')->andReturn($currentRelationshipQuery);

            // Act
            $duration = $this->repository->testGetCurrentPeriodDuration($model, 'currentEmployment');

            // Assert
            expect($duration)->toBeNull();
        });

        test('returns null when current period has no start date', function () {
            // Arrange
            $currentPeriod = (object) ['started_at' => null];

            $currentRelationshipQuery = Mockery::mock(HasOne::class);
            $currentRelationshipQuery->shouldReceive('first')
                ->once()
                ->andReturn($currentPeriod);

            $model = Mockery::mock(Model::class);
            $model->shouldReceive('currentEmployment')->andReturn($currentRelationshipQuery);

            // Act
            $duration = $this->repository->testGetCurrentPeriodDuration($model, 'currentEmployment');

            // Assert
            expect($duration)->toBeNull();
        });

        test('calculates duration for recent period correctly', function () {
            // Arrange - 12 hours ago (0.5 days, should be cast to int as 0)
            $startDate = Carbon::now()->subHours(12);

            $currentPeriod = new class {
                public $started_at;
                public function __construct() {
                    $this->started_at = Carbon::now()->subHours(12);
                }
            };

            $currentRelationshipQuery = Mockery::mock(HasOne::class);
            $currentRelationshipQuery->shouldReceive('first')
                ->once()
                ->andReturn($currentPeriod);

            $model = Mockery::mock(Model::class);
            $model->shouldReceive('currentEmployment')->andReturn($currentRelationshipQuery);

            // Act
            $duration = $this->repository->testGetCurrentPeriodDuration($model, 'currentEmployment');

            // Assert
            expect($duration)->toBe(0); // Less than 1 day rounds to 0
        });
    });

    describe('trait behavior verification', function () {
        test('trait methods exist and are callable', function () {
            // Assert
            expect(method_exists($this->repository, 'startPeriod'))->toBeTrue();
            expect(method_exists($this->repository, 'endCurrentPeriod'))->toBeTrue();
            expect(method_exists($this->repository, 'hasActivePeriod'))->toBeTrue();
            expect(method_exists($this->repository, 'getCurrentPeriodDuration'))->toBeTrue();
        });

        test('trait is model agnostic', function () {
            // Arrange
            $startDate = Carbon::now();

            // Create different mock models
            $model1 = Mockery::mock(Model::class);
            $relationship1 = Mockery::mock(HasMany::class);
            $relationship1->shouldReceive('create')->once()->andReturn(new class extends Model {});
            $model1->shouldReceive('employments')->andReturn($relationship1);

            $model2 = Mockery::mock(Model::class);
            $relationship2 = Mockery::mock(HasMany::class);
            $relationship2->shouldReceive('create')->once()->andReturn(new class extends Model {});
            $model2->shouldReceive('memberships')->andReturn($relationship2);

            // Act - Use same repository instance with different models
            $this->repository->testStartPeriod($model1, 'employments', $startDate);
            $this->repository->testStartPeriod($model2, 'memberships', $startDate);

            // Assert - Mock expectations verified (both models were used)
        });

        test('trait methods are protected', function () {
            // Assert
            $reflection = new ReflectionClass($this->repository);

            expect($reflection->hasMethod('startPeriod'))->toBeTrue();
            expect($reflection->hasMethod('endCurrentPeriod'))->toBeTrue();
            expect($reflection->hasMethod('hasActivePeriod'))->toBeTrue();
            expect($reflection->hasMethod('getCurrentPeriodDuration'))->toBeTrue();

            // Methods should be protected in the trait
            expect($reflection->getMethod('startPeriod')->isProtected())->toBeTrue();
            expect($reflection->getMethod('endCurrentPeriod')->isProtected())->toBeTrue();
            expect($reflection->getMethod('hasActivePeriod')->isProtected())->toBeTrue();
            expect($reflection->getMethod('getCurrentPeriodDuration')->isProtected())->toBeTrue();
        });
    });

    describe('trait integration safety', function () {
        test('trait is reusable across multiple repository instances', function () {
            // Arrange
            $anotherRepository = new class extends BaseRepository {
                use ManagesDates;

                public function testStartPeriod($model, $relationship, $startDate, $additionalData = [])
                {
                    return $this->startPeriod($model, $relationship, $startDate, $additionalData);
                }
            };

            $startDate = Carbon::now();

            $model = Mockery::mock(Model::class);
            $relationship = Mockery::mock(HasMany::class);
            $relationship->shouldReceive('create')->once()->andReturn(new class extends Model {});
            $model->shouldReceive('employments')->andReturn($relationship);

            // Act - Use trait methods from different repository instance
            $anotherRepository->testStartPeriod($model, 'employments', $startDate);

            // Assert - Repository can use the trait
            expect(method_exists($anotherRepository, 'startPeriod'))->toBeTrue();
        });

        test('trait handles edge cases gracefully', function () {
            // Arrange
            $startDate = Carbon::now();
            $emptyData = [];

            $model = Mockery::mock(Model::class);
            $relationship = Mockery::mock(HasMany::class);
            $relationship->shouldReceive('create')
                ->once()
                ->with(['started_at' => $startDate])
                ->andReturn(new class extends Model {});
            $model->shouldReceive('employments')->andReturn($relationship);

            // Act & Assert - Should not throw exception
            expect(fn() => $this->repository->testStartPeriod($model, 'employments', $startDate, $emptyData))
                ->not->toThrow(Exception::class);
        });
    });

    afterEach(function () {
        Mockery::close();
    });
});
