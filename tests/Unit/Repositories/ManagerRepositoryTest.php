<?php

declare(strict_types=1);

use App\Data\Managers\ManagerData;
use App\Models\Managers\Manager;
use App\Models\Managers\ManagerEmployment;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\Concerns\ManagesEmployment;
use App\Repositories\Concerns\ManagesInjury;
use App\Repositories\Concerns\ManagesRetirement;
use App\Repositories\Concerns\ManagesSuspension;
use App\Repositories\Contracts\ManagesEmployment as ManagesEmploymentContract;
use App\Repositories\Contracts\ManagesInjury as ManagesInjuryContract;
use App\Repositories\Contracts\ManagesRetirement as ManagesRetirementContract;
use App\Repositories\Contracts\ManagesSuspension as ManagesSuspensionContract;
use App\Repositories\Contracts\ManagerRepositoryInterface;
use App\Repositories\ManagerRepository;

use function Spatie\PestPluginTestTime\testTime;

/**
 * Unit tests for ManagerRepository business logic and data operations.
 *
 * UNIT TEST SCOPE:
 * - Repository configuration and structure verification
 * - Core CRUD operations (create, update, delete, restore)
 * - Trait-based functionality (employment, retirement, suspension, injury management)
 * - Manager specific relationship management (wrestlers, tag teams)
 * - Business logic query methods
 *
 * These tests verify that the ManagerRepository correctly implements
 * all business operations and data persistence requirements.
 *
 * @see ManagerRepository
 */
describe('ManagerRepository Unit Tests', function () {
    beforeEach(function () {
        testTime()->freeze();
        $this->repository = app(ManagerRepository::class);
    });

    describe('repository configuration', function () {
        test('repository can be resolved from container', function () {
            expect($this->repository)->toBeInstanceOf(ManagerRepository::class);
            expect($this->repository)->toBeInstanceOf(ManagerRepositoryInterface::class);
        });

        test('repository implements all required contracts', function () {
            expect($this->repository)->toBeInstanceOf(ManagesEmploymentContract::class);
            expect($this->repository)->toBeInstanceOf(ManagesInjuryContract::class);
            expect($this->repository)->toBeInstanceOf(ManagesRetirementContract::class);
            expect($this->repository)->toBeInstanceOf(ManagesSuspensionContract::class);
        });

        test('repository uses all required traits', function () {
            expect(ManagerRepository::class)->usesTrait(ManagesEmployment::class);
            expect(ManagerRepository::class)->usesTrait(ManagesInjury::class);
            expect(ManagerRepository::class)->usesTrait(ManagesRetirement::class);
            expect(ManagerRepository::class)->usesTrait(ManagesSuspension::class);
        });

        test('repository has all expected methods', function () {
            $methods = [
                'create', 'update', 'restore',
                'createEmployment', 'createRelease', 'createRetirement', 'endRetirement',
                'createSuspension', 'endSuspension', 'createInjury', 'endInjury',
                'removeFromCurrentTagTeams', 'removeFromCurrentWrestlers'
            ];

            foreach ($methods as $method) {
                expect(method_exists($this->repository, $method))
                    ->toBeTrue("Repository should have {$method} method");
            }
        });
    });

    describe('core CRUD operations', function () {
        test('can create manager with required data', function () {
            // Arrange
            $data = new ManagerData('Hulk', 'Hogan', null);

            // Act
            $manager = $this->repository->create($data);

            // Assert
            expect($manager)->toBeInstanceOf(Manager::class);
            expect($manager->first_name)->toEqual('Hulk');
            expect($manager->last_name)->toEqual('Hogan');

            $this->assertDatabaseHas('managers', [
                'first_name' => 'Hulk',
                'last_name' => 'Hogan',
            ]);
        });

        test('can update existing manager', function () {
            // Arrange
            $manager = Manager::factory()->create();
            $data = new ManagerData('Updated', 'Name', null);

            // Act
            $updatedManager = $this->repository->update($manager, $data);

            // Assert
            expect($updatedManager->fresh())
                ->first_name->toBe('Updated')
                ->last_name->toBe('Name');

            $this->assertDatabaseHas('managers', [
                'id' => $manager->id,
                'first_name' => 'Updated',
                'last_name' => 'Name',
            ]);
        });

        test('can soft delete manager', function () {
            // Arrange
            $manager = Manager::factory()->create();

            // Act
            $this->repository->delete($manager);

            // Assert
            expect($manager->deleted_at)->not()->toBeNull();
            $this->assertSoftDeleted('managers', ['id' => $manager->id]);
        });

        test('can restore soft deleted manager', function () {
            // Arrange
            $manager = Manager::factory()->trashed()->create();

            // Act
            $this->repository->restore($manager);

            // Assert
            expect($manager->fresh()->deleted_at)->toBeNull();
            $this->assertDatabaseHas('managers', [
                'id' => $manager->id,
                'deleted_at' => null,
            ]);
        });
    });

    describe('employment management', function () {
        test('can create employment for manager', function () {
            // Arrange
            $manager = Manager::factory()->create();
            $employmentDate = now()->subDays(30);

            // Act
            $this->repository->createEmployment($manager, $employmentDate);

            // Assert
            expect($manager->fresh()->employments)->toHaveCount(1);
            expect($manager->fresh()->employments->first()->started_at)->eq($employmentDate);

            $this->assertDatabaseHas('managers_employments', [
                'manager_id' => $manager->id,
                'started_at' => $employmentDate,
                'ended_at' => null,
            ]);
        });

        test('can release employed manager', function () {
            // Arrange
            $manager = Manager::factory()->available()->create();
            $releaseDate = now();

            // Act
            $this->repository->createRelease($manager, $releaseDate);

            // Assert
            expect($manager->fresh()->employments)->toHaveCount(1);
            expect($manager->fresh()->employments->first()->started_at)->eq($releaseDate->copy()->subDays(3));

            $this->assertDatabaseHas('managers_employments', [
                'manager_id' => $manager->id,
                'ended_at' => $releaseDate,
            ]);
        });

        test('updates existing employment when creating new employment', function () {
            // Arrange
            $manager = Manager::factory()
                ->has(ManagerEmployment::factory()->started(now()->subDays(10)), 'employments')
                ->create();
            $newEmploymentDate = now()->subDays(5);

            // Act
            $this->repository->createEmployment($manager, $newEmploymentDate);

            // Assert
            expect($manager->fresh()->employments)->toHaveCount(1);
            expect($manager->fresh()->employments->first()->started_at)->eq($newEmploymentDate);
        });
    });

    describe('injury management', function () {
        test('can injure manager', function () {
            // Arrange
            $manager = Manager::factory()->create();
            $injuryDate = now();

            // Act
            $this->repository->createInjury($manager, $injuryDate);

            // Assert
            expect($manager->fresh()->injuries)->toHaveCount(1);
            expect($manager->fresh()->injuries->first()->started_at)->eq($injuryDate);

            $this->assertDatabaseHas('managers_injuries', [
                'manager_id' => $manager->id,
                'started_at' => $injuryDate,
                'ended_at' => null,
            ]);
        });

        test('can clear injured manager', function () {
            // Arrange
            $manager = Manager::factory()->injured()->create();
            $clearDate = now();

            // Act
            $this->repository->endInjury($manager, $clearDate);

            // Assert
            expect($manager->fresh()->injuries)->toHaveCount(1);
            expect($manager->fresh()->injuries->first()->started_at)->eq($clearDate->copy()->subDays(3));

            $this->assertDatabaseHas('managers_injuries', [
                'manager_id' => $manager->id,
                'ended_at' => $clearDate,
            ]);
        });
    });

    describe('retirement management', function () {
        test('can retire manager', function () {
            // Arrange
            $manager = Manager::factory()->available()->create();
            $retirementDate = now();

            // Act
            $this->repository->createRetirement($manager, $retirementDate);

            // Assert
            expect($manager->fresh()->retirements)->toHaveCount(1);
            expect($manager->fresh()->retirements->first()->started_at)->eq($retirementDate);

            $this->assertDatabaseHas('managers_retirements', [
                'manager_id' => $manager->id,
                'started_at' => $retirementDate,
                'ended_at' => null,
            ]);
        });

        test('can unretire manager', function () {
            // Arrange
            $manager = Manager::factory()->retired()->create();
            $unretirementDate = now();

            // Act
            $this->repository->endRetirement($manager, $unretirementDate);

            // Assert
            expect($manager->fresh()->retirements)->toHaveCount(1);
            expect($manager->fresh()->retirements()->first()->started_at)->eq($unretirementDate->copy()->subDays(2));

            $this->assertDatabaseHas('managers_retirements', [
                'manager_id' => $manager->id,
                'ended_at' => $unretirementDate,
            ]);
        });
    });

    describe('suspension management', function () {
        test('can suspend manager', function () {
            // Arrange
            $manager = Manager::factory()->available()->create();
            $suspensionDate = now();

            // Act
            $this->repository->createSuspension($manager, $suspensionDate);

            // Assert
            expect($manager->fresh()->suspensions)->toHaveCount(1);
            expect($manager->fresh()->suspensions->first()->started_at)->eq($suspensionDate);

            $this->assertDatabaseHas('managers_suspensions', [
                'manager_id' => $manager->id,
                'started_at' => $suspensionDate,
                'ended_at' => null,
            ]);
        });

        test('can reinstate suspended manager', function () {
            // Arrange
            $manager = Manager::factory()->suspended()->create();
            $reinstatementDate = now();

            // Act
            $this->repository->endSuspension($manager, $reinstatementDate);

            // Assert
            expect($manager->fresh()->suspensions)->toHaveCount(1);
            expect($manager->fresh()->suspensions()->first()->started_at)->eq($reinstatementDate->copy()->subDays(2));

            $this->assertDatabaseHas('managers_suspensions', [
                'manager_id' => $manager->id,
                'ended_at' => $reinstatementDate,
            ]);
        });
    });

    describe('tag team relationship management', function () {
        test('can remove manager from current tag teams', function () {
            // Arrange
            $removalDate = now();
            $manager = Manager::factory()
                ->hasAttached(TagTeam::factory()->count(2), ['hired_at' => $removalDate->copy()->subDays(3)])
                ->create();

            // Act
            $this->repository->removeFromCurrentTagTeams($manager, $removalDate);

            // Assert
            expect($manager->fresh()->currentTagTeams)->toHaveCount(0);
            expect($manager->fresh()->previousTagTeams)->toHaveCount(2);
            $manager->fresh()->previousTagTeams->each(function ($tagTeam) use ($removalDate) {
                expect($tagTeam->pivot->fired_at)->toBe($removalDate->toDateTimeString());
            });
        });
    });

    describe('wrestler relationship management', function () {
        test('can remove manager from current wrestlers', function () {
            // Arrange
            $removalDate = now();
            $manager = Manager::factory()
                ->hasAttached(Wrestler::factory()->count(2), ['hired_at' => $removalDate->copy()->subDays(3)])
                ->create();

            // Act
            $this->repository->removeFromCurrentWrestlers($manager, $removalDate);

            // Assert
            expect($manager->fresh()->currentWrestlers)->toHaveCount(0);
            expect($manager->fresh()->previousWrestlers)->toHaveCount(2);
            $manager->fresh()->previousWrestlers->each(function ($wrestler) use ($removalDate) {
                expect($wrestler->pivot->fired_at)->toBe($removalDate->toDateTimeString());
            });
        });
    });
});