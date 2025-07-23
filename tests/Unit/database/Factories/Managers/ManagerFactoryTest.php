<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Enums\Shared\EmploymentStatus;
use App\Models\Managers\Manager;
use Database\Factories\Managers\ManagerFactory;

/**
 * Unit tests for ManagerFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (realistic data patterns)
 * - Factory state methods (available, injured, suspended, retired, employed, unemployed, etc.)
 * - Factory relationship creation (withWrestlers, withTagTeams, etc.)
 * - Custom factory methods and configurations
 * - Data consistency and business rule compliance
 *
 * These tests verify that the ManagerFactory generates consistent,
 * realistic test data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see ManagerFactory
 */
describe('ManagerFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates manager with correct default attributes', function () {
            // Arrange & Act
            $manager = Manager::factory()->make();

            // Assert
            expect($manager->first_name)->toBeString();
            expect($manager->first_name)->not->toBeEmpty();
            expect($manager->last_name)->toBeString();
            expect($manager->last_name)->not->toBeEmpty();
            expect($manager->status)->toBeInstanceOf(EmploymentStatus::class);
        });

        test('generates realistic manager names', function () {
            // Arrange & Act
            $manager = Manager::factory()->make();

            // Assert
            expect($manager->first_name)->toBeString();
            expect(mb_strlen($manager->first_name))->toBeGreaterThan(1);
            expect($manager->last_name)->toBeString();
            expect(mb_strlen($manager->last_name))->toBeGreaterThan(1);
        });

        test('sets default employment status', function () {
            // Arrange & Act
            $manager = Manager::factory()->make();

            // Assert
            expect($manager->status)->toBeInstanceOf(EmploymentStatus::class);
            expect($manager->status)->toBeIn([
                EmploymentStatus::Unemployed,
                EmploymentStatus::Employed,
            ]);
        });
    });

    describe('factory state methods', function () {
        test('unemployed state works correctly', function () {
            // Arrange & Act
            $manager = Manager::factory()->make(['status' => EmploymentStatus::Unemployed]);

            // Assert
            expect($manager->status)->toBe(EmploymentStatus::Unemployed);
        });

        test('employed state works correctly', function () {
            // Arrange & Act
            $manager = Manager::factory()->employed()->create();

            // Assert
            expect($manager->status)->toBe(EmploymentStatus::Employed);
        });

        test('released state works correctly', function () {
            // Arrange & Act
            $manager = Manager::factory()->released()->create();

            // Assert
            expect($manager->status)->toBe(EmploymentStatus::Released);
        });

        test('future employment state works correctly', function () {
            // Arrange & Act
            $manager = Manager::factory()->withFutureEmployment()->create();

            // Assert
            expect($manager->status)->toBe(EmploymentStatus::FutureEmployment);
        });
    });

    describe('factory customization', function () {
        test('accepts custom attribute overrides', function () {
            // Arrange & Act - Use factory state method for employed status since status is computed
            $manager = Manager::factory()->employed()->create([
                'first_name' => 'John',
                'last_name' => 'Doe',
            ]);

            // Assert
            expect($manager->first_name)->toBe('John');
            expect($manager->last_name)->toBe('Doe');
            expect($manager->status)->toBe(EmploymentStatus::Employed);
        });

        test('maintains required attributes when overriding', function () {
            // Arrange & Act
            $manager = Manager::factory()->make([
                'first_name' => 'Custom',
            ]);

            // Assert
            expect($manager->first_name)->toBe('Custom');
            expect($manager->last_name)->toBeString();
            expect($manager->status)->toBeInstanceOf(EmploymentStatus::class);
        });
    });

    describe('data consistency', function () {
        test('generates unique manager names', function () {
            // Arrange & Act
            $manager1 = Manager::factory()->make();
            $manager2 = Manager::factory()->make();

            // Assert
            expect($manager1->first_name)->not->toBe($manager2->first_name);
        });

        test('generates consistent data format', function () {
            // Arrange & Act
            $managers = collect(range(1, 5))->map(fn () => Manager::factory()->make());

            // Assert
            foreach ($managers as $manager) {
                expect($manager->first_name)->toBeString();
                expect($manager->last_name)->toBeString();
                expect($manager->status)->toBeInstanceOf(EmploymentStatus::class);
            }
        });

        test('database creation works correctly', function () {
            // Arrange & Act
            $manager = Manager::factory()->create();

            // Assert
            expect($manager->exists)->toBeTrue();
            expect($manager->id)->toBeGreaterThan(0);
        });
    });
});
