<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories\Titles;

use App\Enums\Shared\ActivationStatus;
use App\Models\Titles\Title;
use App\Models\Titles\TitleStatusChange;

/**
 * Unit tests for TitleStatusChangeFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (status change data)
 * - Factory state methods (active, inactive, retired, unactivated)
 * - Factory relationship creation (title associations)
 * - Status transition tracking and timestamping
 * - Business rule compliance for title status changes
 *
 * These tests verify that the TitleStatusChangeFactory generates consistent,
 * realistic status change data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Titles\TitleStatusChangeFactory
 */
describe('TitleStatusChangeFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates status change with correct default attributes', function () {
            // Arrange & Act
            $statusChange = TitleStatusChange::factory()->make();
            
            // Assert
            expect($statusChange->title_id)->toBeInt();
            expect($statusChange->status)->toBeInstanceOf(ActivationStatus::class);
            expect($statusChange->changed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
        });

        test('generates realistic status change timeline', function () {
            // Arrange & Act
            $statusChange = TitleStatusChange::factory()->make();
            
            // Assert
            expect($statusChange->changed_at->isPast())->toBeTrue();
            expect($statusChange->changed_at->isAfter(now()->subYear()))->toBeTrue();
        });

        test('creates valid activation status values', function () {
            // Arrange & Act
            $statusChanges = collect(range(1, 10))->map(fn() => TitleStatusChange::factory()->make());
            
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
            $title = Title::factory()->create();

            // Act
            $statusChange = TitleStatusChange::factory()->active()->make([
                'title_id' => $title->id,
            ]);
            
            // Assert
            expect($statusChange->title_id)->toBe($title->id);
            expect($statusChange->status)->toBe(ActivationStatus::Active);
        });

        test('inactive state works correctly', function () {
            // Arrange
            $title = Title::factory()->create();

            // Act
            $statusChange = TitleStatusChange::factory()->inactive()->make([
                'title_id' => $title->id,
            ]);
            
            // Assert
            expect($statusChange->title_id)->toBe($title->id);
            expect($statusChange->status)->toBe(ActivationStatus::Inactive);
        });

        test('retired state works correctly', function () {
            // Arrange
            $title = Title::factory()->create();

            // Act
            $statusChange = TitleStatusChange::factory()->retired()->make([
                'title_id' => $title->id,
            ]);
            
            // Assert
            expect($statusChange->title_id)->toBe($title->id);
            expect($statusChange->status)->toBe(ActivationStatus::Retired);
        });

        test('unactivated state works correctly', function () {
            // Arrange
            $title = Title::factory()->create();

            // Act
            $statusChange = TitleStatusChange::factory()->unactivated()->make([
                'title_id' => $title->id,
            ]);
            
            // Assert
            expect($statusChange->title_id)->toBe($title->id);
            expect($statusChange->status)->toBe(ActivationStatus::Unactivated);
        });
    });

    describe('factory customization', function () {
        test('accepts custom title association', function () {
            // Arrange
            $title = Title::factory()->create(['name' => 'Custom Championship']);

            // Act
            $statusChange = TitleStatusChange::factory()->make(['title_id' => $title->id]);
            
            // Assert
            expect($statusChange->title_id)->toBe($title->id);
        });

        test('accepts custom timestamp', function () {
            // Arrange
            $customDate = now()->subDays(30);

            // Act
            $statusChange = TitleStatusChange::factory()->make(['changed_at' => $customDate]);
            
            // Assert
            expect($statusChange->changed_at->format('Y-m-d H:i:s'))->toBe($customDate->format('Y-m-d H:i:s'));
        });

        test('accepts custom status override', function () {
            // Arrange & Act
            $statusChange = TitleStatusChange::factory()->make(['status' => ActivationStatus::Active]);
            
            // Assert
            expect($statusChange->status)->toBe(ActivationStatus::Active);
        });
    });

    describe('business rule compliance', function () {
        test('enforces chronological ordering for title status changes', function () {
            // Arrange
            $title = Title::factory()->create();
            $debutDate = now()->subDays(60);
            $retirementDate = now()->subDays(30);

            // Act
            $debutChange = TitleStatusChange::factory()->active()->make([
                'title_id' => $title->id,
                'changed_at' => $debutDate,
            ]);
            $retirementChange = TitleStatusChange::factory()->retired()->make([
                'title_id' => $title->id,
                'changed_at' => $retirementDate,
            ]);
            
            // Assert
            expect($debutChange->changed_at->isBefore($retirementChange->changed_at))->toBeTrue();
        });

        test('maintains title context throughout status changes', function () {
            // Arrange
            $title = Title::factory()->create();

            // Act
            $statusChanges = collect([
                TitleStatusChange::factory()->unactivated()->make(['title_id' => $title->id]),
                TitleStatusChange::factory()->active()->make(['title_id' => $title->id]),
                TitleStatusChange::factory()->inactive()->make(['title_id' => $title->id]),
                TitleStatusChange::factory()->retired()->make(['title_id' => $title->id]),
            ]);
            
            // Assert
            foreach ($statusChanges as $statusChange) {
                expect($statusChange->title_id)->toBe($title->id);
            }
        });

        test('supports title lifecycle tracking', function () {
            // Arrange
            $title = Title::factory()->create();

            // Act - Create realistic title status progression
            $creationChange = TitleStatusChange::factory()->unactivated()->make([
                'title_id' => $title->id,
                'changed_at' => now()->subDays(100),
            ]);
            $debutChange = TitleStatusChange::factory()->active()->make([
                'title_id' => $title->id,
                'changed_at' => now()->subDays(90),
            ]);
            $retirementChange = TitleStatusChange::factory()->retired()->make([
                'title_id' => $title->id,
                'changed_at' => now()->subDays(30),
            ]);
            
            // Assert
            expect($creationChange->status)->toBe(ActivationStatus::Unactivated);
            expect($debutChange->status)->toBe(ActivationStatus::Active);
            expect($retirementChange->status)->toBe(ActivationStatus::Retired);
            expect($creationChange->changed_at->isBefore($debutChange->changed_at))->toBeTrue();
            expect($debutChange->changed_at->isBefore($retirementChange->changed_at))->toBeTrue();
        });
    });

    describe('data consistency and integrity', function () {
        test('generates unique timestamps for different status changes', function () {
            // Arrange & Act
            $statusChange1 = TitleStatusChange::factory()->make();
            $statusChange2 = TitleStatusChange::factory()->make();
            
            // Assert - timestamps should be different (faker generates different values)
            expect($statusChange1->changed_at->format('Y-m-d H:i:s'))->not->toBe($statusChange2->changed_at->format('Y-m-d H:i:s'));
        });

        test('database creation works correctly', function () {
            // Arrange & Act
            $statusChange = TitleStatusChange::factory()->create();
            
            // Assert
            expect($statusChange->exists)->toBeTrue();
            expect($statusChange->id)->toBeGreaterThan(0);
        });

        test('maintains referential integrity with titles', function () {
            // Arrange
            $title = Title::factory()->create();

            // Act
            $statusChange = TitleStatusChange::factory()->create(['title_id' => $title->id]);
            
            // Assert
            expect($statusChange->title_id)->toBe($title->id);
            expect(Title::find($title->id))->not->toBeNull();
        });

        test('generates consistent data format across multiple instances', function () {
            // Arrange & Act
            $statusChanges = collect(range(1, 5))->map(fn() => TitleStatusChange::factory()->make());
            
            // Assert
            foreach ($statusChanges as $statusChange) {
                expect($statusChange->title_id)->toBeInt();
                expect($statusChange->status)->toBeInstanceOf(ActivationStatus::class);
                expect($statusChange->changed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
                expect($statusChange->changed_at->isPast())->toBeTrue();
            }
        });
    });
});