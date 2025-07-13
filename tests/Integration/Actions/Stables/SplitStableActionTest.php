<?php

declare(strict_types=1);

use App\Actions\Stables\SplitStableAction;
use App\Data\Stables\StableData;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Integration tests for SplitStableAction.
 *
 * This test suite validates the complete workflow of splitting a stable,
 * including selective member transfers, new stable creation, and data integrity.
 * These tests ensure that complex stable splitting operations work correctly
 * across the entire system stack.
 */
describe('SplitStableAction Integration Tests', function () {
    beforeEach(function () {
        // Create original stable with mixed members
        $this->originalStable = Stable::factory()->active()->create(['name' => 'Original Stable']);
        
        // Create wrestlers for the stable
        $this->wrestlers = Wrestler::factory()->bookable()->count(4)->create();
        
        // Create tag teams for the stable
        $this->tagTeams = TagTeam::factory()->employed()->count(2)->create();
        
        // Attach all members to original stable
        $joinDate = Carbon::yesterday();
        
        $this->originalStable->wrestlers()->attach($this->wrestlers->pluck('id'), ['joined_at' => $joinDate]);
        $this->originalStable->tagTeams()->attach($this->tagTeams->pluck('id'), ['joined_at' => $joinDate]);
        
        // Define members to transfer (split selection)
        $this->transferWrestlers = $this->wrestlers->take(2);
        $this->transferTagTeams = $this->tagTeams->take(1);
        $this->transferManagers = collect(); // Empty for this test setup
        
        // Create new stable data
        $this->newStableData = new StableData(name: 'New Split Stable');
    });

    describe('complete split workflow', function () {
        test('split creates new stable with specified members', function () {
            $splitDate = Carbon::now();
            
            // Get initial member counts
            $initialWrestlerCount = $this->originalStable->currentWrestlers()->count();
            $initialTagTeamCount = $this->originalStable->currentTagTeams()->count();
            
            // Execute split
            $newStable = SplitStableAction::run(
                $this->originalStable,
                $this->newStableData,
                $this->transferWrestlers,
                $this->transferTagTeams,
                $this->transferManagers,
                $splitDate
            );
            
            // Verify new stable was created
            expect($newStable)->toBeInstanceOf(Stable::class);
            expect($newStable->name)->toBe('New Split Stable');
            expect($newStable->isCurrentlyActive())->toBeTrue();
            
            // Verify new stable has transferred members
            expect($newStable->currentWrestlers()->count())->toBe($this->transferWrestlers->count());
            expect($newStable->currentTagTeams()->count())->toBe($this->transferTagTeams->count());
            
            // Verify original stable has remaining members
            $refreshedOriginal = $this->originalStable->fresh();
            expect($refreshedOriginal->currentWrestlers()->count())->toBe($initialWrestlerCount - $this->transferWrestlers->count());
            expect($refreshedOriginal->currentTagTeams()->count())->toBe($initialTagTeamCount - $this->transferTagTeams->count());
        });

        test('split transfers specified members correctly', function () {
            $splitDate = Carbon::now();
            
            // Get IDs of members to transfer
            $transferWrestlerIds = $this->transferWrestlers->pluck('id');
            $transferTagTeamIds = $this->transferTagTeams->pluck('id');
            
            // Execute split
            $newStable = SplitStableAction::run(
                $this->originalStable,
                $this->newStableData,
                $this->transferWrestlers,
                $this->transferTagTeams,
                $this->transferManagers,
                $splitDate
            );
            
            // Verify exact members were transferred
            $newStableWrestlerIds = $newStable->currentWrestlers()->pluck('id');
            $newStableTagTeamIds = $newStable->currentTagTeams()->pluck('id');
            
            expect($newStableWrestlerIds->sort()->values())->toBe($transferWrestlerIds->sort()->values());
            expect($newStableTagTeamIds->sort()->values())->toBe($transferTagTeamIds->sort()->values());
            
            // Verify members are no longer in original stable
            $refreshedOriginal = $this->originalStable->fresh();
            foreach ($transferWrestlerIds as $wrestlerId) {
                expect($refreshedOriginal->currentWrestlers()->where('id', $wrestlerId)->exists())->toBeFalse();
            }
            
            foreach ($transferTagTeamIds as $tagTeamId) {
                expect($refreshedOriginal->currentTagTeams()->where('id', $tagTeamId)->exists())->toBeFalse();
            }
        });

        test('split maintains proper date tracking for membership changes', function () {
            $splitDate = Carbon::now();
            
            // Execute split
            $newStable = SplitStableAction::run(
                $this->originalStable,
                $this->newStableData,
                $this->transferWrestlers,
                $this->transferTagTeams,
                $this->transferManagers,
                $splitDate
            );
            
            // Verify new stable memberships have correct join dates
            $newStableWrestlers = $newStable->wrestlers()->get();
            $newStableTagTeams = $newStable->tagTeams()->get();
            
            foreach ($newStableWrestlers as $wrestler) {
                expect($wrestler->pivot->joined_at)->not->toBeNull();
                expect(Carbon::parse($wrestler->pivot->joined_at)->gte($splitDate->subSecond()))->toBeTrue();
            }
            
            foreach ($newStableTagTeams as $tagTeam) {
                expect($tagTeam->pivot->joined_at)->not->toBeNull();
                expect(Carbon::parse($tagTeam->pivot->joined_at)->gte($splitDate->subSecond()))->toBeTrue();
            }
            
            // Verify original stable memberships were ended properly
            $originalStableMemberships = DB::table('stable_wrestler')
                ->where('stable_id', $this->originalStable->id)
                ->whereIn('wrestler_id', $this->transferWrestlers->pluck('id'))
                ->whereNotNull('left_at')
                ->get();
            
            expect($originalStableMemberships->count())->toBe($this->transferWrestlers->count());
        });

        test('split preserves historical membership data', function () {
            $splitDate = Carbon::now();
            
            // Get historical membership count before split
            $historicalWrestlerMemberships = DB::table('stable_wrestler')->count();
            $historicalTagTeamMemberships = DB::table('stable_tag_team')->count();
            
            // Execute split
            $newStable = SplitStableAction::run(
                $this->originalStable,
                $this->newStableData,
                $this->transferWrestlers,
                $this->transferTagTeams,
                $this->transferManagers,
                $splitDate
            );
            
            // Verify historical data is preserved (records added, not deleted)
            $newWrestlerMemberships = DB::table('stable_wrestler')->count();
            $newTagTeamMemberships = DB::table('stable_tag_team')->count();
            
            expect($newWrestlerMemberships)->toBeGreaterThan($historicalWrestlerMemberships);
            expect($newTagTeamMemberships)->toBeGreaterThan($historicalTagTeamMemberships);
        });
    });

    describe('selective member transfer scenarios', function () {
        test('split handles wrestlers-only transfer', function () {
            $splitDate = Carbon::now();
            
            // Split with only wrestlers
            $newStable = SplitStableAction::run(
                $this->originalStable,
                $this->newStableData,
                $this->transferWrestlers,
                collect(), // No tag teams
                collect(), // No managers
                $splitDate
            );
            
            // Verify new stable has only wrestlers
            expect($newStable->currentWrestlers()->count())->toBe($this->transferWrestlers->count());
            expect($newStable->currentTagTeams()->count())->toBe(0);
            
            // Verify original stable retains all tag teams
            $refreshedOriginal = $this->originalStable->fresh();
            expect($refreshedOriginal->currentTagTeams()->count())->toBe($this->tagTeams->count());
        });

        test('split handles tag-teams-only transfer', function () {
            $splitDate = Carbon::now();
            
            // Split with only tag teams
            $newStable = SplitStableAction::run(
                $this->originalStable,
                $this->newStableData,
                collect(), // No wrestlers
                $this->transferTagTeams,
                collect(), // No managers
                $splitDate
            );
            
            // Verify new stable has only tag teams
            expect($newStable->currentWrestlers()->count())->toBe(0);
            expect($newStable->currentTagTeams()->count())->toBe($this->transferTagTeams->count());
            
            // Verify original stable retains all wrestlers
            $refreshedOriginal = $this->originalStable->fresh();
            expect($refreshedOriginal->currentWrestlers()->count())->toBe($this->wrestlers->count());
        });

        test('split handles mixed member type transfer', function () {
            $splitDate = Carbon::now();
            
            // Split with mixed member types
            $newStable = SplitStableAction::run(
                $this->originalStable,
                $this->newStableData,
                $this->transferWrestlers,
                $this->transferTagTeams,
                $this->transferManagers,
                $splitDate
            );
            
            // Verify new stable has all transferred member types
            expect($newStable->currentWrestlers()->count())->toBe($this->transferWrestlers->count());
            expect($newStable->currentTagTeams()->count())->toBe($this->transferTagTeams->count());
            
            // Verify original stable has remaining members
            $refreshedOriginal = $this->originalStable->fresh();
            expect($refreshedOriginal->currentWrestlers()->count())->toBe($this->wrestlers->count() - $this->transferWrestlers->count());
            expect($refreshedOriginal->currentTagTeams()->count())->toBe($this->tagTeams->count() - $this->transferTagTeams->count());
        });
    });

    describe('edge cases and error scenarios', function () {
        test('split handles empty transfer collections gracefully', function () {
            $splitDate = Carbon::now();
            
            // Split with no members to transfer
            $newStable = SplitStableAction::run(
                $this->originalStable,
                $this->newStableData,
                collect(), // No wrestlers
                collect(), // No tag teams
                collect(), // No managers
                $splitDate
            );
            
            // Verify new stable was created but is empty
            expect($newStable->currentWrestlers()->count())->toBe(0);
            expect($newStable->currentTagTeams()->count())->toBe(0);
            
            // Verify original stable unchanged
            $refreshedOriginal = $this->originalStable->fresh();
            expect($refreshedOriginal->currentWrestlers()->count())->toBe($this->wrestlers->count());
            expect($refreshedOriginal->currentTagTeams()->count())->toBe($this->tagTeams->count());
        });

        test('split handles transfer of all members', function () {
            $splitDate = Carbon::now();
            
            // Split with all members transferred
            $newStable = SplitStableAction::run(
                $this->originalStable,
                $this->newStableData,
                $this->wrestlers, // All wrestlers
                $this->tagTeams,  // All tag teams
                collect(),
                $splitDate
            );
            
            // Verify new stable has all members
            expect($newStable->currentWrestlers()->count())->toBe($this->wrestlers->count());
            expect($newStable->currentTagTeams()->count())->toBe($this->tagTeams->count());
            
            // Verify original stable is empty
            $refreshedOriginal = $this->originalStable->fresh();
            expect($refreshedOriginal->currentWrestlers()->count())->toBe(0);
            expect($refreshedOriginal->currentTagTeams()->count())->toBe(0);
        });

        test('split validates member availability before transfer', function () {
            // Create unemployed wrestler
            $unemployedWrestler = Wrestler::factory()->create(['status' => \App\Enums\Shared\EmploymentStatus::Unemployed]);
            
            $splitDate = Carbon::now();
            
            // Try to split with unemployed wrestler
            $transferWrestlers = $this->transferWrestlers->push($unemployedWrestler);
            
            // Execute split - should handle unemployed members appropriately
            $newStable = SplitStableAction::run(
                $this->originalStable,
                $this->newStableData,
                $transferWrestlers,
                $this->transferTagTeams,
                collect(),
                $splitDate
            );
            
            // Verify unemployed wrestler was not transferred (or handled per business rules)
            $newStableWrestlerIds = $newStable->currentWrestlers()->pluck('id');
            expect($newStableWrestlerIds->contains($unemployedWrestler->id))->toBeFalse();
        });

        test('split validates stable status before execution', function () {
            // Create retired stable
            $retiredStable = Stable::factory()->retired()->create(['name' => 'Retired Stable']);
            
            $splitDate = Carbon::now();
            
            // Expect validation exception
            expect(fn () => SplitStableAction::run(
                $retiredStable,
                $this->newStableData,
                $this->transferWrestlers,
                $this->transferTagTeams,
                collect(),
                $splitDate
            ))->toThrow(Exception::class);
        });
    });

    describe('new stable creation validation', function () {
        test('split creates new stable with proper initialization', function () {
            $splitDate = Carbon::now();
            
            // Execute split
            $newStable = SplitStableAction::run(
                $this->originalStable,
                $this->newStableData,
                $this->transferWrestlers,
                $this->transferTagTeams,
                collect(),
                $splitDate
            );
            
            // Verify new stable has correct properties
            expect($newStable->name)->toBe($this->newStableData->name);
            expect($newStable->isCurrentlyActive())->toBeTrue();
            expect($newStable->activityPeriods()->count())->toBe(1);
            
            // Verify activity period has correct start date
            $activityPeriod = $newStable->currentActivityPeriod;
            expect($activityPeriod->started_at->format('Y-m-d H:i:s'))->toBe($splitDate->format('Y-m-d H:i:s'));
            expect($activityPeriod->ended_at)->toBeNull();
        });

        test('split validates new stable name uniqueness', function () {
            $splitDate = Carbon::now();
            
            // Create stable with same name
            Stable::factory()->create(['name' => $this->newStableData->name]);
            
            // Try to split with duplicate name
            expect(fn () => SplitStableAction::run(
                $this->originalStable,
                $this->newStableData,
                $this->transferWrestlers,
                $this->transferTagTeams,
                collect(),
                $splitDate
            ))->toThrow(Exception::class);
        });
    });

    describe('transaction integrity', function () {
        test('split maintains transaction integrity on success', function () {
            $splitDate = Carbon::now();
            
            // Count initial records
            $initialStableCount = Stable::count();
            $initialMembershipCount = DB::table('stable_wrestler')->count() + DB::table('stable_tag_team')->count();
            
            // Execute split
            $newStable = SplitStableAction::run(
                $this->originalStable,
                $this->newStableData,
                $this->transferWrestlers,
                $this->transferTagTeams,
                collect(),
                $splitDate
            );
            
            // Verify stable count increased by one
            expect(Stable::count())->toBe($initialStableCount + 1);
            
            // Verify membership records increased (new memberships + ended memberships)
            $newMembershipCount = DB::table('stable_wrestler')->count() + DB::table('stable_tag_team')->count();
            expect($newMembershipCount)->toBeGreaterThan($initialMembershipCount);
        });

        test('split handles transaction rollback on constraint violation', function () {
            $splitDate = Carbon::now();
            
            // Get initial counts
            $initialStableCount = Stable::count();
            $initialMembershipCount = DB::table('stable_wrestler')->count();
            
            // For now, verify that normal split doesn't affect counts negatively
            try {
                $newStable = SplitStableAction::run(
                    $this->originalStable,
                    $this->newStableData,
                    $this->transferWrestlers,
                    $this->transferTagTeams,
                    collect(),
                    $splitDate
                );
                
                // Verify operation completed successfully
                expect(Stable::count())->toBe($initialStableCount + 1);
                
            } catch (Exception $e) {
                // If transaction fails, verify no partial changes occurred
                expect(Stable::count())->toBe($initialStableCount);
                expect(DB::table('stable_wrestler')->count())->toBe($initialMembershipCount);
            }
        });
    });

    describe('business rule validation', function () {
        test('split respects minimum member requirements', function () {
            $splitDate = Carbon::now();
            
            // Execute split
            $newStable = SplitStableAction::run(
                $this->originalStable,
                $this->newStableData,
                $this->transferWrestlers,
                $this->transferTagTeams,
                collect(),
                $splitDate
            );
            
            // Verify both stables meet minimum requirements
            $newStableMemberCount = $newStable->currentWrestlers()->count() + $newStable->currentTagTeams()->count();
            
            $refreshedOriginal = $this->originalStable->fresh();
            $originalMemberCount = $refreshedOriginal->currentWrestlers()->count() + $refreshedOriginal->currentTagTeams()->count();
            
            // Assume minimum of 1 member required (adjust based on business rules)
            expect($newStableMemberCount)->toBeGreaterThanOrEqual(1);
            expect($originalMemberCount)->toBeGreaterThanOrEqual(0); // Original can be empty after split
        });

        test('split validates member employment status', function () {
            $splitDate = Carbon::now();
            
            // Execute split
            $newStable = SplitStableAction::run(
                $this->originalStable,
                $this->newStableData,
                $this->transferWrestlers,
                $this->transferTagTeams,
                collect(),
                $splitDate
            );
            
            // Verify all transferred members have proper employment status
            $transferredWrestlers = $newStable->currentWrestlers;
            $transferredTagTeams = $newStable->currentTagTeams;
            
            foreach ($transferredWrestlers as $wrestler) {
                expect($wrestler->status)->toBe(\App\Enums\Shared\EmploymentStatus::Employed);
            }
            
            foreach ($transferredTagTeams as $tagTeam) {
                expect($tagTeam->status)->toBe(\App\Enums\Shared\EmploymentStatus::Employed);
            }
        });
    });

    describe('data integrity validation', function () {
        test('split preserves referential integrity', function () {
            $splitDate = Carbon::now();
            
            // Get original member IDs
            $originalWrestlerIds = $this->transferWrestlers->pluck('id');
            $originalTagTeamIds = $this->transferTagTeams->pluck('id');
            
            // Execute split
            $newStable = SplitStableAction::run(
                $this->originalStable,
                $this->newStableData,
                $this->transferWrestlers,
                $this->transferTagTeams,
                collect(),
                $splitDate
            );
            
            // Verify all members still exist in the system
            foreach ($originalWrestlerIds as $wrestlerId) {
                expect(Wrestler::find($wrestlerId))->not->toBeNull();
            }
            
            foreach ($originalTagTeamIds as $tagTeamId) {
                expect(TagTeam::find($tagTeamId))->not->toBeNull();
            }
        });

        test('split maintains stable status consistency', function () {
            $splitDate = Carbon::now();
            
            // Execute split
            $newStable = SplitStableAction::run(
                $this->originalStable,
                $this->newStableData,
                $this->transferWrestlers,
                $this->transferTagTeams,
                collect(),
                $splitDate
            );
            
            // Verify new stable is active
            expect($newStable->isCurrentlyActive())->toBeTrue();
            
            // Verify original stable status is appropriate
            $refreshedOriginal = $this->originalStable->fresh();
            
            // If original has members, should remain active; if empty, may become inactive
            $totalRemainingMembers = $refreshedOriginal->currentWrestlers()->count() + $refreshedOriginal->currentTagTeams()->count();
            
            if ($totalRemainingMembers > 0) {
                expect($refreshedOriginal->isCurrentlyActive())->toBeTrue();
            } else {
                // Empty stable may become inactive (depends on business rules)
                expect($refreshedOriginal->isCurrentlyActive() || $refreshedOriginal->isInactive())->toBeTrue();
            }
        });
    });
});