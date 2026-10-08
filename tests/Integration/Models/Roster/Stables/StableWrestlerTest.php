<?php

declare(strict_types=1);

use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

/**
 * @return array{
 *     stable: Stable,
 *     wrestler: Wrestler,
 *     secondStable: Stable,
 *     secondWrestler: Wrestler,
 * }
 */
function stablesStableWrestlerStableFixtures(): array
{
    // Create test entities with realistic factory states
    $stable = Stable::factory()->unactivated()->create([
        'name' => 'The Four Horsemen',
    ]);

    $wrestler = Wrestler::factory()->employed()->create([
        'name' => 'Ric Flair',
        'hometown' => 'Charlotte, North Carolina',
    ]);

    $secondStable = Stable::factory()->unactivated()->create([
        'name' => 'D-Generation X',
    ]);

    $secondWrestler = Wrestler::factory()->employed()->create([
        'name' => 'Tully Blanchard',
        'hometown' => 'San Antonio, Texas',
    ]);

    return [
        'stable' => $stable,
        'wrestler' => $wrestler,
        'secondStable' => $secondStable,
        'secondWrestler' => $secondWrestler,
    ];
}

function stablesStableWrestlerWrestlerSetupFixtures(Wrestler $wrestler, Stable $stable): void
{
    // Set up active stable membership
    $wrestler->stables()->attach($stable->id, [
        'joined_at' => Carbon::now()->subMonths(6),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

}

function stablesStableWrestlerWrestlerSetupFixtures2(Wrestler $wrestler, Stable $stable, Stable $secondStable): void
{
    // Set up complex membership scenario
    $wrestler->stables()->attach($stable->id, [
        'joined_at' => Carbon::now()->subYear(),
        'left_at' => Carbon::now()->subMonths(6),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $wrestler->stables()->attach($secondStable->id, [
        'joined_at' => Carbon::now()->subMonths(3),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

}

/**
 * Integration tests for StableWrestler pivot model functionality.
 *
 * This test suite validates the complete workflow of stable-wrestler relationships
 * including wrestlers joining stables, leaving stables, querying current and previous
 * memberships, and ensuring proper business rule enforcement.
 *
 * Tests cover the wrestler-specific stable membership functionality with real database
 * relationships using the stables_wrestlers pivot table.
 *
 * @see StableWrestler
 */
describe('StableWrestler Pivot Model', function () {
    describe('Wrestler-Stable Membership Creation', function () {
        test('wrestler can join a stable with proper pivot data', function () {
            ['stable' => $stable, 'wrestler' => $wrestler] = stablesStableWrestlerStableFixtures();

            $joinedDate = Carbon::now()->subMonths(6);

            // Create the relationship using the attach method with pivot data
            $wrestler->stables()->attach($stable->id, [
                'joined_at' => $joinedDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify the relationship exists
            expect($wrestler->stables()->count())->toBe(1);
            expect($wrestler->currentStable)->not()->toBeNull()
                ->and($wrestler->previousStables()->count())->toBe(0);

            // Verify pivot data is correct
            $pivotData = $wrestler->stables()->firstOrFail()->pivot;
            expect(Carbon::parse($pivotData->joined_at)->format('Y-m-d H:i:s'))->toBe($joinedDate->format('Y-m-d H:i:s'))
                ->and($pivotData->left_at)->toBeNull()
                ->and($pivotData->wrestler_id)->toBe($wrestler->id)
                ->and($pivotData->stable_id)->toBe($stable->id);
        });

        test('stable can have multiple wrestlers', function () {
            ['stable' => $stable, 'wrestler' => $wrestler, 'secondWrestler' => $secondWrestler] = stablesStableWrestlerStableFixtures();

            $wrestlerJoinDate = Carbon::now()->subMonths(6);
            $secondWrestlerJoinDate = Carbon::now()->subMonths(4);

            // Attach different wrestlers to the same stable
            $wrestler->stables()->attach($stable->id, [
                'joined_at' => $wrestlerJoinDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $secondWrestler->stables()->attach($stable->id, [
                'joined_at' => $secondWrestlerJoinDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify all relationships exist
            expect($wrestler->currentStable)->not->toBeNull()->id->toBe($stable->id);
            expect($secondWrestler->currentStable)->not->toBeNull()->id->toBe($stable->id);

            // Verify stable has both wrestlers
            expect($stable->currentWrestlers()->count())->toBe(2);

            // Verify total pivot record count
            $wrestlerCount = StableWrestler::where('stable_id', $stable->id)
                ->whereNull('left_at')
                ->count();
            expect($wrestlerCount)->toBe(2);
        });

        test('wrestler can be part of multiple stables across different time periods', function () {
            ['stable' => $stable, 'wrestler' => $wrestler, 'secondStable' => $secondStable] = stablesStableWrestlerStableFixtures();

            $firstPeriodStart = Carbon::now()->subYear();
            $firstPeriodEnd = Carbon::now()->subMonths(6);
            $secondPeriodStart = Carbon::now()->subMonths(3);

            // First stable membership (completed)
            $wrestler->stables()->attach($stable->id, [
                'joined_at' => $firstPeriodStart,
                'left_at' => $firstPeriodEnd,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Second stable membership (current)
            $wrestler->stables()->attach($secondStable->id, [
                'joined_at' => $secondPeriodStart,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify relationship counts
            expect($wrestler->stables()->count())->toBe(2);
            expect($wrestler->currentStable)->not()->toBeNull()
                ->and($wrestler->previousStables()->count())->toBe(1);

            // Verify current stable is correct
            $currentStable = requiredModel($wrestler->currentStable);
            expect($currentStable->id)->toBe($secondStable->id);
            $currentMembership = StableWrestler::query()
                ->whereBelongsTo($currentStable)
                ->whereBelongsTo($wrestler)
                ->firstOrFail();
            expect($currentMembership->joined_at->format('Y-m-d H:i:s'))->toBe($secondPeriodStart->format('Y-m-d H:i:s'))
                ->and($currentMembership->left_at)->toBeNull();

            // Verify previous stable is correct
            $previousStable = $wrestler->previousStables()->firstOrFail();
            expect($previousStable->id)->toBe($stable->id)
                ->and(Carbon::parse($previousStable->pivot->joined_at)->format('Y-m-d H:i:s'))->toBe($firstPeriodStart->format('Y-m-d H:i:s'))
                ->and(Carbon::parse($previousStable->pivot->left_at)->format('Y-m-d H:i:s'))->toBe($firstPeriodEnd->format('Y-m-d H:i:s'));
        });
    });

    describe('Wrestler-Stable Membership Termination', function () {
        test('wrestler leaving stable updates pivot correctly', function () {
            ['stable' => $stable, 'wrestler' => $wrestler] = stablesStableWrestlerStableFixtures();
            stablesStableWrestlerWrestlerSetupFixtures($wrestler, $stable);

            $leaveDate = Carbon::now();

            // End the relationship by updating the pivot
            $wrestler->stables()->updateExistingPivot($stable->id, [
                'left_at' => $leaveDate,
                'updated_at' => now(),
            ]);

            // Verify relationship status changed
            expect($wrestler->currentStable)->toBeNull();
            expect($wrestler->previousStables()->count())->toBe(1);

            // Verify pivot data is updated
            $previousStable = $wrestler->previousStables()->firstOrFail();
            expect(Carbon::parse($previousStable->pivot->left_at)->format('Y-m-d H:i:s'))->toBe($leaveDate->format('Y-m-d H:i:s'));
        });

        test('detaching wrestler completely removes relationship', function () {
            ['stable' => $stable, 'wrestler' => $wrestler] = stablesStableWrestlerStableFixtures();
            stablesStableWrestlerWrestlerSetupFixtures($wrestler, $stable);

            // Detach the wrestler from stable
            $wrestler->stables()->detach($stable->id);

            // Verify all relationships are gone
            expect($wrestler->stables()->count())->toBe(0);
            expect($wrestler->currentStable)->toBeNull()
                ->and($wrestler->previousStables()->count())->toBe(0);

            // Verify pivot record is deleted
            expect(StableWrestler::where('wrestler_id', $wrestler->id)
                ->where('stable_id', $stable->id)
                ->exists())->toBeFalse();
        });
    });

    describe('StableWrestler Pivot Model Direct Queries', function () {
        test('StableWrestler pivot model can be queried directly', function () {
            ['stable' => $stable, 'wrestler' => $wrestler] = stablesStableWrestlerStableFixtures();

            $wrestler->stables()->attach($stable->id, [
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $pivotRecord = StableWrestler::where('wrestler_id', $wrestler->id)
                ->where('stable_id', $stable->id)
                ->firstOrFail();

            expect($pivotRecord->wrestler_id)->toBe($wrestler->id)
                ->and($pivotRecord->stable_id)->toBe($stable->id)
                ->and($pivotRecord->joined_at)->toBeInstanceOf(Carbon::class)
                ->and($pivotRecord->left_at)->toBeNull();

            // Test pivot relationships
            expect($pivotRecord->wrestler?->id)->toBe($wrestler->id);
            expect($pivotRecord->stable->id)->toBe($stable->id);
        });

        test('pivot model handles date casting correctly', function () {
            ['stable' => $stable, 'wrestler' => $wrestler] = stablesStableWrestlerStableFixtures();

            $joinedDate = Carbon::now()->subMonths(6);
            $leftDate = Carbon::now()->subMonths(1);

            // Test wrestler pivot
            $wrestler->stables()->attach($stable->id, [
                'joined_at' => $joinedDate,
                'left_at' => $leftDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $wrestlerPivot = StableWrestler::where('wrestler_id', $wrestler->id)
                ->where('stable_id', $stable->id)
                ->firstOrFail();

            expect($wrestlerPivot->joined_at)->toBeInstanceOf(Carbon::class)
                ->and($wrestlerPivot->left_at)->toBeInstanceOf(Carbon::class)
                ->and($wrestlerPivot->joined_at->format('Y-m-d H:i:s'))->toBe($joinedDate->format('Y-m-d H:i:s'))
                ->and(requiredDate($wrestlerPivot->left_at)->format('Y-m-d H:i:s'))->toBe($leftDate->format('Y-m-d H:i:s'));
        });
    });

    describe('Wrestler Stable Queries', function () {
        test('current stable query returns only active relationship', function () {
            ['stable' => $stable, 'wrestler' => $wrestler, 'secondStable' => $secondStable] = stablesStableWrestlerStableFixtures();
            stablesStableWrestlerWrestlerSetupFixtures2($wrestler, $stable, $secondStable);

            $currentStable = requiredModel($wrestler->currentStable);

            expect($currentStable->id)->toBe($secondStable->id);
            $membership = StableWrestler::query()
                ->whereBelongsTo($currentStable)
                ->whereBelongsTo($wrestler)
                ->firstOrFail();
            expect($membership->left_at)->toBeNull();
        });

        test('previous stables query returns only completed relationships', function () {
            ['stable' => $stable, 'wrestler' => $wrestler, 'secondStable' => $secondStable] = stablesStableWrestlerStableFixtures();
            stablesStableWrestlerWrestlerSetupFixtures2($wrestler, $stable, $secondStable);

            $previousStables = $wrestler->previousStables()->get();

            expect($previousStables)->toHaveCount(1)
                ->and($previousStables->firstOrFail()->id)->toBe($stable->id);
            $membership = StableWrestler::query()
                ->whereBelongsTo($stable)
                ->whereBelongsTo($wrestler)
                ->firstOrFail();
            expect($membership->left_at)->not->toBeNull();
        });

        test('all stables query returns complete membership history', function () {
            ['stable' => $stable, 'wrestler' => $wrestler, 'secondStable' => $secondStable] = stablesStableWrestlerStableFixtures();
            stablesStableWrestlerWrestlerSetupFixtures2($wrestler, $stable, $secondStable);

            $allStables = $wrestler->stables()->get();

            expect($allStables)->toHaveCount(2);

            $stableIds = $allStables->pluck('id')->toArray();
            expect($stableIds)->toContain($stable->id)
                ->toContain($secondStable->id);
        });

    });
});
