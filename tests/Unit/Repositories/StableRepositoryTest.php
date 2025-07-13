<?php

declare(strict_types=1);

use App\Data\Stables\StableData;
use App\Models\Managers\Manager;
use App\Models\Stables\Stable;
use App\Models\Stables\StableActivityPeriod;
use App\Models\Stables\StableRetirement;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\Concerns\ManagesActivity;
use App\Repositories\Concerns\ManagesMembers;
use App\Repositories\Concerns\ManagesRetirement;
use App\Repositories\Contracts\ManagesActivity as ManagesActivityContract;
use App\Repositories\Contracts\ManagesRetirement as ManagesRetirementContract;
use App\Repositories\Contracts\ManagesStableMembers;
use App\Repositories\Contracts\StableRepositoryInterface;
use App\Repositories\StableRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;

use function Spatie\PestPluginTestTime\testTime;

/**
 * Unit tests for StableRepository business logic and data operations.
 *
 * UNIT TEST SCOPE:
 * - Repository configuration and structure verification
 * - Core CRUD operations (create, update, delete, restore)
 * - Trait-based functionality (activity, retirement, member management)
 * - Stable specific relationship management (wrestlers, tag teams)
 * - Complex stable operations (member updates, disassembly)
 * - Business logic query methods
 *
 * These tests verify that the StableRepository correctly implements
 * all business operations and data persistence requirements.
 *
 * @see StableRepository
 */
describe('StableRepository Unit Tests', function () {
    beforeEach(function () {
        testTime()->freeze();
        $this->repository = app(StableRepository::class);

        // Create test entities - only wrestlers and tag teams can be direct stable members
        $this->wrestler1 = Wrestler::factory()->create(['name' => 'Test Wrestler 1']);
        $this->wrestler2 = Wrestler::factory()->create(['name' => 'Test Wrestler 2']);
        $this->wrestler3 = Wrestler::factory()->create(['name' => 'Test Wrestler 3']);

        $this->tagTeam1 = TagTeam::factory()->create(['name' => 'Test Tag Team 1']);
        $this->tagTeam2 = TagTeam::factory()->create(['name' => 'Test Tag Team 2']);

        $this->stable = Stable::factory()->create(['name' => 'Test Stable']);
    });

    describe('repository configuration', function () {
        test('repository can be resolved from container', function () {
            expect($this->repository)->toBeInstanceOf(StableRepository::class);
            expect($this->repository)->toBeInstanceOf(StableRepositoryInterface::class);
        });

        test('repository implements all required contracts', function () {
            expect($this->repository)->toBeInstanceOf(ManagesActivityContract::class);
            expect($this->repository)->toBeInstanceOf(ManagesRetirementContract::class);
            expect($this->repository)->toBeInstanceOf(ManagesStableMembers::class);
        });

        test('repository uses all required traits', function () {
            expect(StableRepository::class)->usesTrait(ManagesActivity::class);
            expect(StableRepository::class)->usesTrait(ManagesMembers::class);
            expect(StableRepository::class)->usesTrait(ManagesRetirement::class);
        });

        test('repository has all expected methods', function () {
            $methods = [
                'create', 'update', 'restore',
                'createActivity', 'endActivity', 'createEstablishment', 'createDebut',
                'createRetirement', 'endRetirement',
                'addWrestler', 'removeWrestler', 'addWrestlers', 'removeWrestlers',
                'addTagTeam', 'removeTagTeam', 'addTagTeams', 'removeTagTeams',
                'addMember', 'removeMember', 'disassembleAllMembers', 'disbandMembers',
                'updateStableMembers', 'getAllAssociatedManagers'
            ];

            foreach ($methods as $method) {
                expect(method_exists($this->repository, $method))
                    ->toBeTrue("Repository should have {$method} method");
            }
        });
    });

    describe('core CRUD operations', function () {
        test('can create stable with minimal data', function () {
            // Arrange
            $stableData = new StableData(
                'New Test Stable',
                null,
                new Collection(),
                new Collection(),
                new Collection()
            );

            // Act
            $stable = $this->repository->create($stableData);

            // Assert
            expect($stable)->toBeInstanceOf(Stable::class);
            expect($stable->exists)->toBeTrue();
            expect($stable->name)->toBe('New Test Stable');

            $this->assertDatabaseHas('stables', [
                'name' => 'New Test Stable',
            ]);
        });

        test('can update existing stable', function () {
            // Arrange
            $stable = $this->stable;
            $stableData = new StableData(
                'Updated Stable Name',
                null,
                new Collection(),
                new Collection(),
                new Collection()
            );

            // Act
            $updatedStable = $this->repository->update($stable, $stableData);

            // Assert
            expect($updatedStable->fresh())
                ->name->toBe('Updated Stable Name');

            $this->assertDatabaseHas('stables', [
                'id' => $stable->id,
                'name' => 'Updated Stable Name',
            ]);
        });

        test('can soft delete stable', function () {
            // Arrange
            $stable = $this->stable;

            // Act
            $this->repository->delete($stable);

            // Assert
            expect($stable->fresh()->deleted_at)->not->toBeNull();
            $this->assertSoftDeleted('stables', ['id' => $stable->id]);
        });

        test('can restore soft deleted stable', function () {
            // Arrange
            $stable = $this->stable;
            $stable->delete();
            expect($stable->trashed())->toBeTrue();

            // Act
            $this->repository->restore($stable);

            // Assert
            expect($stable->fresh()->deleted_at)->toBeNull();

            $this->assertDatabaseHas('stables', [
                'id' => $stable->id,
                'deleted_at' => null,
            ]);
        });
    });

    describe('activity management', function () {
        test('can create establishment for stable', function () {
            // Arrange
            $stable = $this->stable;
            $establishmentDate = now()->subDays(30);

            // Act
            $this->repository->createEstablishment($stable, $establishmentDate);

            // Assert
            expect($stable->fresh()->activityPeriods)->toHaveCount(1);
            expect($stable->fresh()->activityPeriods->first()->started_at)->eq($establishmentDate);

            $this->assertDatabaseHas('stables_activations', [
                'stable_id' => $stable->id,
                'started_at' => $establishmentDate,
                'ended_at' => null,
            ]);
        });

        test('can create debut for stable', function () {
            // Arrange
            $stable = $this->stable;
            $debutDate = now()->subDays(15);

            // Act
            $this->repository->createDebut($stable, $debutDate);

            // Assert
            expect($stable->fresh()->activityPeriods)->toHaveCount(1);
            expect($stable->fresh()->activityPeriods->first()->started_at)->eq($debutDate);

            $this->assertDatabaseHas('stables_activations', [
                'stable_id' => $stable->id,
                'started_at' => $debutDate,
                'ended_at' => null,
            ]);
        });

        test('can create activity period for stable', function () {
            // Arrange
            $stable = $this->stable;
            $activationDate = now()->subDays(20);

            // Act
            $this->repository->createActivity($stable, $activationDate);

            // Assert
            expect($stable->fresh()->activityPeriods)->toHaveCount(1);
            expect($stable->fresh()->activityPeriods->first()->started_at)->eq($activationDate);

            $this->assertDatabaseHas('stables_activations', [
                'stable_id' => $stable->id,
                'started_at' => $activationDate,
                'ended_at' => null,
            ]);
        });

        test('can end current activity period', function () {
            // Arrange
            $stable = $this->stable;
            $activationDate = now()->subDays(30);
            $deactivationDate = now();

            $this->repository->createActivity($stable, $activationDate);

            // Act
            $this->repository->endActivity($stable, $deactivationDate);

            // Assert
            expect($stable->fresh()->activityPeriods)->toHaveCount(1);
            expect($stable->fresh()->activityPeriods->first()->ended_at)->eq($deactivationDate);

            $this->assertDatabaseHas('stables_activations', [
                'stable_id' => $stable->id,
                'started_at' => $activationDate,
                'ended_at' => $deactivationDate,
            ]);
        });
    });

    describe('retirement management', function () {
        test('can retire stable', function () {
            // Arrange
            $stable = $this->stable;
            $retirementDate = now();

            // Act
            $this->repository->createRetirement($stable, $retirementDate);

            // Assert
            expect($stable->fresh()->retirements)->toHaveCount(1);
            expect($stable->fresh()->retirements->first()->started_at)->eq($retirementDate);

            $this->assertDatabaseHas('stables_retirements', [
                'stable_id' => $stable->id,
                'started_at' => $retirementDate,
                'ended_at' => null,
            ]);
        });

        test('can unretire stable', function () {
            // Arrange
            $stable = $this->stable;
            $retirementStart = now()->subDays(30);
            $retirementEnd = now();

            $this->repository->createRetirement($stable, $retirementStart);

            // Act
            $this->repository->endRetirement($stable, $retirementEnd);

            // Assert
            expect($stable->fresh()->retirements)->toHaveCount(1);
            expect($stable->fresh()->retirements->first()->ended_at)->eq($retirementEnd);

            $this->assertDatabaseHas('stables_retirements', [
                'stable_id' => $stable->id,
                'started_at' => $retirementStart,
                'ended_at' => $retirementEnd,
            ]);
        });
    });

    describe('wrestler relationship management', function () {
        test('can add wrestler to stable', function () {
            // Arrange
            $stable = $this->stable;
            $wrestler = $this->wrestler1;
            $joinDate = now()->subDays(30);

            // Act
            $this->repository->addWrestler($stable, $wrestler, $joinDate);

            // Assert
            expect($stable->fresh()->currentWrestlers)->toHaveCount(1);
            expect($stable->fresh()->currentWrestlers->first()->id)->toBe($wrestler->id);

            $this->assertDatabaseHas('stables_members', [
                'stable_id' => $stable->id,
                'member_id' => $wrestler->id,
                'member_type' => 'wrestler',
                'joined_at' => $joinDate,
                'left_at' => null,
            ]);
        });

        test('can remove wrestler from stable', function () {
            // Arrange
            $stable = $this->stable;
            $wrestler = $this->wrestler1;
            $joinDate = now()->subDays(30);
            $leaveDate = now();

            $this->repository->addWrestler($stable, $wrestler, $joinDate);

            // Act
            $this->repository->removeWrestler($stable, $wrestler, $leaveDate);

            // Assert
            expect($stable->fresh()->currentWrestlers)->toHaveCount(0);
            expect($stable->fresh()->previousWrestlers)->toHaveCount(1);

            $this->assertDatabaseHas('stables_members', [
                'stable_id' => $stable->id,
                'member_id' => $wrestler->id,
                'member_type' => 'wrestler',
                'joined_at' => $joinDate,
                'left_at' => $leaveDate,
            ]);
        });

        test('can add multiple wrestlers to stable', function () {
            // Arrange
            $stable = $this->stable;
            $wrestlers = collect([$this->wrestler1, $this->wrestler2]);
            $joinDate = now()->subDays(30);

            // Act
            $this->repository->addWrestlers($stable, $wrestlers, $joinDate);

            // Assert
            expect($stable->fresh()->currentWrestlers)->toHaveCount(2);
            expect($stable->fresh()->currentWrestlers->pluck('id'))
                ->toContain($this->wrestler1->id)
                ->toContain($this->wrestler2->id);
        });

        test('can remove multiple wrestlers from stable', function () {
            // Arrange
            $stable = $this->stable;
            $wrestlers = collect([$this->wrestler1, $this->wrestler2]);
            $joinDate = now()->subDays(30);
            $leaveDate = now();

            $this->repository->addWrestlers($stable, $wrestlers, $joinDate);

            // Act
            $this->repository->removeWrestlers($stable, $wrestlers, $leaveDate);

            // Assert
            expect($stable->fresh()->currentWrestlers)->toHaveCount(0);
            expect($stable->fresh()->previousWrestlers)->toHaveCount(2);
        });
    });

    describe('tag team relationship management', function () {
        test('can add tag team to stable', function () {
            // Arrange
            $stable = $this->stable;
            $tagTeam = $this->tagTeam1;
            $joinDate = now()->subDays(30);

            // Act
            $this->repository->addTagTeam($stable, $tagTeam, $joinDate);

            // Assert
            expect($stable->fresh()->currentTagTeams)->toHaveCount(1);
            expect($stable->fresh()->currentTagTeams->first()->id)->toBe($tagTeam->id);

            $this->assertDatabaseHas('stables_members', [
                'stable_id' => $stable->id,
                'member_id' => $tagTeam->id,
                'member_type' => 'tagTeam',
                'joined_at' => $joinDate,
                'left_at' => null,
            ]);
        });

        test('can remove tag team from stable', function () {
            // Arrange
            $stable = $this->stable;
            $tagTeam = $this->tagTeam1;
            $joinDate = now()->subDays(30);
            $leaveDate = now();

            $this->repository->addTagTeam($stable, $tagTeam, $joinDate);

            // Act
            $this->repository->removeTagTeam($stable, $tagTeam, $leaveDate);

            // Assert
            expect($stable->fresh()->currentTagTeams)->toHaveCount(0);
            expect($stable->fresh()->previousTagTeams)->toHaveCount(1);

            $this->assertDatabaseHas('stables_members', [
                'stable_id' => $stable->id,
                'member_id' => $tagTeam->id,
                'member_type' => 'tagTeam',
                'joined_at' => $joinDate,
                'left_at' => $leaveDate,
            ]);
        });

        test('can add multiple tag teams to stable', function () {
            // Arrange
            $stable = $this->stable;
            $tagTeams = collect([$this->tagTeam1, $this->tagTeam2]);
            $joinDate = now()->subDays(30);

            // Act
            $this->repository->addTagTeams($stable, $tagTeams, $joinDate);

            // Assert
            expect($stable->fresh()->currentTagTeams)->toHaveCount(2);
            expect($stable->fresh()->currentTagTeams->pluck('id'))
                ->toContain($this->tagTeam1->id)
                ->toContain($this->tagTeam2->id);
        });

        test('can remove multiple tag teams from stable', function () {
            // Arrange
            $stable = $this->stable;
            $tagTeams = collect([$this->tagTeam1, $this->tagTeam2]);
            $joinDate = now()->subDays(30);
            $leaveDate = now();

            $this->repository->addTagTeams($stable, $tagTeams, $joinDate);

            // Act
            $this->repository->removeTagTeams($stable, $tagTeams, $leaveDate);

            // Assert
            expect($stable->fresh()->currentTagTeams)->toHaveCount(0);
            expect($stable->fresh()->previousTagTeams)->toHaveCount(2);
        });
    });

    describe('member type resolution', function () {
        test('can add member with wrestler type resolution', function () {
            // Arrange
            $stable = $this->stable;
            $wrestler = $this->wrestler1;
            $joinDate = now()->subDays(30);

            // Act
            $this->repository->addMember($stable, $wrestler, $joinDate);

            // Assert
            expect($stable->fresh()->currentWrestlers)->toHaveCount(1);
            expect($stable->fresh()->currentWrestlers->first()->id)->toBe($wrestler->id);

            $this->assertDatabaseHas('stables_members', [
                'stable_id' => $stable->id,
                'member_id' => $wrestler->id,
                'member_type' => 'wrestler',
                'joined_at' => $joinDate,
                'left_at' => null,
            ]);
        });

        test('can add member with tag team type resolution', function () {
            // Arrange
            $stable = $this->stable;
            $tagTeam = $this->tagTeam1;
            $joinDate = now()->subDays(30);

            // Act
            $this->repository->addMember($stable, $tagTeam, $joinDate);

            // Assert
            expect($stable->fresh()->currentTagTeams)->toHaveCount(1);
            expect($stable->fresh()->currentTagTeams->first()->id)->toBe($tagTeam->id);

            $this->assertDatabaseHas('stables_members', [
                'stable_id' => $stable->id,
                'member_id' => $tagTeam->id,
                'member_type' => 'tagTeam',
                'joined_at' => $joinDate,
                'left_at' => null,
            ]);
        });

        test('adding member with unsupported type is handled gracefully', function () {
            // Arrange
            $stable = $this->stable;
            $invalidMember = Manager::factory()->create(); // Managers cannot be direct stable members
            $joinDate = now()->subDays(30);

            // Act & Assert - Manager addition should be handled gracefully (no-op)
            $this->repository->addMember($stable, $invalidMember, $joinDate);

            // Assert - No members should be added since managers aren't direct stable members
            expect($stable->fresh()->currentWrestlers)->toHaveCount(0);
            expect($stable->fresh()->currentTagTeams)->toHaveCount(0);
        });

        test('can remove member with wrestler type resolution', function () {
            // Arrange
            $stable = $this->stable;
            $wrestler = $this->wrestler1;
            $joinDate = now()->subDays(30);
            $leaveDate = now();

            $this->repository->addMember($stable, $wrestler, $joinDate);

            // Act
            $this->repository->removeMember($stable, $wrestler, $leaveDate);

            // Assert
            expect($stable->fresh()->currentWrestlers)->toHaveCount(0);
            expect($stable->fresh()->previousWrestlers)->toHaveCount(1);

            $this->assertDatabaseHas('stables_members', [
                'stable_id' => $stable->id,
                'member_id' => $wrestler->id,
                'member_type' => 'wrestler',
                'joined_at' => $joinDate,
                'left_at' => $leaveDate,
            ]);
        });

        test('can remove member with tag team type resolution', function () {
            // Arrange
            $stable = $this->stable;
            $tagTeam = $this->tagTeam1;
            $joinDate = now()->subDays(30);
            $leaveDate = now();

            $this->repository->addMember($stable, $tagTeam, $joinDate);

            // Act
            $this->repository->removeMember($stable, $tagTeam, $leaveDate);

            // Assert
            expect($stable->fresh()->currentTagTeams)->toHaveCount(0);
            expect($stable->fresh()->previousTagTeams)->toHaveCount(1);

            $this->assertDatabaseHas('stables_members', [
                'stable_id' => $stable->id,
                'member_id' => $tagTeam->id,
                'member_type' => 'tagTeam',
                'joined_at' => $joinDate,
                'left_at' => $leaveDate,
            ]);
        });

        test('throws exception for invalid member type', function () {
            // Arrange
            $stable = $this->stable;
            $invalidMember = new stdClass(); // Invalid member type
            $joinDate = now()->subDays(30);

            // Act & Assert
            expect(function () use ($stable, $invalidMember, $joinDate) {
                $this->repository->addMember($stable, $invalidMember, $joinDate);
            })->toThrow(TypeError::class);
        });
    });

    describe('complex stable operations', function () {
        test('can disassemble all members from stable', function () {
            // Arrange
            $stable = $this->stable;
            $wrestler = $this->wrestler1;
            $tagTeam = $this->tagTeam1;
            $joinDate = now()->subDays(30);
            $disassemblyDate = now();

            $this->repository->addWrestler($stable, $wrestler, $joinDate);
            $this->repository->addTagTeam($stable, $tagTeam, $joinDate);

            // Act
            $this->repository->disassembleAllMembers($stable, $disassemblyDate);

            // Assert
            expect($stable->fresh()->currentWrestlers)->toHaveCount(0);
            expect($stable->fresh()->currentTagTeams)->toHaveCount(0);
            expect($stable->fresh()->previousWrestlers)->toHaveCount(1);
            expect($stable->fresh()->previousTagTeams)->toHaveCount(1);
        });

        test('disband members is alias for disassemble all members', function () {
            // Arrange
            $stable = $this->stable;
            $wrestler = $this->wrestler1;
            $tagTeam = $this->tagTeam1;
            $joinDate = now()->subDays(30);
            $disbandDate = now();

            $this->repository->addWrestler($stable, $wrestler, $joinDate);
            $this->repository->addTagTeam($stable, $tagTeam, $joinDate);

            // Act
            $this->repository->disbandMembers($stable, $disbandDate);

            // Assert
            expect($stable->fresh()->currentWrestlers)->toHaveCount(0);
            expect($stable->fresh()->currentTagTeams)->toHaveCount(0);
            expect($stable->fresh()->previousWrestlers)->toHaveCount(1);
            expect($stable->fresh()->previousTagTeams)->toHaveCount(1);
        });

        test('can update stable members by adding and removing', function () {
            // Arrange
            $stable = $this->stable;
            $existingWrestler = $this->wrestler1;
            $newWrestler = $this->wrestler2;
            $existingTagTeam = $this->tagTeam1;
            $newTagTeam = $this->tagTeam2;

            $initialJoinDate = now()->subDays(30);
            $updateDate = now()->subDays(15);

            // Add initial members
            $this->repository->addWrestler($stable, $existingWrestler, $initialJoinDate);
            $this->repository->addTagTeam($stable, $existingTagTeam, $initialJoinDate);

            $newWrestlers = collect([$newWrestler]);
            $newTagTeams = collect([$newTagTeam]);
            $managers = collect(); // Empty collection for deprecated managers parameter

            // Act
            $this->repository->updateStableMembers($stable, $newWrestlers, $newTagTeams, $managers, $updateDate);

            // Assert
            expect($stable->fresh()->currentWrestlers)->toHaveCount(1);
            expect($stable->fresh()->currentTagTeams)->toHaveCount(1);
            expect($stable->fresh()->currentWrestlers->first()->id)->toBe($newWrestler->id);
            expect($stable->fresh()->currentTagTeams->first()->id)->toBe($newTagTeam->id);
        });

        test('update stable members uses current time as default', function () {
            // Arrange
            $stable = $this->stable;
            $wrestler = $this->wrestler1;
            $initialJoinDate = now()->subDays(30);

            $this->repository->addWrestler($stable, $wrestler, $initialJoinDate);

            $emptyWrestlers = collect();
            $emptyTagTeams = collect();
            $managers = collect(); // Empty collection for deprecated managers parameter

            // Act
            $this->repository->updateStableMembers($stable, $emptyWrestlers, $emptyTagTeams, $managers);

            // Assert
            expect($stable->fresh()->currentWrestlers)->toHaveCount(0);
            expect($stable->fresh()->previousWrestlers)->toHaveCount(1);
        });

        test('handles empty collections gracefully', function () {
            // Arrange
            $stable = $this->stable;
            $wrestler = $this->wrestler1;
            $tagTeam = $this->tagTeam1;
            $joinDate = now()->subDays(30);
            $updateDate = now();

            $this->repository->addWrestler($stable, $wrestler, $joinDate);
            $this->repository->addTagTeam($stable, $tagTeam, $joinDate);

            $emptyWrestlers = collect();
            $emptyTagTeams = collect();
            $managers = collect(); // Empty collection for deprecated managers parameter

            // Act - Update with empty collections
            $this->repository->updateStableMembers($stable, $emptyWrestlers, $emptyTagTeams, $managers, $updateDate);

            // Assert
            expect($stable->fresh()->currentWrestlers)->toHaveCount(0);
            expect($stable->fresh()->currentTagTeams)->toHaveCount(0);
            expect($stable->fresh()->previousWrestlers)->toHaveCount(1);
            expect($stable->fresh()->previousTagTeams)->toHaveCount(1);
        });
    });

    describe('trait integration', function () {
        test('integrates ManagesActivity trait correctly', function () {
            // Arrange
            $stable = $this->stable;
            $activationDate = now()->subDays(30);

            // Act
            $this->repository->createActivity($stable, $activationDate);

            // Assert
            expect($stable->fresh()->activityPeriods)->toHaveCount(1);
            expect($stable->fresh()->activityPeriods->first())
                ->started_at->eq($activationDate)
                ->ended_at->toBeNull();
        });

        test('integrates ManagesRetirement trait correctly', function () {
            // Arrange
            $stable = $this->stable;
            $retirementDate = now();

            // Act
            $this->repository->createRetirement($stable, $retirementDate);

            // Assert
            expect($stable->fresh()->retirements)->toHaveCount(1);
            expect($stable->fresh()->retirements->first())
                ->started_at->eq($retirementDate)
                ->ended_at->toBeNull();
        });

        test('integrates ManagesMembers trait correctly', function () {
            // Arrange
            $stable = $this->stable;
            $wrestler = $this->wrestler1;
            $joinDate = now()->subDays(30);

            // Act
            $this->repository->addWrestler($stable, $wrestler, $joinDate);

            // Assert
            expect($stable->fresh()->currentWrestlers)->toHaveCount(1);
            expect($stable->fresh()->currentWrestlers->first()->id)->toBe($wrestler->id);
        });
    });

    describe('edge cases and error handling', function () {
        test('adding same member twice creates multiple records', function () {
            // Arrange
            $stable = $this->stable;
            $wrestler = $this->wrestler1;
            $firstJoinDate = now()->subDays(30);
            $secondJoinDate = now()->subDays(15);

            $this->repository->addWrestler($stable, $wrestler, $firstJoinDate);

            // Act
            $this->repository->addWrestler($stable, $wrestler, $secondJoinDate);

            // Assert
            expect($stable->fresh()->wrestlers)->toHaveCount(2);
            expect($stable->fresh()->currentWrestlers)->toHaveCount(2);
        });

        test('removing non-existent member does not cause errors', function () {
            // Arrange
            $stable = $this->stable;
            $wrestler = $this->wrestler1;
            $leaveDate = now();

            // Act & Assert - Should not throw exception
            $this->repository->removeWrestler($stable, $wrestler, $leaveDate);

            expect(true)->toBeTrue(); // Test completed without exception
        });

        test('handles date edge cases correctly', function () {
            // Arrange
            $stable = $this->stable;
            $wrestler = $this->wrestler1;
            $sameDateTime = now();

            // Act - Add and remove on same datetime
            $this->repository->addWrestler($stable, $wrestler, $sameDateTime);
            $this->repository->removeWrestler($stable, $wrestler, $sameDateTime);

            // Assert
            expect($stable->fresh()->currentWrestlers)->toHaveCount(0);
            expect($stable->fresh()->previousWrestlers)->toHaveCount(1);

            $this->assertDatabaseHas('stables_members', [
                'stable_id' => $stable->id,
                'member_id' => $wrestler->id,
                'joined_at' => $sameDateTime,
                'left_at' => $sameDateTime,
            ]);
        });
    });

    describe('business logic query methods', function () {
        test('can query all associated managers through members', function () {
            // Arrange
            $stable = $this->stable;

            // Create managers locally - they associate with stable through wrestler/tag team management
            $manager1 = Manager::factory()->create(['first_name' => 'Manager', 'last_name' => 'One']);
            $manager2 = Manager::factory()->create(['first_name' => 'Manager', 'last_name' => 'Two']);

            $wrestler = Wrestler::factory()
                ->hasAttached($manager1, ['hired_at' => now()->subDays(30)])
                ->create();
            $tagTeam = TagTeam::factory()
                ->hasAttached($manager2, ['hired_at' => now()->subDays(30)])
                ->create();

            $this->repository->addWrestler($stable, $wrestler, now()->subDays(30));
            $this->repository->addTagTeam($stable, $tagTeam, now()->subDays(30));

            // Act
            $associatedManagers = $this->repository->getAllAssociatedManagers($stable);

            // Assert
            expect($associatedManagers)->toBeInstanceOf(SupportCollection::class);
            expect($associatedManagers)->toHaveCount(2);
            expect($associatedManagers->pluck('id'))
                ->toContain($manager1->id)
                ->toContain($manager2->id);
        });

        test('returns empty collection when no associated managers', function () {
            // Arrange
            $stable = $this->stable;
            $wrestler = Wrestler::factory()->create();
            $tagTeam = TagTeam::factory()->create();

            $this->repository->addWrestler($stable, $wrestler, now()->subDays(30));
            $this->repository->addTagTeam($stable, $tagTeam, now()->subDays(30));

            // Act
            $associatedManagers = $this->repository->getAllAssociatedManagers($stable);

            // Assert
            expect($associatedManagers)->toBeInstanceOf(SupportCollection::class);
            expect($associatedManagers)->toHaveCount(0);
        });

        test('deduplicates managers associated with multiple members', function () {
            // Arrange
            $stable = $this->stable;

            // Create shared manager locally - associates with stable through multiple members
            $sharedManager = Manager::factory()->create(['first_name' => 'Shared', 'last_name' => 'Manager']);

            $wrestler = Wrestler::factory()
                ->hasAttached($sharedManager, ['hired_at' => now()->subDays(30)])
                ->create();
            $tagTeam = TagTeam::factory()
                ->hasAttached($sharedManager, ['hired_at' => now()->subDays(30)])
                ->create();

            $this->repository->addWrestler($stable, $wrestler, now()->subDays(30));
            $this->repository->addTagTeam($stable, $tagTeam, now()->subDays(30));

            // Act
            $associatedManagers = $this->repository->getAllAssociatedManagers($stable);

            // Assert
            expect($associatedManagers)->toHaveCount(1);
            expect($associatedManagers->first()->id)->toBe($sharedManager->id);
        });
    });
});
