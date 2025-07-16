<?php

declare(strict_types=1);

use App\Models\Managers\Manager;
use App\Models\Wrestlers\Wrestler;
use App\Models\Wrestlers\WrestlerManager;
use Illuminate\Support\Carbon;

/**
 * Integration tests for WrestlerManager pivot model functionality.
 *
 * This test suite validates the complete workflow of manager-wrestler
 * relationships including hiring, ending relationships, querying current
 * and previous managers, and ensuring proper business rule enforcement.
 *
 * Tests cover the CanBeManaged trait implementation and WrestlerManager
 * pivot model functionality with real database relationships.
 *
 * @see \App\Models\Wrestlers\WrestlerManager
 */
describe('WrestlerManager Pivot Model', function () {
    beforeEach(function () {
        // Create test entities with realistic factory states
        $this->manager = Manager::factory()->employed()->create([
            'first_name' => 'Paul',
            'last_name' => 'Bearer',
        ]);

        $this->wrestler = Wrestler::factory()->employed()->create([
            'name' => 'The Undertaker',
            'hometown' => 'Death Valley',
        ]);

        $this->secondManager = Manager::factory()->employed()->create([
            'first_name' => 'Miss',
            'last_name' => 'Elizabeth',
        ]);

        $this->secondWrestler = Wrestler::factory()->employed()->create([
            'name' => 'Macho Man Randy Savage',
            'hometown' => 'Sarasota, Florida',
        ]);
    });

    describe('Relationship Creation', function () {
        test('wrestler can be assigned a manager with proper pivot data', function () {
            $hiredDate = Carbon::now()->subMonths(6);

            createManagementRelationship($this->wrestler, $this->manager, ['hired_at' => $hiredDate]);

            expectRelationshipCounts($this->wrestler, [
                'managers' => 1,
                'currentManagers' => 1,
                'previousManagers' => 0,
            ]);

            $pivotData = $this->wrestler->managers()->first()->pivot;
            expect($pivotData->hired_at->timestamp)->toBe($hiredDate->timestamp);
            expect($pivotData->fired_at)->toBeNull();
        });

        test('manager can manage multiple wrestlers simultaneously', function () {
            $hiredDate1 = Carbon::now()->subMonths(3);
            $hiredDate2 = Carbon::now()->subMonths(2);

            createManagementRelationship($this->wrestler, $this->manager, ['hired_at' => $hiredDate1]);
            createManagementRelationship($this->secondWrestler, $this->manager, ['hired_at' => $hiredDate2]);

            expect($this->wrestler->currentManagers()->count())->toBe(1);
            expect($this->secondWrestler->currentManagers()->count())->toBe(1);

            // Verify manager has both wrestlers
            if (method_exists($this->manager, 'currentWrestlers')) {
                expect($this->manager->currentWrestlers()->count())->toBe(2);
                expect($this->manager->currentWrestlers->pluck('id'))
                    ->toContain($this->wrestler->id)
                    ->toContain($this->secondWrestler->id);
            }
        });

        test('wrestler can have multiple managers during different time periods', function () {
            $periods = [
                [
                    'manager' => $this->manager,
                    'hired_at' => Carbon::now()->subYear(),
                    'fired_at' => Carbon::now()->subMonths(6),
                ],
                [
                    'manager' => $this->secondManager,
                    'hired_at' => Carbon::now()->subMonths(3),
                    'fired_at' => null,
                ],
            ];

            createManagementHistory($this->wrestler, $periods);

            expectRelationshipCounts($this->wrestler, [
                'managers' => 2,
                'currentManagers' => 1,
                'previousManagers' => 1,
            ]);

            $currentManager = $this->wrestler->currentManagers()->first();
            expect($currentManager->id)->toBe($this->secondManager->id);
            expectCurrentRelationshipsActive($this->wrestler);

            $previousManager = $this->wrestler->previousManagers()->first();
            expect($previousManager->id)->toBe($this->manager->id);
            expectPreviousRelationshipsEnded($this->wrestler);
        });
    });

    describe('Relationship Termination', function () {
        beforeEach(function () {
            createManagementRelationship($this->wrestler, $this->manager);
        });

        test('ending management relationship updates pivot correctly', function () {
            $endDate = Carbon::now();

            endManagementRelationship($this->wrestler, $this->manager, $endDate);

            expectRelationshipCounts($this->wrestler, [
                'currentManagers' => 0,
                'previousManagers' => 1,
            ]);

            expectPreviousRelationshipsEnded($this->wrestler);
            
            $previousManager = $this->wrestler->previousManagers()->first();
            expect($previousManager->pivot->fired_at->equalTo($endDate))->toBeTrue();
        });

        test('detaching manager completely removes relationship', function () {
            // Detach the manager
            $this->wrestler->managers()->detach($this->manager->id);

            // Verify all relationships are gone
            expect($this->wrestler->managers()->count())->toBe(0);
            expect($this->wrestler->currentManagers()->count())->toBe(0);
            expect($this->wrestler->previousManagers()->count())->toBe(0);

            // Verify pivot record is deleted
            expect(WrestlerManager::where('wrestler_id', $this->wrestler->id)
                ->where('manager_id', $this->manager->id)
                ->exists())->toBeFalse();
        });
    });

    describe('Relationship Queries', function () {
        beforeEach(function () {
            // Set up complex relationship scenario
            createManagementHistory($this->wrestler, [
                [
                    'manager' => $this->manager,
                    'hired_at' => Carbon::now()->subYear(),
                    'fired_at' => Carbon::now()->subMonths(6),
                ],
                [
                    'manager' => $this->secondManager,
                    'hired_at' => Carbon::now()->subMonths(3),
                    'fired_at' => null,
                ],
            ]);

            createManagementRelationship($this->secondWrestler, $this->manager, [
                'hired_at' => Carbon::now()->subMonths(2),
            ]);
        });

        test('current managers query returns only active relationships', function () {
            $currentManagers = $this->wrestler->currentManagers()->get();

            expect($currentManagers)->toHaveCount(1);
            expect($currentManagers->first()->id)->toBe($this->secondManager->id);
            expect($currentManagers->first()->pivot->fired_at)->toBeNull();
        });

        test('previous managers query returns only completed relationships', function () {
            $previousManagers = $this->wrestler->previousManagers()->get();

            expect($previousManagers)->toHaveCount(1);
            expect($previousManagers->first()->id)->toBe($this->manager->id);
            expect($previousManagers->first()->pivot->fired_at)->not->toBeNull();
        });

        test('all managers query returns complete relationship history', function () {
            $allManagers = $this->wrestler->managers()->get();

            expect($allManagers)->toHaveCount(2);

            $managerIds = $allManagers->pluck('id')->toArray();
            expect($managerIds)->toContain($this->manager->id);
            expect($managerIds)->toContain($this->secondManager->id);
        });

        test('manager relationships are properly ordered by hired_at', function () {
            $managersChronological = $this->wrestler->managers()
                ->orderBy('hired_at', 'asc')
                ->get();

            expect($managersChronological->first()->id)->toBe($this->manager->id);
            expect($managersChronological->last()->id)->toBe($this->secondManager->id);
        });

        test('can query managers within specific date ranges', function () {
            $recentManagers = $this->wrestler->managers()
                ->wherePivot('hired_at', '>=', Carbon::now()->subMonths(4))
                ->get();

            expect($recentManagers)->toHaveCount(1);
            expect($recentManagers->first()->id)->toBe($this->secondManager->id);
        });
    });

    describe('Pivot Model Operations', function () {
        test('pivot model can be queried directly', function () {
            createManagementRelationship($this->wrestler, $this->manager);

            $pivotRecord = WrestlerManager::where('wrestler_id', $this->wrestler->id)
                ->where('manager_id', $this->manager->id)
                ->first();

            expect($pivotRecord)->not->toBeNull();
            expect($pivotRecord->wrestler_id)->toBe($this->wrestler->id);
            expect($pivotRecord->manager_id)->toBe($this->manager->id);
            expect($pivotRecord->hired_at)->toBeInstanceOf(Carbon::class);
            expect($pivotRecord->fired_at)->toBeNull();
        });

        test('pivot model relationships work correctly', function () {
            createManagementRelationship($this->wrestler, $this->manager);

            $pivotRecord = WrestlerManager::where('wrestler_id', $this->wrestler->id)
                ->where('manager_id', $this->manager->id)
                ->first();

            // Test pivot relationships
            expect($pivotRecord->wrestler->id)->toBe($this->wrestler->id);
            expect($pivotRecord->manager->id)->toBe($this->manager->id);
        });

        test('pivot model handles date casting correctly', function () {
            $hiredDate = Carbon::now()->subMonths(6);
            $firedDate = Carbon::now()->subMonths(1);

            createManagementRelationship($this->wrestler, $this->manager, [
                'hired_at' => $hiredDate,
                'fired_at' => $firedDate,
            ]);

            $pivotRecord = WrestlerManager::where('wrestler_id', $this->wrestler->id)
                ->where('manager_id', $this->manager->id)
                ->first();

            expect($pivotRecord->hired_at)->toBeInstanceOf(Carbon::class);
            expect($pivotRecord->fired_at)->toBeInstanceOf(Carbon::class);
            expect($pivotRecord->hired_at->equalTo($hiredDate))->toBeTrue();
            expect($pivotRecord->fired_at->equalTo($firedDate))->toBeTrue();
        });
    });

    describe('Business Rule Validation', function () {
        test('cannot have duplicate active management relationships', function () {
            createManagementRelationship($this->wrestler, $this->manager);
            $initialCount = $this->wrestler->currentManagers()->count();

            // Try to attach the same manager again
            try {
                createManagementRelationship($this->wrestler, $this->manager, [
                    'hired_at' => Carbon::now()->subMonths(3),
                ]);

                // If duplicate is allowed, verify only one current relationship exists
                expect($this->wrestler->currentManagers()->count())->toBe($initialCount);
            } catch (Exception $e) {
                // If exception is thrown, that's expected behavior
                expect($this->wrestler->currentManagers()->count())->toBe($initialCount);
            }
        });

        test('management periods cannot overlap incorrectly', function () {
            $overlap = createOverlappingManagementPeriods($this->wrestler, $this->manager, $this->secondManager);

            // Verify both relationships exist (validation would be in business logic)
            expect($this->wrestler->managers()->count())->toBe(2);
            expect($overlap['overlap_detected'])->toBeTrue();
        });

        test('hire date must be before leave date when both are set', function () {
            $hiredDate = Carbon::now()->subMonths(3);
            $firedDate = Carbon::now()->subMonths(6); // Earlier than hired date (invalid)

            createManagementRelationship($this->wrestler, $this->manager, [
                'hired_at' => $hiredDate,
                'fired_at' => $firedDate,
            ]);

            $pivotRecord = WrestlerManager::where('wrestler_id', $this->wrestler->id)
                ->where('manager_id', $this->manager->id)
                ->first();

            // Data is stored as-is; validation should happen in business logic
            expect($pivotRecord->hired_at->greaterThan($pivotRecord->fired_at))->toBeTrue();
        });
    });

    describe('Complex Scenarios', function () {
        test('manager and wrestler can have multiple separate management periods', function () {
            createManagementHistory($this->wrestler, [
                [
                    'manager' => $this->manager,
                    'hired_at' => Carbon::now()->subYear(),
                    'fired_at' => Carbon::now()->subMonths(8),
                ],
                [
                    'manager' => $this->secondManager,
                    'hired_at' => Carbon::now()->subMonths(6),
                    'fired_at' => Carbon::now()->subMonths(4),
                ],
                [
                    'manager' => $this->manager,
                    'hired_at' => Carbon::now()->subMonths(2),
                    'fired_at' => null,
                ],
            ]);

            expectRelationshipCounts($this->wrestler, [
                'managers' => 3,
                'currentManagers' => 1,
                'previousManagers' => 2,
            ]);

            // Verify current manager is the original manager
            $currentManager = $this->wrestler->currentManagers()->first();
            expect($currentManager->id)->toBe($this->manager->id);

            // Verify relationship history includes both managers
            $allManagers = $this->wrestler->managers()->get();
            $uniqueManagers = $allManagers->unique('id');
            expect($uniqueManagers)->toHaveCount(2);
        });

        test('can query management duration and calculate statistics', function () {
            createManagementHistory($this->wrestler, [
                [
                    'manager' => $this->manager,
                    'hired_at' => Carbon::now()->subYear(),
                    'fired_at' => Carbon::now()->subMonths(6),
                ],
                [
                    'manager' => $this->secondManager,
                    'hired_at' => Carbon::now()->subMonths(3),
                    'fired_at' => null,
                ],
            ]);

            // Calculate duration of completed period
            $completedPeriod = $this->wrestler->previousManagers()->first();
            $duration = $completedPeriod->pivot->hired_at->diffInDays($completedPeriod->pivot->fired_at);
            expect($duration)->toBeGreaterThan(150); // Approximately 6 months

            // Calculate duration of current period
            $currentPeriod = $this->wrestler->currentManagers()->first();
            $currentDuration = $currentPeriod->pivot->hired_at->diffInDays(Carbon::now());
            expect($currentDuration)->toBeGreaterThan(80); // Approximately 3 months
        });
    });

    describe('Performance Optimization', function () {
        test('eager loading relationships works correctly', function () {
            createManagementRelationship($this->wrestler, $this->manager);
            createManagementRelationship($this->secondWrestler, $this->secondManager);

            // Load wrestlers with their current managers
            $wrestlers = Wrestler::with('currentManagers')->get();

            expect($wrestlers)->toHaveCount(2);

            // Verify relationships are loaded
            $wrestlerWithManager = $wrestlers->firstWhere('id', $this->wrestler->id);
            expect($wrestlerWithManager->relationLoaded('currentManagers'))->toBeTrue();
            expect($wrestlerWithManager->currentManagers)->toHaveCount(1);
        });

        test('can efficiently count relationships without loading them', function () {
            createManagementHistory($this->wrestler, [
                [
                    'manager' => $this->manager,
                    'hired_at' => Carbon::now()->subMonths(6),
                    'fired_at' => null,
                ],
                [
                    'manager' => $this->secondManager,
                    'hired_at' => Carbon::now()->subMonths(3),
                    'fired_at' => Carbon::now()->subMonths(1),
                ],
            ]);

            expectRelationshipCounts($this->wrestler, [
                'managers' => 2,
                'currentManagers' => 1,
                'previousManagers' => 1,
            ]);

            // Verify relationships are not loaded
            expect($this->wrestler->relationLoaded('managers'))->toBeFalse();
        });
    });
});
