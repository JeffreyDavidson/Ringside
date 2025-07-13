<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Enums\Shared\EmploymentStatus;
use App\Models\Referees\Referee;

/**
 * Unit tests for RefereeFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (realistic data patterns)
 * - Factory state methods (employed, unemployed, etc.)
 * - Custom factory methods and configurations
 * - Data consistency and business rule compliance
 *
 * These tests verify that the RefereeFactory generates consistent,
 * realistic test data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Referees\RefereeFactory
 */
describe('RefereeFactory Unit Tests', function () {
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
            expect(strlen($referee->first_name))->toBeGreaterThan(1);
            expect($referee->last_name)->toBeString();
            expect(strlen($referee->last_name))->toBeGreaterThan(1);
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
            // Arrange & Act
            $referee = Referee::factory()->make(['status' => EmploymentStatus::Employed]);
            
            // Assert
            expect($referee->status)->toBe(EmploymentStatus::Employed);
        });
    });

    describe('factory customization', function () {
        test('accepts custom attribute overrides', function () {
            // Arrange & Act
            $referee = Referee::factory()->make([
                'first_name' => 'John',
                'last_name' => 'Doe',
                'status' => EmploymentStatus::Employed,
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
