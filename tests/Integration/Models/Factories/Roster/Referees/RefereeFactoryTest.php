<?php

declare(strict_types=1);

namespace Tests\Integration\Models\Factories\Roster\Referees;

use App\Enums\Shared\EmploymentStatus;
use App\Models\Roster\Referees\Referee;
use Database\Factories\Roster\Referees\RefereeFactory;

/**
 * Integration tests for RefereeFactory data generation and state management.
 *
 * INTEGRATION TEST SCOPE:
 * - Factory default attribute generation (realistic data patterns)
 * - Factory state methods (employed, unemployed, etc.)
 * - Custom factory methods and configurations
 * - Data consistency and business rule compliance
 *
 * These tests verify that the RefereeFactory generates consistent,
 * realistic test data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see RefereeFactory
 */
describe('RefereeFactory Integration Tests', function () {
    describe('default attribute generation', function () {
        test('creates referee with correct default attributes', function () {
            // Arrange & Act
            $referee = Referee::factory()->make();

            // Assert
            expect($referee->first_name)->toBeString();
            expect($referee->first_name)->not->toBeEmpty();
            expect($referee->last_name)->toBeString();
            expect($referee->last_name)->not->toBeEmpty();
            expect($referee->status)->toBeInstanceOf(EmploymentStatus::class);
        });

        test('generates realistic referee names', function () {
            // Arrange & Act
            $referee = Referee::factory()->make();

            // Assert
            expect($referee->first_name)->toBeString();
            expect(mb_strlen($referee->first_name))->toBeGreaterThan(1);
            expect($referee->last_name)->toBeString();
            expect(mb_strlen($referee->last_name))->toBeGreaterThan(1);
        });
    });

    describe('factory state methods', function () {
        test('unemployed state works correctly', function () {
            // Arrange & Act
            $referee = Referee::factory()->make(['status' => EmploymentStatus::Unemployed]);

            // Assert
            expect($referee->status)->toBe(EmploymentStatus::Unemployed);
        });

        test('employed state works correctly', function () {
            // Arrange & Act - Use factory state method for employed status since status is computed
            // Note: Employment status requires persisted relationships, so use create() instead of make()
            $referee = Referee::factory()->employed()->create();

            // Assert
            expect($referee->status)->toBe(EmploymentStatus::Employed);
        });

        test('suspended state creates exactly one active employment', function () {
            $referee = Referee::factory()->suspended()->create();

            expect($referee->currentSuspension()->exists())->toBeTrue();
            expect($referee->currentEmployment()->exists())->toBeTrue();
            expect($referee->employments()->whereNull('ended_at')->count())->toBe(1);
        });

        test('injured state creates exactly one active employment', function () {
            $referee = Referee::factory()->injured()->create();

            expect($referee->currentInjury()->exists())->toBeTrue();
            expect($referee->currentEmployment()->exists())->toBeTrue();
            expect($referee->employments()->whereNull('ended_at')->count())->toBe(1);
        });
    });

    describe('factory customization', function () {
        test('accepts custom attribute overrides', function () {
            // Arrange & Act - Use factory state method for employed status since status is computed
            // Note: Employment status requires persisted relationships, so use create() instead of make()
            $referee = Referee::factory()->employed()->create([
                'first_name' => 'John',
                'last_name' => 'Doe',
            ]);

            // Assert
            expect($referee->first_name)->toBe('John');
            expect($referee->last_name)->toBe('Doe');
            expect($referee->status)->toBe(EmploymentStatus::Employed);
        });
    });

    describe('data consistency', function () {
        test('generates unique referee names', function () {
            // Arrange & Act
            $referee1 = Referee::factory()->make();
            $referee2 = Referee::factory()->make();

            // Assert
            expect($referee1->first_name)->not->toBe($referee2->first_name);
        });

        test('database creation works correctly', function () {
            // Arrange & Act
            $referee = Referee::factory()->create();

            // Assert
            expect($referee->exists)->toBeTrue();
            expect($referee->id)->toBeGreaterThan(0);
        });
    });
});
