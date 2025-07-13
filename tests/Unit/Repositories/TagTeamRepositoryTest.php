<?php

declare(strict_types=1);

use App\Data\TagTeams\TagTeamData;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\TagTeams\TagTeamEmployment;
use App\Models\TagTeams\TagTeamRetirement;
use App\Models\TagTeams\TagTeamSuspension;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\Concerns\ManagesEmployment;
use App\Repositories\Concerns\ManagesMembers;
use App\Repositories\Concerns\ManagesRetirement;
use App\Repositories\Concerns\ManagesSuspension;
use App\Repositories\Contracts\ManagesEmployment as ManagesEmploymentContract;
use App\Repositories\Contracts\ManagesRetirement as ManagesRetirementContract;
use App\Repositories\Contracts\ManagesSuspension as ManagesSuspensionContract;
use App\Repositories\Contracts\ManagesTagTeamMembers;
use App\Repositories\Contracts\TagTeamRepositoryInterface;
use App\Repositories\TagTeamRepository;
use Illuminate\Support\Carbon;

/**
 * Unit tests for TagTeamRepository business logic and data operations.
 *
 * UNIT TEST SCOPE:
 * - Repository configuration and structure verification
 * - Core CRUD operations (create, update, delete, restore)
 * - Trait-based functionality (employment, retirement, suspension management)
 * - Tag team specific relationship management (wrestlers, managers)
 * - Business logic query methods
 *
 * These tests verify that the TagTeamRepository correctly implements
 * all business operations and data persistence requirements.
 *
 * @see TagTeamRepository
 */
describe('TagTeamRepository Unit Tests', function () {
    beforeEach(function () {
        $this->repository = app(TagTeamRepository::class);
    });

    describe('repository configuration', function () {
        test('repository can be resolved from container', function () {
            expect($this->repository)->toBeInstanceOf(TagTeamRepository::class);
            expect($this->repository)->toBeInstanceOf(TagTeamRepositoryInterface::class);
        });

        test('repository implements all required contracts', function () {
            expect($this->repository)->toBeInstanceOf(ManagesEmploymentContract::class);
            expect($this->repository)->toBeInstanceOf(ManagesRetirementContract::class);
            expect($this->repository)->toBeInstanceOf(ManagesSuspensionContract::class);
            expect($this->repository)->toBeInstanceOf(ManagesTagTeamMembers::class);
        });

        test('repository uses all required traits', function () {
            expect(TagTeamRepository::class)->usesTrait(ManagesEmployment::class);
            expect(TagTeamRepository::class)->usesTrait(ManagesMembers::class);
            expect(TagTeamRepository::class)->usesTrait(ManagesRetirement::class);
            expect(TagTeamRepository::class)->usesTrait(ManagesSuspension::class);
        });

        test('repository has all expected methods', function () {
            $methods = [
                'create', 'update', 'restore',
                'addWrestler', 'removeWrestler', 'addWrestlers', 'removeWrestlers', 
                'syncWrestlers', 'updateTagTeamPartners',
                'addManager', 'removeManager', 'addManagers', 'removeManagers',
                'createEmployment', 'createRelease', 'createRetirement', 'endRetirement',
                'createSuspension', 'endSuspension'
            ];

            foreach ($methods as $method) {
                expect(method_exists($this->repository, $method))
                    ->toBeTrue("Repository should have {$method} method");
            }
        });
    });

    describe('core CRUD operations', function () {
        test('can create tag team with required data', function () {
            // Arrange
            $data = new TagTeamData('The Awesome Tag Team', 'Double Powerbomb', null, null, null, null);

            // Act
            $tagTeam = $this->repository->create($data);

            // Assert
            expect($tagTeam)
                ->toBeInstanceOf(TagTeam::class)
                ->name->toBe('The Awesome Tag Team')
                ->signature_move->toBe('Double Powerbomb');

            $this->assertDatabaseHas('tag_teams', [
                'name' => 'The Awesome Tag Team',
                'signature_move' => 'Double Powerbomb',
            ]);
        });

        test('can create tag team with null signature move', function () {
            // Arrange
            $data = new TagTeamData('Simple Tag Team', null, null, null, null, null);

            // Act
            $tagTeam = $this->repository->create($data);

            // Assert
            expect($tagTeam)
                ->name->toBe('Simple Tag Team')
                ->signature_move->toBeNull();
        });

        test('can update existing tag team', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create(['name' => 'Old Name', 'signature_move' => 'Old Move']);
            $data = new TagTeamData('New Name', 'New Move', null, null, null, null);

            // Act
            $updatedTagTeam = $this->repository->update($tagTeam, $data);

            // Assert
            expect($updatedTagTeam)
                ->name->toBe('New Name')
                ->signature_move->toBe('New Move');

            $this->assertDatabaseHas('tag_teams', [
                'id' => $tagTeam->id,
                'name' => 'New Name',
                'signature_move' => 'New Move',
            ]);
        });

        test('can restore soft deleted tag team', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->trashed()->create();

            // Act
            $this->repository->restore($tagTeam);

            // Assert
            expect($tagTeam->fresh()->deleted_at)->toBeNull();
            $this->assertDatabaseHas('tag_teams', [
                'id' => $tagTeam->id,
                'deleted_at' => null,
            ]);
        });
    });

    describe('employment management', function () {
        test('can create employment for tag team', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create();
            $employmentDate = now()->subDays(30);

            // Act
            $this->repository->createEmployment($tagTeam, $employmentDate);

            // Assert
            expect($tagTeam->fresh()->employments)->toHaveCount(1);
            expect($tagTeam->fresh()->employments->first()->started_at)->eq($employmentDate);
            
            $this->assertDatabaseHas('tag_teams_employments', [
                'tag_team_id' => $tagTeam->id,
                'started_at' => $employmentDate,
                'ended_at' => null,
            ]);
        });

        test('can release employed tag team', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->bookable()->create();
            $releaseDate = now();

            // Act
            $this->repository->createRelease($tagTeam, $releaseDate);

            // Assert
            expect($tagTeam->fresh()->employments->first()->ended_at)->eq($releaseDate);
            
            $this->assertDatabaseHas('tag_teams_employments', [
                'tag_team_id' => $tagTeam->id,
                'ended_at' => $releaseDate,
            ]);
        });

        test('updates existing employment when creating new employment', function () {
            // Arrange
            $tagTeam = TagTeam::factory()
                ->has(TagTeamEmployment::factory()->started(now()->subDays(10)), 'employments')
                ->create();
            $newEmploymentDate = now()->subDays(5);

            // Act
            $this->repository->createEmployment($tagTeam, $newEmploymentDate);

            // Assert
            expect($tagTeam->fresh()->employments)->toHaveCount(1);
            expect($tagTeam->fresh()->employments->first()->started_at)->eq($newEmploymentDate);
        });
    });

    describe('retirement management', function () {
        test('can retire tag team', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->bookable()->create();
            $retirementDate = now();

            // Act
            $this->repository->createRetirement($tagTeam, $retirementDate);

            // Assert
            expect($tagTeam->fresh()->retirements)->toHaveCount(1);
            expect($tagTeam->fresh()->retirements->first()->started_at)->eq($retirementDate);
            
            $this->assertDatabaseHas('tag_teams_retirements', [
                'tag_team_id' => $tagTeam->id,
                'started_at' => $retirementDate,
                'ended_at' => null,
            ]);
        });

        test('can unretire tag team', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->retired()->create();
            $unretirementDate = now();

            // Act
            $this->repository->endRetirement($tagTeam, $unretirementDate);

            // Assert
            expect($tagTeam->fresh()->retirements->first()->ended_at)->eq($unretirementDate);
            
            $this->assertDatabaseHas('tag_teams_retirements', [
                'tag_team_id' => $tagTeam->id,
                'ended_at' => $unretirementDate,
            ]);
        });
    });

    describe('suspension management', function () {
        test('can suspend tag team', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->bookable()->create();
            $suspensionDate = now();

            // Act
            $this->repository->createSuspension($tagTeam, $suspensionDate);

            // Assert
            expect($tagTeam->fresh()->suspensions)->toHaveCount(1);
            expect($tagTeam->fresh()->suspensions->first()->started_at)->eq($suspensionDate);
            
            $this->assertDatabaseHas('tag_teams_suspensions', [
                'tag_team_id' => $tagTeam->id,
                'started_at' => $suspensionDate,
                'ended_at' => null,
            ]);
        });

        test('can reinstate suspended tag team', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->suspended()->create();
            $reinstatementDate = now();

            // Act
            $this->repository->endSuspension($tagTeam, $reinstatementDate);

            // Assert
            expect($tagTeam->fresh()->suspensions->first()->ended_at)->eq($reinstatementDate);
            
            $this->assertDatabaseHas('tag_teams_suspensions', [
                'tag_team_id' => $tagTeam->id,
                'ended_at' => $reinstatementDate,
            ]);
        });
    });

    describe('wrestler relationship management', function () {
        test('can add wrestler to tag team', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create();
            $wrestler = Wrestler::factory()->create();
            $joinDate = now();

            // Act
            $this->repository->addWrestler($tagTeam, $wrestler, $joinDate);

            // Assert
            expect($tagTeam->fresh()->wrestlers)->toHaveCount(1);
            expect($tagTeam->fresh()->wrestlers->first()->id)->toBe($wrestler->id);
            
            $this->assertDatabaseHas('tag_teams_wrestlers', [
                'tag_team_id' => $tagTeam->id,
                'wrestler_id' => $wrestler->id,
                'joined_at' => $joinDate,
                'left_at' => null,
            ]);
        });

        test('can remove wrestler from tag team', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create();
            $wrestler = Wrestler::factory()->create();
            $joinDate = now()->subDays(30);
            $leaveDate = now();
            $this->repository->addWrestler($tagTeam, $wrestler, $joinDate);

            // Act
            $this->repository->removeWrestler($tagTeam, $wrestler, $leaveDate);

            // Assert
            $this->assertDatabaseHas('tag_teams_wrestlers', [
                'tag_team_id' => $tagTeam->id,
                'wrestler_id' => $wrestler->id,
                'left_at' => $leaveDate,
            ]);
        });

        test('can add multiple wrestlers to tag team', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create();
            $wrestlers = Wrestler::factory()->count(2)->create();
            $joinDate = now();

            // Act
            $this->repository->addWrestlers($tagTeam, $wrestlers, $joinDate);

            // Assert
            expect($tagTeam->fresh()->wrestlers)->toHaveCount(2);
            
            foreach ($wrestlers as $wrestler) {
                $this->assertDatabaseHas('tag_teams_wrestlers', [
                    'tag_team_id' => $tagTeam->id,
                    'wrestler_id' => $wrestler->id,
                    'joined_at' => $joinDate,
                ]);
            }
        });

        test('can remove multiple wrestlers from tag team', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create();
            $wrestlers = Wrestler::factory()->count(2)->create();
            $joinDate = now()->subDays(30);
            $leaveDate = now();
            $this->repository->addWrestlers($tagTeam, $wrestlers, $joinDate);

            // Act
            $this->repository->removeWrestlers($tagTeam, $wrestlers, $leaveDate);

            // Assert
            foreach ($wrestlers as $wrestler) {
                $this->assertDatabaseHas('tag_teams_wrestlers', [
                    'tag_team_id' => $tagTeam->id,
                    'wrestler_id' => $wrestler->id,
                    'left_at' => $leaveDate,
                ]);
            }
        });

        test('can sync wrestlers for tag team', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create();
            $oldWrestlers = Wrestler::factory()->count(2)->create();
            $newWrestlers = Wrestler::factory()->count(2)->create();
            $syncDate = now();
            
            // Add old wrestlers first
            $this->repository->addWrestlers($tagTeam, $oldWrestlers, now()->subDays(30));

            // Act
            $this->repository->syncWrestlers($tagTeam, $oldWrestlers, $newWrestlers, $syncDate);

            // Assert
            // Old wrestlers should be removed
            foreach ($oldWrestlers as $wrestler) {
                $this->assertDatabaseHas('tag_teams_wrestlers', [
                    'tag_team_id' => $tagTeam->id,
                    'wrestler_id' => $wrestler->id,
                    'left_at' => $syncDate,
                ]);
            }

            // New wrestlers should be added
            foreach ($newWrestlers as $wrestler) {
                $this->assertDatabaseHas('tag_teams_wrestlers', [
                    'tag_team_id' => $tagTeam->id,
                    'wrestler_id' => $wrestler->id,
                    'joined_at' => $syncDate,
                    'left_at' => null,
                ]);
            }
        });

        test('can update tag team partners when no current wrestlers', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create();
            $wrestlers = Wrestler::factory()->count(2)->create();
            $joinDate = now();

            // Act
            $this->repository->updateTagTeamPartners($tagTeam, $wrestlers, $joinDate);

            // Assert
            expect($tagTeam->fresh()->currentWrestlers)->toHaveCount(2);
            
            foreach ($wrestlers as $wrestler) {
                $this->assertDatabaseHas('tag_teams_wrestlers', [
                    'tag_team_id' => $tagTeam->id,
                    'wrestler_id' => $wrestler->id,
                    'joined_at' => $joinDate,
                    'left_at' => null,
                ]);
            }
        });

        test('can update tag team partners with existing wrestlers', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create();
            $oldWrestlers = Wrestler::factory()->count(2)->create();
            $newWrestlers = Wrestler::factory()->count(2)->create();
            $initialDate = now()->subDays(30);
            $updateDate = now();
            
            // Add old wrestlers first
            $this->repository->addWrestlers($tagTeam, $oldWrestlers, $initialDate);

            // Act
            $this->repository->updateTagTeamPartners($tagTeam, $newWrestlers, $updateDate);

            // Assert
            expect($tagTeam->fresh()->currentWrestlers)->toHaveCount(2);
            
            // Old wrestlers should be removed
            foreach ($oldWrestlers as $wrestler) {
                $this->assertDatabaseHas('tag_teams_wrestlers', [
                    'tag_team_id' => $tagTeam->id,
                    'wrestler_id' => $wrestler->id,
                    'left_at' => $updateDate,
                ]);
            }

            // New wrestlers should be added
            foreach ($newWrestlers as $wrestler) {
                $this->assertDatabaseHas('tag_teams_wrestlers', [
                    'tag_team_id' => $tagTeam->id,
                    'wrestler_id' => $wrestler->id,
                    'joined_at' => $updateDate,
                    'left_at' => null,
                ]);
            }
        });
    });

    describe('manager relationship management', function () {
        test('can add manager to tag team', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create();
            $manager = Manager::factory()->create();
            $hireDate = now();

            // Act
            $this->repository->addManager($tagTeam, $manager, $hireDate);

            // Assert
            expect($tagTeam->fresh()->managers)->toHaveCount(1);
            expect($tagTeam->fresh()->managers->first()->id)->toBe($manager->id);
            
            $this->assertDatabaseHas('tag_teams_managers', [
                'tag_team_id' => $tagTeam->id,
                'manager_id' => $manager->id,
                'hired_at' => $hireDate,
                'fired_at' => null,
            ]);
        });

        test('can remove manager from tag team', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create();
            $manager = Manager::factory()->create();
            $hireDate = now()->subDays(30);
            $fireDate = now();
            $this->repository->addManager($tagTeam, $manager, $hireDate);

            // Act
            $this->repository->removeManager($tagTeam, $manager, $fireDate);

            // Assert
            $this->assertDatabaseHas('tag_teams_managers', [
                'tag_team_id' => $tagTeam->id,
                'manager_id' => $manager->id,
                'fired_at' => $fireDate,
            ]);
        });

        test('can add multiple managers to tag team', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create();
            $managers = Manager::factory()->count(2)->create();
            $hireDate = now();

            // Act
            $this->repository->addManagers($tagTeam, $managers, $hireDate);

            // Assert
            expect($tagTeam->fresh()->managers)->toHaveCount(2);
            
            foreach ($managers as $manager) {
                $this->assertDatabaseHas('tag_teams_managers', [
                    'tag_team_id' => $tagTeam->id,
                    'manager_id' => $manager->id,
                    'hired_at' => $hireDate,
                ]);
            }
        });

        test('can remove multiple managers from tag team', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create();
            $managers = Manager::factory()->count(2)->create();
            $hireDate = now()->subDays(30);
            $fireDate = now();
            $this->repository->addManagers($tagTeam, $managers, $hireDate);

            // Act
            $this->repository->removeManagers($tagTeam, $managers, $fireDate);

            // Assert
            foreach ($managers as $manager) {
                $this->assertDatabaseHas('tag_teams_managers', [
                    'tag_team_id' => $tagTeam->id,
                    'manager_id' => $manager->id,
                    'fired_at' => $fireDate,
                ]);
            }
        });
    });
});