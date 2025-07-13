<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories\Stables;

use App\Enums\Shared\ActivationStatus;
use App\Models\Stables\Stable;
use App\Models\Stables\StableStatusChange;

/**
 * Unit tests for StableStatusChangeFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (status change data)
 * - Factory state methods (active, inactive, retired, unactivated)
 * - Factory relationship creation (stable associations)
 * - Status transition tracking and timestamping
 * - Business rule compliance for status changes
 *
 * These tests verify that the StableStatusChangeFactory generates consistent,
 * realistic status change data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Stables\StableStatusChangeFactory
 */
describe('StableStatusChangeFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates status change with correct default attributes', function () {
            // Arrange & Act
            $statusChange = StableStatusChange::factory()->make();
            
            // Assert
            expect($statusChange->stable_id)->toBeInt();
            expect($statusChange->status)->toBeInstanceOf(ActivationStatus::class);
            expect($statusChange->changed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
        });

        test('generates realistic status change timeline', function () {
            // Arrange & Act
            $statusChange = StableStatusChange::factory()->make();
            
            // Assert
            expect($statusChange->changed_at->isPast())->toBeTrue();
            expect($statusChange->changed_at->isAfter(now()->subYear()))->toBeTrue();
        });

        test('creates valid activation status values', function () {
            // Arrange & Act
            $statusChanges = collect(range(1, 10))->map(fn() => StableStatusChange::factory()->make());
            
            // Assert
            foreach ($statusChanges as $statusChange) {
                expect($statusChange->status)->toBeInstanceOf(ActivationStatus::class);
                expect($statusChange->status)->toBeIn(ActivationStatus::cases());
            }
        });
    });

    describe('factory state methods', function () {
        test('active state works correctly', function () {
            // Arrange
            $stable = Stable::factory()->create();

            // Act
            $statusChange = StableStatusChange::factory()->active()->make([
                'stable_id' => $stable->id,
            ]);
            
            // Assert
            expect($statusChange->stable_id)->toBe($stable->id);
            expect($statusChange->status)->toBe(ActivationStatus::Active);
        });

        test('inactive state works correctly', function () {
            // Arrange
            $stable = Stable::factory()->create();

            // Act
            $statusChange = StableStatusChange::factory()->inactive()->make([
                'stable_id' => $stable->id,
            ]);
            
            // Assert
            expect($statusChange->stable_id)->toBe($stable->id);
            expect($statusChange->status)->toBe(ActivationStatus::Inactive);
        });

        test('retired state works correctly', function () {
            // Arrange
            $stable = Stable::factory()->create();

            // Act
            $statusChange = StableStatusChange::factory()->retired()->make([
                'stable_id' => $stable->id,
            ]);
            
            // Assert
            expect($statusChange->stable_id)->toBe($stable->id);
            expect($statusChange->status)->toBe(ActivationStatus::Retired);
        });

        test('unactivated state works correctly', function () {
            // Arrange
            $stable = Stable::factory()->create();

            // Act
            $statusChange = StableStatusChange::factory()->unactivated()->make([
                'stable_id' => $stable->id,
            ]);
            
            // Assert
            expect($statusChange->stable_id)->toBe($stable->id);
            expect($statusChange->status)->toBe(ActivationStatus::Unactivated);
        });
    });

    describe('factory customization', function () {
        test('accepts custom stable association', function () {
            // Arrange
            $stable = Stable::factory()->create(['name' => 'Custom Stable']);

            // Act
            $statusChange = StableStatusChange::factory()->make(['stable_id' => $stable->id]);
            
            // Assert
            expect($statusChange->stable_id)->toBe($stable->id);
        });

        test('accepts custom timestamp', function () {
            // Arrange
            $customDate = now()->subDays(30);

            // Act
            $statusChange = StableStatusChange::factory()->make(['changed_at' => $customDate]);
            
            // Assert
            expect($statusChange->changed_at->format('Y-m-d H:i:s'))->toBe($customDate->format('Y-m-d H:i:s'));
        });

        test('accepts custom status override', function () {
            // Arrange & Act
            $statusChange = StableStatusChange::factory()->make(['status' => ActivationStatus::Active]);
            
            // Assert
            expect($statusChange->status)->toBe(ActivationStatus::Active);
        });
    });

    describe('business rule compliance', function () {
        test('enforces chronological ordering for status changes', function () {
            // Arrange
            $stable = Stable::factory()->create();
            $earlierDate = now()->subDays(60);
            $laterDate = now()->subDays(30);

            // Act
            $earlierChange = StableStatusChange::factory()->make([
                'stable_id' => $stable->id,
                'changed_at' => $earlierDate,
            ]);
            $laterChange = StableStatusChange::factory()->make([
                'stable_id' => $stable->id,
                'changed_at' => $laterDate,
            ]);
            
            // Assert
            expect($earlierChange->changed_at->isBefore($laterChange->changed_at))->toBeTrue();
        });

        test('maintains stable context throughout status changes', function () {
            // Arrange
            $stable = Stable::factory()->create();

            // Act
            $statusChanges = collect([
                StableStatusChange::factory()->unactivated()->make(['stable_id' => $stable->id]),
                StableStatusChange::factory()->active()->make(['stable_id' => $stable->id]),
                StableStatusChange::factory()->inactive()->make(['stable_id' => $stable->id]),
                StableStatusChange::factory()->retired()->make(['stable_id' => $stable->id]),
            ]);
            
            // Assert
            foreach ($statusChanges as $statusChange) {
                expect($statusChange->stable_id)->toBe($stable->id);
            }
        });
    });

    describe('data consistency and integrity', function () {
        test('generates unique timestamps for different status changes', function () {
            // Arrange & Act
            $statusChange1 = StableStatusChange::factory()->make();
            $statusChange2 = StableStatusChange::factory()->make();
            
            // Assert - timestamps should be different (faker generates different values)
            expect($statusChange1->changed_at->format('Y-m-d H:i:s'))->not->toBe($statusChange2->changed_at->format('Y-m-d H:i:s'));
        });

        test('database creation works correctly', function () {
            // Arrange & Act
            $statusChange = StableStatusChange::factory()->create();
            
            // Assert
            expect($statusChange->exists)->toBeTrue();
            expect($statusChange->id)->toBeGreaterThan(0);
        });

        test('maintains referential integrity with stables', function () {
            // Arrange
            $stable = Stable::factory()->create();

            // Act
            $statusChange = StableStatusChange::factory()->create(['stable_id' => $stable->id]);
            
            // Assert
            expect($statusChange->stable_id)->toBe($stable->id);
            expect(Stable::find($stable->id))->not->toBeNull();
        });

        test('generates consistent data format across multiple instances', function () {
            // Arrange & Act
            $statusChanges = collect(range(1, 5))->map(fn() => StableStatusChange::factory()->make());
            
            // Assert
            foreach ($statusChanges as $statusChange) {
                expect($statusChange->stable_id)->toBeInt();
                expect($statusChange->status)->toBeInstanceOf(ActivationStatus::class);
                expect($statusChange->changed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
                expect($statusChange->changed_at->isPast())->toBeTrue();
            }
        });
    });
});