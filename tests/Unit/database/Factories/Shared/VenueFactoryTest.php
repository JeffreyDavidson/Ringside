<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\Shared\Venue;

/**
 * Unit tests for VenueFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (realistic data patterns)
 * - Factory state methods and configurations
 * - Custom factory methods and overrides
 * - Data consistency and business rule compliance
 *
 * These tests verify that the VenueFactory generates consistent,
 * realistic test data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Shared\VenueFactory
 */
describe('VenueFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates venue with correct default attributes', function () {
            // Arrange & Act
            $venue = Venue::factory()->make();
            
            // Assert
            expect($venue->name)->toBeString();
            expect($venue->name)->toContain('Arena');
            expect($venue->street_address)->toBeString();
            expect($venue->city)->toBeString();
            expect($venue->state)->toBeString();
            expect($venue->zipcode)->toBeString();
            expect(mb_strlen($venue->zipcode))->toBe(5);
        });

        test('generates realistic venue names', function () {
            // Arrange & Act
            $venue = Venue::factory()->make();
            
            // Assert
            expect($venue->name)->toBeString();
            expect(strlen($venue->name))->toBeGreaterThan(3);
            expect($venue->name)->toContain('Arena');
        });

        test('generates valid address components', function () {
            // Arrange & Act
            $venue = Venue::factory()->make();
            
            // Assert
            expect($venue->street_address)->toBeString();
            expect(strlen($venue->street_address))->toBeGreaterThan(5);
            expect($venue->city)->toBeString();
            expect(strlen($venue->city))->toBeGreaterThan(2);
            expect($venue->state)->toBeString();
            expect(strlen($venue->state))->toBe(2);
            expect($venue->zipcode)->toBeString();
            expect(strlen($venue->zipcode))->toBe(5);
        });
    });

    describe('factory customization', function () {
        test('accepts custom attribute overrides', function () {
            // Arrange & Act
            $venue = Venue::factory()->make([
                'name' => 'Custom Arena',
                'city' => 'Custom City',
                'state' => 'CC',
            ]);
            
            // Assert
            expect($venue->name)->toBe('Custom Arena');
            expect($venue->city)->toBe('Custom City');
            expect($venue->state)->toBe('CC');
        });

        test('maintains required attributes when overriding', function () {
            // Arrange & Act
            $venue = Venue::factory()->make([
                'name' => 'Override Arena',
            ]);
            
            // Assert
            expect($venue->name)->toBe('Override Arena');
            expect($venue->street_address)->toBeString();
            expect($venue->city)->toBeString();
            expect($venue->state)->toBeString();
            expect($venue->zipcode)->toBeString();
        });
    });

    describe('data consistency', function () {
        test('generates unique venue names', function () {
            // Arrange & Act
            $venue1 = Venue::factory()->make();
            $venue2 = Venue::factory()->make();
            
            // Assert
            expect($venue1->name)->not->toBe($venue2->name);
        });

        test('generates consistent data format', function () {
            // Arrange & Act
            $venues = collect(range(1, 5))->map(fn() => Venue::factory()->make());
            
            // Assert
            foreach ($venues as $venue) {
                expect($venue->name)->toBeString();
                expect($venue->zipcode)->toMatch('/^\d{5}$/');
                expect($venue->state)->toMatch('/^[A-Z]{2}$/');
            }
        });
    });
});
