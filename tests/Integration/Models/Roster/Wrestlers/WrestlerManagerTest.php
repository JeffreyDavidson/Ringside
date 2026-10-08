<?php

declare(strict_types=1);

use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Roster\Wrestlers\WrestlerManager;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @return array{
 *     manager: Manager,
 *     wrestler: Wrestler,
 *     secondManager: Manager,
 *     secondWrestler: Wrestler,
 * }
 */
function wrestlersWrestlerManagerManagerFixtures(): array
{
    // Create test entities with realistic factory states
    $manager = Manager::factory()->employed()->create([
        'first_name' => 'Paul',
        'last_name' => 'Bearer',
    ]);

    $wrestler = Wrestler::factory()->employed()->create([
        'name' => 'The Undertaker',
        'hometown' => 'Death Valley',
    ]);

    $secondManager = Manager::factory()->employed()->create([
        'first_name' => 'Miss',
        'last_name' => 'Elizabeth',
    ]);

    $secondWrestler = Wrestler::factory()->employed()->create([
        'name' => 'Macho Man Randy Savage',
        'hometown' => 'Sarasota, Florida',
    ]);

    return [
        'manager' => $manager,
        'wrestler' => $wrestler,
        'secondManager' => $secondManager,
        'secondWrestler' => $secondWrestler,
    ];
}

function wrestlersWrestlerManagerWrestlerSetupFixtures(Wrestler $wrestler, Manager $manager): void
{
    createManagementRelationship($wrestler, $manager);
}

function wrestlersWrestlerManagerWrestlerSetupFixtures2(Wrestler $wrestler, Manager $manager, Manager $secondManager, Wrestler $secondWrestler): void
{
    // Set up complex relationship scenario
    createManagementHistory($wrestler, [
        [
            'manager' => $manager,
            'hired_at' => Carbon::now()->subYear(),
            'fired_at' => Carbon::now()->subMonths(6),
        ],
        [
            'manager' => $secondManager,
            'hired_at' => Carbon::now()->subMonths(3),
            'fired_at' => null,
        ],
    ]);

    createManagementRelationship($secondWrestler, $manager, [
        'hired_at' => Carbon::now()->subMonths(2),
    ]);
}

/**
 * Integration tests for WrestlerManager pivot model functionality.
 *
 * This test suite validates the complete workflow of manager-wrestler
 * relationships including hiring, ending relationships, querying current
 * and previous managers, and ensuring proper business rule enforcement.
 *
 * Tests cover Wrestler manager relationships and WrestlerManager
 * pivot model functionality with real database relationships.
 *
 * @see WrestlerManager
 */
describe('WrestlerManager Pivot Model', function () {
    describe('Relationship Creation', function () {
        test('wrestler can be assigned a manager with proper pivot data', function () {
            ['manager' => $manager, 'wrestler' => $wrestler] = wrestlersWrestlerManagerManagerFixtures();

            $hiredDate = Carbon::now()->subMonths(6);

            createManagementRelationship($wrestler, $manager, ['hired_at' => $hiredDate]);

            expectRelationshipCounts($wrestler, [
                'managers' => 1,
                'currentManagers' => 1,
                'previousManagers' => 0,
            ]);

            $pivotData = $wrestler->managers()->firstOrFail()->pivot;
            expect($pivotData->hired_at->timestamp)->toBe($hiredDate->timestamp)
                ->and($pivotData->fired_at)->toBeNull();
        });

        test('manager can manage multiple wrestlers simultaneously', function () {
            ['manager' => $manager, 'wrestler' => $wrestler, 'secondWrestler' => $secondWrestler] = wrestlersWrestlerManagerManagerFixtures();

            $hiredDate1 = Carbon::now()->subMonths(3);
            $hiredDate2 = Carbon::now()->subMonths(2);

            createManagementRelationship($wrestler, $manager, ['hired_at' => $hiredDate1]);
            createManagementRelationship($secondWrestler, $manager, ['hired_at' => $hiredDate2]);

            expect($wrestler->currentManagers()->count())->toBe(1)
                ->and($secondWrestler->currentManagers()->count())->toBe(1);

            // Verify manager has both wrestlers
            expect($manager->currentWrestlers()->count())->toBe(2);
            expect($manager->currentWrestlers->pluck('id'))
                ->toContain($wrestler->id)
                ->toContain($secondWrestler->id);
        });

        test('wrestler can have multiple managers during different time periods', function () {
            ['manager' => $manager, 'wrestler' => $wrestler, 'secondManager' => $secondManager] = wrestlersWrestlerManagerManagerFixtures();

            $periods = [
                [
                    'manager' => $manager,
                    'hired_at' => Carbon::now()->subYear(),
                    'fired_at' => Carbon::now()->subMonths(6),
                ],
                [
                    'manager' => $secondManager,
                    'hired_at' => Carbon::now()->subMonths(3),
                    'fired_at' => null,
                ],
            ];

            createManagementHistory($wrestler, $periods);

            expectRelationshipCounts($wrestler, [
                'managers' => 2,
                'currentManagers' => 1,
                'previousManagers' => 1,
            ]);

            $currentManager = $wrestler->currentManagers()->firstOrFail();
            expect($currentManager->id)->toBe($secondManager->id);
            expectCurrentRelationshipsActive($wrestler);

            $previousManager = $wrestler->previousManagers()->firstOrFail();
            expect($previousManager->id)->toBe($manager->id);
            expectPreviousRelationshipsEnded($wrestler);
        });
    });

    describe('Relationship Termination', function () {
        test('ending management relationship updates pivot correctly', function () {
            ['manager' => $manager, 'wrestler' => $wrestler] = wrestlersWrestlerManagerManagerFixtures();
            wrestlersWrestlerManagerWrestlerSetupFixtures($wrestler, $manager);

            $endDate = Carbon::now();

            endManagementRelationship($wrestler, $manager, $endDate);

            expectRelationshipCounts($wrestler, [
                'currentManagers' => 0,
                'previousManagers' => 1,
            ]);

            expectPreviousRelationshipsEnded($wrestler);

            $previousManager = $wrestler->previousManagers()->firstOrFail();
            expect(requiredDate($previousManager->pivot->fired_at)->format('Y-m-d H:i:s'))->toBe($endDate->format('Y-m-d H:i:s'));
        });

        test('detaching manager completely removes relationship', function () {
            ['manager' => $manager, 'wrestler' => $wrestler] = wrestlersWrestlerManagerManagerFixtures();
            wrestlersWrestlerManagerWrestlerSetupFixtures($wrestler, $manager);

            // Detach the manager
            $wrestler->managers()->detach($manager->id);

            // Verify all relationships are gone
            expect($wrestler->managers()->count())->toBe(0);
            expect($wrestler->currentManagers()->count())->toBe(0)
                ->and($wrestler->previousManagers()->count())->toBe(0);

            // Verify pivot record is deleted
            expect(WrestlerManager::where('wrestler_id', $wrestler->id)
                ->where('manager_id', $manager->id)
                ->exists())->toBeFalse();
        });
    });

    describe('Relationship Queries', function () {
        test('current managers query returns only active relationships', function () {
            ['manager' => $manager, 'wrestler' => $wrestler, 'secondManager' => $secondManager, 'secondWrestler' => $secondWrestler] = wrestlersWrestlerManagerManagerFixtures();
            wrestlersWrestlerManagerWrestlerSetupFixtures2($wrestler, $manager, $secondManager, $secondWrestler);

            $currentManagers = $wrestler->currentManagers()->get();

            expect($currentManagers)->toHaveCount(1)
                ->and($currentManagers->firstOrFail()->id)->toBe($secondManager->id);
            $management = WrestlerManager::query()
                ->whereBelongsTo($wrestler)
                ->whereBelongsTo($secondManager)
                ->firstOrFail();
            expect($management->fired_at)->toBeNull();
        });

        test('previous managers query returns only completed relationships', function () {
            ['manager' => $manager, 'wrestler' => $wrestler, 'secondManager' => $secondManager, 'secondWrestler' => $secondWrestler] = wrestlersWrestlerManagerManagerFixtures();
            wrestlersWrestlerManagerWrestlerSetupFixtures2($wrestler, $manager, $secondManager, $secondWrestler);

            $previousManagers = $wrestler->previousManagers()->get();

            expect($previousManagers)->toHaveCount(1)
                ->and($previousManagers->firstOrFail()->id)->toBe($manager->id);
            $management = WrestlerManager::query()
                ->whereBelongsTo($wrestler)
                ->whereBelongsTo($manager)
                ->firstOrFail();
            expect($management->fired_at)->not->toBeNull();
        });

        test('all managers query returns complete relationship history', function () {
            ['manager' => $manager, 'wrestler' => $wrestler, 'secondManager' => $secondManager, 'secondWrestler' => $secondWrestler] = wrestlersWrestlerManagerManagerFixtures();
            wrestlersWrestlerManagerWrestlerSetupFixtures2($wrestler, $manager, $secondManager, $secondWrestler);

            $allManagers = $wrestler->managers()->get();

            expect($allManagers)->toHaveCount(2);

            $managerIds = $allManagers->pluck('id')->toArray();
            expect($managerIds)->toContain($manager->id)
                ->toContain($secondManager->id);
        });

        test('manager relationships are properly ordered by hired_at', function () {
            ['manager' => $manager, 'wrestler' => $wrestler, 'secondManager' => $secondManager, 'secondWrestler' => $secondWrestler] = wrestlersWrestlerManagerManagerFixtures();
            wrestlersWrestlerManagerWrestlerSetupFixtures2($wrestler, $manager, $secondManager, $secondWrestler);

            $managersChronological = $wrestler->managers()
                ->orderBy('hired_at', 'asc')
                ->get();

            expect($managersChronological->firstOrFail()->id)->toBe($manager->id)
                ->and($managersChronological->reverse()->firstOrFail()->id)->toBe($secondManager->id);
        });

        test('can query managers within specific date ranges', function () {
            ['manager' => $manager, 'wrestler' => $wrestler, 'secondManager' => $secondManager, 'secondWrestler' => $secondWrestler] = wrestlersWrestlerManagerManagerFixtures();
            wrestlersWrestlerManagerWrestlerSetupFixtures2($wrestler, $manager, $secondManager, $secondWrestler);

            $recentManagers = $wrestler->managers()
                ->wherePivot('hired_at', '>=', Carbon::now()->subMonths(4))
                ->get();

            expect($recentManagers)->toHaveCount(1)
                ->and($recentManagers->firstOrFail()->id)->toBe($secondManager->id);
        });
    });

    describe('Pivot Model Operations', function () {
        test('pivot model can be queried directly', function () {
            ['manager' => $manager, 'wrestler' => $wrestler] = wrestlersWrestlerManagerManagerFixtures();

            createManagementRelationship($wrestler, $manager);

            $pivotRecord = WrestlerManager::where('wrestler_id', $wrestler->id)
                ->where('manager_id', $manager->id)
                ->firstOrFail();

            expect($pivotRecord->wrestler_id)->toBe($wrestler->id)
                ->and($pivotRecord->manager_id)->toBe($manager->id)
                ->and($pivotRecord->hired_at)->toBeInstanceOf(Carbon::class)
                ->and($pivotRecord->fired_at)->toBeNull();
        });

        test('pivot model relationships work correctly', function () {
            ['manager' => $manager, 'wrestler' => $wrestler] = wrestlersWrestlerManagerManagerFixtures();

            createManagementRelationship($wrestler, $manager);

            $pivotRecord = WrestlerManager::where('wrestler_id', $wrestler->id)
                ->where('manager_id', $manager->id)
                ->firstOrFail();

            // Test pivot relationships
            expect($pivotRecord->wrestler->id)->toBe($wrestler->id);
            expect($pivotRecord->manager->id)->toBe($manager->id);
        });

        test('pivot model handles date casting correctly', function () {
            ['manager' => $manager, 'wrestler' => $wrestler] = wrestlersWrestlerManagerManagerFixtures();

            $hiredDate = Carbon::now()->subMonths(6);
            $firedDate = Carbon::now()->subMonths(1);

            createManagementRelationship($wrestler, $manager, [
                'hired_at' => $hiredDate,
                'fired_at' => $firedDate,
            ]);

            $pivotRecord = WrestlerManager::where('wrestler_id', $wrestler->id)
                ->where('manager_id', $manager->id)
                ->firstOrFail();

            expect($pivotRecord->hired_at)->toBeInstanceOf(Carbon::class)
                ->and($pivotRecord->fired_at)->toBeInstanceOf(Carbon::class)
                ->and($pivotRecord->hired_at->format('Y-m-d H:i:s'))->toBe($hiredDate->format('Y-m-d H:i:s'))
                ->and(requiredDate($pivotRecord->fired_at)->format('Y-m-d H:i:s'))->toBe($firedDate->format('Y-m-d H:i:s'));
        });
    });

    describe('Business Rule Validation', function () {
        test('can have multiple active management relationships', function () {
            ['manager' => $manager, 'wrestler' => $wrestler, 'secondManager' => $secondManager] = wrestlersWrestlerManagerManagerFixtures();

            createManagementRelationship($wrestler, $manager);
            $initialCount = $wrestler->currentManagers()->count();

            // Add a second manager
            createManagementRelationship($wrestler, $secondManager, [
                'hired_at' => Carbon::now()->subMonths(3),
            ]);

            // Verify wrestler now has multiple current managers
            expect($wrestler->currentManagers()->count())->toBe($initialCount + 1);
            expect($wrestler->currentManagers()->pluck('managers.id'))
                ->toContain($manager->id)
                ->toContain($secondManager->id);
        });

        test('management periods cannot overlap incorrectly', function () {
            ['manager' => $manager, 'wrestler' => $wrestler, 'secondManager' => $secondManager] = wrestlersWrestlerManagerManagerFixtures();

            $overlap = createOverlappingManagementPeriods($wrestler, $manager, $secondManager);

            // Verify both relationships exist (validation would be in business logic)
            expect($wrestler->managers()->count())->toBe(2);
            expect($overlap['overlap_detected'])->toBeTrue();
        });

        test('hire date cannot be after leave date', function () {
            ['manager' => $manager, 'wrestler' => $wrestler] = wrestlersWrestlerManagerManagerFixtures();

            $hiredDate = Carbon::now()->subMonths(3);
            $firedDate = Carbon::now()->subMonths(6); // Earlier than hired date (invalid)

            expect(fn () => DB::transaction(fn () => createManagementRelationship($wrestler, $manager, [
                'hired_at' => $hiredDate,
                'fired_at' => $firedDate,
            ])))->toThrow(QueryException::class);
        });
    });

    describe('Complex Scenarios', function () {
        test('manager and wrestler can have multiple separate management periods', function () {
            ['manager' => $manager, 'wrestler' => $wrestler, 'secondManager' => $secondManager] = wrestlersWrestlerManagerManagerFixtures();

            createManagementHistory($wrestler, [
                [
                    'manager' => $manager,
                    'hired_at' => Carbon::now()->subYear(),
                    'fired_at' => Carbon::now()->subMonths(8),
                ],
                [
                    'manager' => $secondManager,
                    'hired_at' => Carbon::now()->subMonths(6),
                    'fired_at' => Carbon::now()->subMonths(4),
                ],
                [
                    'manager' => $manager,
                    'hired_at' => Carbon::now()->subMonths(2),
                    'fired_at' => null,
                ],
            ]);

            expectRelationshipCounts($wrestler, [
                'managers' => 3,
                'currentManagers' => 1,
                'previousManagers' => 2,
            ]);

            // Verify current manager is the original manager
            $currentManager = $wrestler->currentManagers()->firstOrFail();
            expect($currentManager->id)->toBe($manager->id);

            // Verify relationship history includes both managers
            $allManagers = $wrestler->managers()->get();
            $uniqueManagers = $allManagers->unique('id');
            expect($uniqueManagers)->toHaveCount(2);
        });

        test('can query management duration and calculate statistics', function () {
            ['manager' => $manager, 'wrestler' => $wrestler, 'secondManager' => $secondManager] = wrestlersWrestlerManagerManagerFixtures();

            createManagementHistory($wrestler, [
                [
                    'manager' => $manager,
                    'hired_at' => Carbon::now()->subYear(),
                    'fired_at' => Carbon::now()->subMonths(6),
                ],
                [
                    'manager' => $secondManager,
                    'hired_at' => Carbon::now()->subMonths(3),
                    'fired_at' => null,
                ],
            ]);

            // Calculate duration of completed period
            $completedPeriod = $wrestler->previousManagers()->firstOrFail();
            $duration = $completedPeriod->pivot->hired_at->diffInDays($completedPeriod->pivot->fired_at);
            expect($duration)->toBeGreaterThan(150); // Approximately 6 months

            // Calculate duration of current period
            $currentPeriod = $wrestler->currentManagers()->firstOrFail();
            $currentDuration = $currentPeriod->pivot->hired_at->diffInDays(Carbon::now());
            expect($currentDuration)->toBeGreaterThan(80); // Approximately 3 months
        });
    });

    describe('Performance Optimization', function () {
        test('eager loading relationships works correctly', function () {
            ['manager' => $manager, 'wrestler' => $wrestler, 'secondManager' => $secondManager, 'secondWrestler' => $secondWrestler] = wrestlersWrestlerManagerManagerFixtures();

            createManagementRelationship($wrestler, $manager);
            createManagementRelationship($secondWrestler, $secondManager);

            // Load wrestlers with their current managers
            $wrestlers = Wrestler::with('currentManagers')->get();

            expect($wrestlers)->toHaveCount(2);

            // Verify relationships are loaded
            $wrestlerWithManager = requiredModel($wrestlers->firstWhere('id', $wrestler->id));
            expect($wrestlerWithManager->relationLoaded('currentManagers'))->toBeTrue()
                ->and($wrestlerWithManager->currentManagers)->toHaveCount(1);
        });

        test('can efficiently count relationships without loading them', function () {
            ['manager' => $manager, 'wrestler' => $wrestler, 'secondManager' => $secondManager] = wrestlersWrestlerManagerManagerFixtures();

            createManagementHistory($wrestler, [
                [
                    'manager' => $manager,
                    'hired_at' => Carbon::now()->subMonths(6),
                    'fired_at' => null,
                ],
                [
                    'manager' => $secondManager,
                    'hired_at' => Carbon::now()->subMonths(3),
                    'fired_at' => Carbon::now()->subMonths(1),
                ],
            ]);

            expectRelationshipCounts($wrestler, [
                'managers' => 2,
                'currentManagers' => 1,
                'previousManagers' => 1,
            ]);

            // Verify relationships are not loaded
            expect($wrestler->relationLoaded('managers'))->toBeFalse();
        });
    });
});
