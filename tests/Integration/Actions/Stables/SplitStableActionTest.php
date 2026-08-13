<?php

declare(strict_types=1);

use App\Actions\Stables\CreateAction;
use App\Actions\Stables\SplitStableAction;
use App\Data\Stables\StableMembershipData;
use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\Stables\CannotBeSplitException;
use App\Lifecycle\StableRestructuringEligibility;
use App\Models\Stables\Stable;
use App\Models\Stables\StableTagTeam;
use App\Models\Stables\StableWrestler;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Services\StableMembershipService;
use Illuminate\Support\Carbon;
use JMac\Testing\Double;

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
        $this->originalStable = Stable::factory()->create(['name' => 'Original Stable']);

        // Make it active by adding activation period manually
        $activationDate = Carbon::yesterday();
        $this->originalStable->activityPeriods()->create([
            'started_at' => $activationDate,
        ]);

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

        // Create new stable name
        $this->newStableName = 'New Split Stable';

        $this->membersForNewStable = new StableMembershipData(
            wrestlers: $this->transferWrestlers,
            tagTeams: $this->transferTagTeams,
        );
    });

    describe('complete split workflow', function () {
        test('split creates new stable with specified members', function () {
            $splitDate = Carbon::now();

            // Get initial member counts
            $initialWrestlerCount = $this->originalStable->currentWrestlers()->count();
            $initialTagTeamCount = $this->originalStable->currentTagTeams()->count();

            // Execute split
            $newStable = resolve(SplitStableAction::class)->handle(
                $this->originalStable,
                $this->newStableName,
                $this->membersForNewStable,
                $splitDate
            );

            // Verify new stable was created
            expect($newStable)->toBeInstanceOf(Stable::class);
            expect($newStable->name)->toBe($this->newStableName);
            expect($newStable->isCurrentlyActive())->toBeTrue();

            // Verify new stable has transferred members
            expect($newStable->currentWrestlers()->count())->toBe($this->transferWrestlers->count());
            expect($newStable->currentTagTeams()->count())->toBe($this->transferTagTeams->count());

            // Verify original stable has remaining members
            $refreshedOriginal = freshModel($this->originalStable);
            expect($refreshedOriginal->currentWrestlers()->count())->toBe($initialWrestlerCount - $this->transferWrestlers->count());
            expect($refreshedOriginal->currentTagTeams()->count())->toBe($initialTagTeamCount - $this->transferTagTeams->count());
        });

        test('split transfers specified members correctly', function () {
            $splitDate = Carbon::now();

            // Get IDs of members to transfer
            $transferWrestlerIds = $this->transferWrestlers->pluck('id');
            $transferTagTeamIds = $this->transferTagTeams->pluck('id');

            // Execute split
            $newStable = resolve(SplitStableAction::class)->handle(
                $this->originalStable,
                $this->newStableName,
                $this->membersForNewStable,
                $splitDate
            );

            // Verify exact members were transferred
            $newStableWrestlerIds = $newStable->currentWrestlers()->pluck('wrestlers.id');
            $newStableTagTeamIds = $newStable->currentTagTeams()->pluck('tag_teams.id');

            expect($newStableWrestlerIds->sort()->values())->toEqual($transferWrestlerIds->sort()->values());
            expect($newStableTagTeamIds->sort()->values())->toEqual($transferTagTeamIds->sort()->values());

            // Verify members are no longer in original stable
            $refreshedOriginal = freshModel($this->originalStable);
            foreach ($transferWrestlerIds as $wrestlerId) {
                expect($refreshedOriginal->currentWrestlers()->where('wrestlers.id', $wrestlerId)->exists())->toBeFalse();
            }

            foreach ($transferTagTeamIds as $tagTeamId) {
                expect($refreshedOriginal->currentTagTeams()->where('tag_teams.id', $tagTeamId)->exists())->toBeFalse();
            }
        });

        test('split maintains proper date tracking for membership changes', function () {
            $splitDate = Carbon::now();

            // Execute split
            $newStable = resolve(SplitStableAction::class)->handle(
                $this->originalStable,
                $this->newStableName,
                $this->membersForNewStable,
                $splitDate
            );

            // Verify new stable memberships have correct join dates
            $newStableWrestlers = $newStable->wrestlers()->get();
            $newStableTagTeams = $newStable->tagTeams()->get();
            $membershipCutoff = $splitDate->copy()->subSecond();

            foreach ($newStableWrestlers as $wrestler) {
                $membership = StableWrestler::query()
                    ->whereBelongsTo($newStable)
                    ->whereBelongsTo($wrestler)
                    ->firstOrFail();

                expect($membership->joined_at->gte($membershipCutoff))->toBeTrue();
            }

            foreach ($newStableTagTeams as $tagTeam) {
                $membership = StableTagTeam::query()
                    ->whereBelongsTo($newStable)
                    ->whereBelongsTo($tagTeam, 'tagTeam')
                    ->firstOrFail();

                expect($membership->joined_at->gte($membershipCutoff))->toBeTrue();
            }

            // Verify original stable memberships were ended properly
            // Check that transferred wrestlers are no longer current members
            $refreshedOriginal = freshModel($this->originalStable);
            foreach ($this->transferWrestlers as $wrestler) {
                expect($refreshedOriginal->currentWrestlers()->where('wrestlers.id', $wrestler->id)->exists())->toBeFalse();
            }
        });

        test('split preserves historical membership data', function () {
            $splitDate = Carbon::now();

            // Get initial member counts
            $initialWrestlerCount = $this->originalStable->currentWrestlers()->count();
            $initialTagTeamCount = $this->originalStable->currentTagTeams()->count();

            // Execute split
            $newStable = resolve(SplitStableAction::class)->handle(
                $this->originalStable,
                $this->newStableName,
                $this->membersForNewStable,
                $splitDate
            );

            // Verify all members are preserved across both stables
            $refreshedOriginal = freshModel($this->originalStable);
            $totalWrestlers = $newStable->currentWrestlers()->count() + $refreshedOriginal->currentWrestlers()->count();
            $totalTagTeams = $newStable->currentTagTeams()->count() + $refreshedOriginal->currentTagTeams()->count();

            expect($totalWrestlers)->toBe($initialWrestlerCount);
            expect($totalTagTeams)->toBe($initialTagTeamCount);
        });
    });

    describe('selective member transfer scenarios', function () {
        test('split handles wrestlers-only transfer', function () {
            $splitDate = Carbon::now();

            // Split with only wrestlers
            $membersForSplit = new StableMembershipData(
                wrestlers: $this->wrestlers->take(3),
            );

            $newStable = resolve(SplitStableAction::class)->handle(
                $this->originalStable,
                $this->newStableName,
                $membersForSplit,
                $splitDate
            );

            // Verify new stable has only wrestlers
            expect($newStable->currentWrestlers()->count())->toBe(3);
            expect($newStable->currentTagTeams()->count())->toBe(0);

            // Verify original stable retains all tag teams
            $refreshedOriginal = freshModel($this->originalStable);
            expect($refreshedOriginal->currentTagTeams()->count())->toBe($this->tagTeams->count());
        });

        test('split handles tag-teams-only transfer', function () {
            $splitDate = Carbon::now();

            // Split with only tag teams
            $membersForSplit = new StableMembershipData(
                tagTeams: $this->tagTeams,
            );

            $newStable = resolve(SplitStableAction::class)->handle(
                $this->originalStable,
                $this->newStableName,
                $membersForSplit,
                $splitDate
            );

            // Verify new stable has only tag teams
            expect($newStable->currentWrestlers()->count())->toBe(0);
            expect($newStable->currentTagTeams()->count())->toBe(2);

            // Verify original stable retains all wrestlers
            $refreshedOriginal = freshModel($this->originalStable);
            expect($refreshedOriginal->currentWrestlers()->count())->toBe($this->wrestlers->count());
        });

        test('split handles mixed member type transfer', function () {
            $splitDate = Carbon::now();

            // Split with mixed member types
            $newStable = resolve(SplitStableAction::class)->handle(
                $this->originalStable,
                $this->newStableName,
                $this->membersForNewStable,
                $splitDate
            );

            // Verify new stable has all transferred member types
            expect($newStable->currentWrestlers()->count())->toBe($this->transferWrestlers->count());
            expect($newStable->currentTagTeams()->count())->toBe($this->transferTagTeams->count());

            // Verify original stable has remaining members
            $refreshedOriginal = freshModel($this->originalStable);
            expect($refreshedOriginal->currentWrestlers()->count())->toBe($this->wrestlers->count() - $this->transferWrestlers->count());
            expect($refreshedOriginal->currentTagTeams()->count())->toBe($this->tagTeams->count() - $this->transferTagTeams->count());
        });
    });

    describe('edge cases and error scenarios', function () {
        test('split rejects empty transfer collections', function () {
            $splitDate = Carbon::now();

            $membersForSplit = new StableMembershipData();

            expect(fn () => resolve(SplitStableAction::class)->handle(
                $this->originalStable,
                $this->newStableName,
                $membersForSplit,
                $splitDate
            ))->toThrow(CannotBeSplitException::class);

            // Verify original stable unchanged
            $refreshedOriginal = freshModel($this->originalStable);
            expect($refreshedOriginal->currentWrestlers()->count())->toBe($this->wrestlers->count());
            expect($refreshedOriginal->currentTagTeams()->count())->toBe($this->tagTeams->count());
        });

        test('split rejects transferring all members', function () {
            $splitDate = Carbon::now();

            $membersForSplit = new StableMembershipData(
                wrestlers: $this->wrestlers,
                tagTeams: $this->tagTeams,
            );

            expect(fn () => resolve(SplitStableAction::class)->handle(
                $this->originalStable,
                $this->newStableName,
                $membersForSplit,
                $splitDate
            ))->toThrow(CannotBeSplitException::class);

            // Verify original stable still has all members (transaction rolled back)
            $refreshedOriginal = freshModel($this->originalStable);
            expect($refreshedOriginal->currentWrestlers()->count())->toBe($this->wrestlers->count());
            expect($refreshedOriginal->currentTagTeams()->count())->toBe($this->tagTeams->count());
        });

        test('split rejects selected members outside the original stable', function () {
            $outsider = Wrestler::factory()->bookable()->create();
            $membersForSplit = new StableMembershipData(
                wrestlers: $this->transferWrestlers->push($outsider),
                tagTeams: $this->transferTagTeams,
            );

            expect(fn () => resolve(SplitStableAction::class)->handle(
                $this->originalStable,
                $this->newStableName,
                $membersForSplit,
                now(),
            ))->toThrow(CannotBeSplitException::class);

            expect(Stable::query()->where('name', $this->newStableName)->exists())->toBeFalse();
        });

        test('split rejects a new stable below the canonical minimum headcount', function () {
            $membersForSplit = new StableMembershipData(
                wrestlers: $this->wrestlers->take(2),
            );

            expect(fn () => resolve(SplitStableAction::class)->handle(
                $this->originalStable,
                $this->newStableName,
                $membersForSplit,
                now(),
            ))->toThrow(CannotBeSplitException::class);
        });

        test('split rejects an original stable below the canonical minimum headcount', function () {
            $membersForSplit = new StableMembershipData(
                wrestlers: $this->wrestlers,
                tagTeams: $this->tagTeams->take(1),
            );

            expect(fn () => resolve(SplitStableAction::class)->handle(
                $this->originalStable,
                $this->newStableName,
                $membersForSplit,
                now(),
            ))->toThrow(CannotBeSplitException::class);
        });

        test('split rejects unavailable members instead of silently dropping them', function () {
            $splitDate = Carbon::now();
            $unavailableWrestler = $this->transferWrestlers->firstOrFail();
            $unavailableWrestler->currentEmployment()->update(['ended_at' => $splitDate]);

            $membersForSplit = new StableMembershipData(
                wrestlers: $this->transferWrestlers,
                tagTeams: $this->transferTagTeams,
            );

            expect(fn () => resolve(SplitStableAction::class)->handle(
                $this->originalStable,
                $this->newStableName,
                $membersForSplit,
                $splitDate
            ))->toThrow(CannotBeSplitException::class);

            expect(Stable::query()->where('name', $this->newStableName)->exists())->toBeFalse();
        });

        test('split validates stable status before execution', function () {
            // Create retired stable
            $retiredStable = Stable::factory()->retired()->create(['name' => 'Retired Stable']);

            $splitDate = Carbon::now();

            // Expect validation exception
            expect(fn () => resolve(SplitStableAction::class)->handle(
                $retiredStable,
                $this->newStableName,
                $this->membersForNewStable,
                $splitDate
            ))->toThrow(Exception::class);
        });
    });

    describe('new stable creation validation', function () {
        test('split creates new stable with proper initialization', function () {
            $splitDate = Carbon::now();

            // Execute split
            $newStable = resolve(SplitStableAction::class)->handle(
                $this->originalStable,
                $this->newStableName,
                $this->membersForNewStable,
                $splitDate
            );

            // Verify new stable has correct properties
            expect($newStable->name)->toBe($this->newStableName);
            expect($newStable->isCurrentlyActive())->toBeTrue();
            expect($newStable->activityPeriods()->count())->toBe(1);

            // Verify activity period has correct start date
            $activityPeriod = $newStable->currentActivityPeriod()->firstOrFail();
            expect(requiredDate($activityPeriod->started_at)->format('Y-m-d H:i:s'))->toBe($splitDate->format('Y-m-d H:i:s'));
            expect($activityPeriod->ended_at)->toBeNull();
        });

        test('split validates new stable name uniqueness', function () {
            $splitDate = Carbon::now();

            // Create stable with same name
            Stable::factory()->create(['name' => $this->newStableName]);

            // Try to split with duplicate name
            expect(fn () => resolve(SplitStableAction::class)->handle(
                $this->originalStable,
                $this->newStableName,
                $this->membersForNewStable,
                $splitDate
            ))->toThrow(Exception::class);
        });
    });

    describe('transaction integrity', function () {
        test('split maintains transaction integrity on success', function () {
            $splitDate = Carbon::now();

            // Count initial records
            $initialStableCount = Stable::count();

            // Execute split
            $newStable = resolve(SplitStableAction::class)->handle(
                $this->originalStable,
                $this->newStableName,
                $this->membersForNewStable,
                $splitDate
            );

            // Verify stable count increased by one
            expect(Stable::count())->toBe($initialStableCount + 1);

            // Verify both stables exist and have members
            expect($newStable->currentWrestlers()->count())->toBeGreaterThan(0);
            expect(freshModel($this->originalStable)->currentWrestlers()->count())->toBeGreaterThanOrEqual(0);
        });

        test('split rolls back membership changes when stable creation fails', function () {
            $splitDate = Carbon::now();
            $initialStableCount = Stable::count();
            $originalMembershipIds = StableWrestler::query()
                ->whereBelongsTo($this->originalStable)
                ->whereNull('left_at')
                ->pluck('id');
            $originalTagTeamMembershipIds = StableTagTeam::query()
                ->whereBelongsTo($this->originalStable)
                ->whereNull('left_at')
                ->pluck('id');
            $createAction = Double::for(CreateAction::class);
            $createAction->expects('handle')
                ->throws(new LogicException('Stable creation failed.'));
            $action = new SplitStableAction(
                $createAction,
                resolve(StableMembershipService::class),
                resolve(StableRestructuringEligibility::class),
            );

            expect(fn () => $action->handle(
                $this->originalStable,
                $this->newStableName,
                $this->membersForNewStable,
                $splitDate
            ))
                ->toThrow(LogicException::class, 'Stable creation failed.');

            expect(Stable::count())->toBe($initialStableCount)
                ->and(StableWrestler::query()
                    ->whereKey($originalMembershipIds)
                    ->whereNull('left_at')
                    ->count())
                ->toBe($originalMembershipIds->count())
                ->and(StableTagTeam::query()
                    ->whereKey($originalTagTeamMembershipIds)
                    ->whereNull('left_at')
                    ->count())
                ->toBe($originalTagTeamMembershipIds->count());

            $createAction->verify();
        });
    });

    describe('business rule validation', function () {
        test('split respects minimum member requirements', function () {
            $splitDate = Carbon::now();

            // Execute split
            $newStable = resolve(SplitStableAction::class)->handle(
                $this->originalStable,
                $this->newStableName,
                $this->membersForNewStable,
                $splitDate
            );

            // Verify both stables meet minimum requirements
            $newStableMemberCount = $newStable->getCurrentMemberCount();

            $refreshedOriginal = freshModel($this->originalStable);
            $originalMemberCount = $refreshedOriginal->getCurrentMemberCount();

            expect($newStableMemberCount)->toBeGreaterThanOrEqual(Stable::MIN_MEMBERS_COUNT);
            expect($originalMemberCount)->toBeGreaterThanOrEqual(Stable::MIN_MEMBERS_COUNT);
        });

        test('split validates member employment status', function () {
            $splitDate = Carbon::now();

            // Execute split
            $newStable = resolve(SplitStableAction::class)->handle(
                $this->originalStable,
                $this->newStableName,
                $this->membersForNewStable,
                $splitDate
            );

            // Verify all transferred members have proper employment status
            $transferredWrestlers = $newStable->currentWrestlers;
            $transferredTagTeams = $newStable->currentTagTeams;

            foreach ($transferredWrestlers as $wrestler) {
                expect($wrestler->status)->toBe(EmploymentStatus::Employed);
            }

            foreach ($transferredTagTeams as $tagTeam) {
                expect($tagTeam->status)->toBe(EmploymentStatus::Employed);
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
            $newStable = resolve(SplitStableAction::class)->handle(
                $this->originalStable,
                $this->newStableName,
                $this->membersForNewStable,
                $splitDate
            );

            // Verify all members still exist in the system
            foreach ($originalWrestlerIds as $wrestlerId) {
                expect(Wrestler::find($wrestlerId))->not()->toBeNull();
            }

            foreach ($originalTagTeamIds as $tagTeamId) {
                expect(TagTeam::find($tagTeamId))->not()->toBeNull();
            }
        });

        test('split maintains stable status consistency', function () {
            $splitDate = Carbon::now();

            // Execute split
            $newStable = resolve(SplitStableAction::class)->handle(
                $this->originalStable,
                $this->newStableName,
                $this->membersForNewStable,
                $splitDate
            );

            // Verify new stable is active
            expect($newStable->isCurrentlyActive())->toBeTrue();

            // Verify original stable status is appropriate
            $refreshedOriginal = freshModel($this->originalStable);

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
