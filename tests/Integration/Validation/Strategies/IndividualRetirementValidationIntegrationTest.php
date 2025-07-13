<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\Validation\Strategies\IndividualRetirementValidation;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;

/**
 * Integration tests for IndividualRetirementValidation strategy.
 *
 * INTEGRATION TEST SCOPE:
 * - Database interaction through Wrestler model factories
 * - Complete validation workflow with exception handling
 * - Business rule validation with realistic model states
 * - Employment status verification with actual employment data
 * - Strategy pattern integration with model validation
 *
 * These tests verify that the IndividualRetirementValidation strategy
 * works correctly with real database data and model relationships.
 *
 * @see \App\Models\Validation\Strategies\IndividualRetirementValidation
 */
describe('IndividualRetirementValidation Integration Tests', function () {
    beforeEach(function () {
        $this->strategy = new IndividualRetirementValidation();
    });

    describe('successful validation scenarios', function () {
        test('allows retirement for bookable wrestler', function () {
            // Arrange
            $wrestler = Wrestler::factory()->bookable()->create();

            // Act & Assert - Should not throw exception
            $this->strategy->validate($wrestler);
            expect(true)->toBeTrue(); // Assertion to confirm no exception
        });

        test('allows retirement for suspended wrestler', function () {
            // Arrange
            $wrestler = Wrestler::factory()->suspended()->create();

            // Act & Assert - Should not throw exception
            $this->strategy->validate($wrestler);
            expect(true)->toBeTrue();
        });

        test('allows retirement for injured wrestler', function () {
            // Arrange
            $wrestler = Wrestler::factory()->injured()->create();

            // Act & Assert - Should not throw exception
            $this->strategy->validate($wrestler);
            expect(true)->toBeTrue();
        });

        test('allows retirement for released wrestler', function () {
            // Arrange
            $wrestler = Wrestler::factory()->released()->create();

            // Act & Assert - Should not throw exception
            // Note: Released entities CAN be retired per business rules
            $this->strategy->validate($wrestler);
            expect(true)->toBeTrue();
        });
    });

    describe('validation failure scenarios', function () {
        test('throws exception for unemployed wrestler', function () {
            // Arrange
            $wrestler = Wrestler::factory()->unemployed()->create();

            // Act & Assert
            expect(fn() => $this->strategy->validate($wrestler))
                ->toThrow(CannotBeRetiredException::class);
        });

        test('throws exception for wrestler with future employment', function () {
            // Arrange
            $wrestler = Wrestler::factory()->withFutureEmployment()->create();

            // Act & Assert
            expect(fn() => $this->strategy->validate($wrestler))
                ->toThrow(CannotBeRetiredException::class);
        });

        test('throws exception for already retired wrestler', function () {
            // Arrange
            $wrestler = Wrestler::factory()->retired()->create();

            // Act & Assert
            expect(fn() => $this->strategy->validate($wrestler))
                ->toThrow(CannotBeRetiredException::class);
        });
    });

    describe('method existence handling', function () {
        test('handles entity without hasFutureEmployment method', function () {
            // Arrange
            $mockEntity = new class extends Model {
                public function hasStatus(EmploymentStatus $status): bool {
                    return false; // Not unemployed
                }
                
                public function isRetired(): bool {
                    return false; // Not retired
                }
                
                // Note: No hasFutureEmployment method
            };

            // Act & Assert - Should not throw exception
            $this->strategy->validate($mockEntity);
            expect(true)->toBeTrue();
        });

        test('handles entity without isRetired method', function () {
            // Arrange
            $mockEntity = new class extends Model {
                public function hasStatus(EmploymentStatus $status): bool {
                    return false; // Not unemployed
                }
                
                public function hasFutureEmployment(): bool {
                    return false; // No future employment
                }
                
                // Note: No isRetired method
            };

            // Act & Assert - Should not throw exception
            $this->strategy->validate($mockEntity);
            expect(true)->toBeTrue();
        });

        test('handles entity without hasStatus method', function () {
            // Arrange
            $mockEntity = new class extends Model {
                // Note: No hasStatus method - should default to false for unemployment check
            };

            // Act & Assert - Should not throw exception
            $this->strategy->validate($mockEntity);
            expect(true)->toBeTrue();
        });
    });

    describe('business rule validation', function () {
        test('validates unemployment status correctly', function () {
            // Arrange
            $unemployedWrestler = Wrestler::factory()->unemployed()->create();
            $bookableWrestler = Wrestler::factory()->bookable()->create();

            // Act & Assert
            expect(fn() => $this->strategy->validate($unemployedWrestler))
                ->toThrow(CannotBeRetiredException::class);
            
            // Should not throw for bookable wrestler
            $this->strategy->validate($bookableWrestler);
            expect(true)->toBeTrue();
        });

        test('validates future employment correctly', function () {
            // Arrange
            $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
            $currentEmployedWrestler = Wrestler::factory()->bookable()->create();

            // Act & Assert
            expect(fn() => $this->strategy->validate($futureEmployedWrestler))
                ->toThrow(CannotBeRetiredException::class);
            
            // Should not throw for currently employed wrestler
            $this->strategy->validate($currentEmployedWrestler);
            expect(true)->toBeTrue();
        });

        test('validates retirement status correctly', function () {
            // Arrange
            $retiredWrestler = Wrestler::factory()->retired()->create();
            $activeWrestler = Wrestler::factory()->bookable()->create();

            // Act & Assert
            expect(fn() => $this->strategy->validate($retiredWrestler))
                ->toThrow(CannotBeRetiredException::class);
            
            // Should not throw for active wrestler
            $this->strategy->validate($activeWrestler);
            expect(true)->toBeTrue();
        });
    });
});