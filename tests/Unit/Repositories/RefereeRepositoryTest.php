<?php

declare(strict_types=1);

use App\Data\Referees\RefereeData;
use App\Models\Referees\Referee;
use App\Models\Referees\RefereeEmployment;
use App\Models\Referees\RefereeInjury;
use App\Models\Referees\RefereeRetirement;
use App\Models\Referees\RefereeSuspension;
use App\Repositories\Concerns\ManagesEmployment;
use App\Repositories\Concerns\ManagesInjury;
use App\Repositories\Concerns\ManagesRetirement;
use App\Repositories\Concerns\ManagesSuspension;
use App\Repositories\Contracts\ManagesEmployment as ManagesEmploymentContract;
use App\Repositories\Contracts\ManagesInjury as ManagesInjuryContract;
use App\Repositories\Contracts\ManagesRetirement as ManagesRetirementContract;
use App\Repositories\Contracts\ManagesSuspension as ManagesSuspensionContract;
use App\Repositories\Contracts\RefereeRepositoryInterface;
use App\Repositories\RefereeRepository;

/**
 * Unit tests for RefereeRepository business logic and data operations.
 *
 * UNIT TEST SCOPE:
 * - Repository configuration and structure verification
 * - Core CRUD operations (create, update, delete, restore)
 * - Trait-based functionality (employment, retirement, suspension, injury management)
 * - Referee specific business logic
 *
 * These tests verify that the RefereeRepository correctly implements
 * all business operations and data persistence requirements.
 *
 * @see RefereeRepository
 */
describe('RefereeRepository Unit Tests', function () {
    beforeEach(function () {
        $this->repository = app(RefereeRepository::class);
    });

    describe('repository configuration', function () {
        test('repository can be resolved from container', function () {
            expect($this->repository)->toBeInstanceOf(RefereeRepository::class);
            expect($this->repository)->toBeInstanceOf(RefereeRepositoryInterface::class);
        });

        test('repository implements all required contracts', function () {
            expect($this->repository)->toBeInstanceOf(ManagesEmploymentContract::class);
            expect($this->repository)->toBeInstanceOf(ManagesInjuryContract::class);
            expect($this->repository)->toBeInstanceOf(ManagesRetirementContract::class);
            expect($this->repository)->toBeInstanceOf(ManagesSuspensionContract::class);
        });

        test('repository uses all required traits', function () {
            expect(RefereeRepository::class)->usesTrait(ManagesEmployment::class);
            expect(RefereeRepository::class)->usesTrait(ManagesInjury::class);
            expect(RefereeRepository::class)->usesTrait(ManagesRetirement::class);
            expect(RefereeRepository::class)->usesTrait(ManagesSuspension::class);
        });

        test('repository has all expected methods', function () {
            $methods = [
                'create', 'update', 'restore',
                'createEmployment', 'createRelease', 'createRetirement', 'endRetirement',
                'createSuspension', 'endSuspension', 'createInjury', 'endInjury'
            ];

            foreach ($methods as $method) {
                expect(method_exists($this->repository, $method))
                    ->toBeTrue("Repository should have {$method} method");
            }
        });
    });

    describe('core CRUD operations', function () {
        test('can create referee with required data', function () {
            // Arrange
            $data = new RefereeData('Taylor', 'Otwell', null);

            // Act
            $referee = $this->repository->create($data);

            // Assert
            expect($referee)
                ->toBeInstanceOf(Referee::class)
                ->first_name->toEqual('Taylor')
                ->last_name->toEqual('Otwell');

            $this->assertDatabaseHas('referees', [
                'first_name' => 'Taylor',
                'last_name' => 'Otwell',
            ]);
        });

        test('can update existing referee', function () {
            // Arrange
            $referee = Referee::factory()->create();
            $data = new RefereeData('Updated', 'Name', null);

            // Act
            $updatedReferee = $this->repository->update($referee, $data);

            // Assert
            expect($updatedReferee->fresh())
                ->first_name->toBe('Updated')
                ->last_name->toBe('Name');

            $this->assertDatabaseHas('referees', [
                'id' => $referee->id,
                'first_name' => 'Updated',
                'last_name' => 'Name',
            ]);
        });

        test('can soft delete referee', function () {
            // Arrange
            $referee = Referee::factory()->create();

            // Act
            $this->repository->delete($referee);

            // Assert
            expect($referee->deleted_at)->not()->toBeNull();
            $this->assertSoftDeleted('referees', ['id' => $referee->id]);
        });

        test('can restore soft deleted referee', function () {
            // Arrange
            $referee = Referee::factory()->trashed()->create();

            // Act
            $this->repository->restore($referee);

            // Assert
            expect($referee->fresh()->deleted_at)->toBeNull();
            $this->assertDatabaseHas('referees', [
                'id' => $referee->id,
                'deleted_at' => null,
            ]);
        });
    });

    describe('employment management', function () {
        test('can create employment for referee', function () {
            // Arrange
            $referee = Referee::factory()->create();
            $employmentDate = now()->subDays(30);

            // Act
            $this->repository->createEmployment($referee, $employmentDate);

            // Assert
            expect($referee->fresh()->employments)->toHaveCount(1);
            expect($referee->fresh()->employments->first()->started_at)->eq($employmentDate);

            $this->assertDatabaseHas('referees_employments', [
                'referee_id' => $referee->id,
                'started_at' => $employmentDate,
                'ended_at' => null,
            ]);
        });

        test('can release employed referee', function () {
            // Arrange
            $referee = Referee::factory()->bookable()->create();
            $releaseDate = now();

            // Act
            $this->repository->createRelease($referee, $releaseDate);

            // Assert
            expect($referee->fresh()->employments)->toHaveCount(1);
            expect($referee->fresh()->employments->first()->ended_at)->eq($releaseDate);

            $this->assertDatabaseHas('referees_employments', [
                'referee_id' => $referee->id,
                'ended_at' => $releaseDate,
            ]);
        });

        test('updates existing employment when creating new employment', function () {
            // Arrange
            $referee = Referee::factory()
                ->has(RefereeEmployment::factory()->started(now()->subDays(10)), 'employments')
                ->create();
            $newEmploymentDate = now()->subDays(5);

            // Act
            $this->repository->createEmployment($referee, $newEmploymentDate);

            // Assert
            expect($referee->fresh()->employments)->toHaveCount(1);
            expect($referee->fresh()->employments->first()->started_at)->eq($newEmploymentDate);
        });
    });

    describe('injury management', function () {
        test('can injure referee', function () {
            // Arrange
            $referee = Referee::factory()->bookable()->create();
            $injuryDate = now();

            // Act
            $this->repository->createInjury($referee, $injuryDate);

            // Assert
            expect($referee->fresh()->injuries)->toHaveCount(1);
            expect($referee->fresh()->injuries->first()->started_at)->eq($injuryDate);

            $this->assertDatabaseHas('referees_injuries', [
                'referee_id' => $referee->id,
                'started_at' => $injuryDate,
                'ended_at' => null,
            ]);
        });

        test('can clear injured referee', function () {
            // Arrange
            $referee = Referee::factory()->injured()->create();
            $clearDate = now();

            // Act
            $this->repository->endInjury($referee, $clearDate);

            // Assert
            expect($referee->fresh()->injuries)->toHaveCount(1);
            expect($referee->fresh()->injuries->first()->ended_at)->eq($clearDate);

            $this->assertDatabaseHas('referees_injuries', [
                'referee_id' => $referee->id,
                'ended_at' => $clearDate,
            ]);
        });
    });

    describe('retirement management', function () {
        test('can retire referee', function () {
            // Arrange
            $referee = Referee::factory()->bookable()->create();
            $retirementDate = now();

            // Act
            $this->repository->createRetirement($referee, $retirementDate);

            // Assert
            expect($referee->fresh()->retirements)->toHaveCount(1);
            expect($referee->fresh()->retirements->first()->started_at)->eq($retirementDate);

            $this->assertDatabaseHas('referees_retirements', [
                'referee_id' => $referee->id,
                'started_at' => $retirementDate,
                'ended_at' => null,
            ]);
        });

        test('can unretire referee', function () {
            // Arrange
            $referee = Referee::factory()->retired()->create();
            $unretirementDate = now();

            // Act
            $this->repository->endRetirement($referee, $unretirementDate);

            // Assert
            expect($referee->fresh()->retirements)->toHaveCount(1);
            expect($referee->fresh()->retirements->first()->ended_at)->eq($unretirementDate);

            $this->assertDatabaseHas('referees_retirements', [
                'referee_id' => $referee->id,
                'ended_at' => $unretirementDate,
            ]);
        });
    });

    describe('suspension management', function () {
        test('can suspend referee', function () {
            // Arrange
            $referee = Referee::factory()->bookable()->create();
            $suspensionDate = now();

            // Act
            $this->repository->createSuspension($referee, $suspensionDate);

            // Assert
            expect($referee->fresh()->suspensions)->toHaveCount(1);
            expect($referee->fresh()->suspensions->first()->started_at)->eq($suspensionDate);

            $this->assertDatabaseHas('referees_suspensions', [
                'referee_id' => $referee->id,
                'started_at' => $suspensionDate,
                'ended_at' => null,
            ]);
        });

        test('can reinstate suspended referee', function () {
            // Arrange
            $referee = Referee::factory()->suspended()->create();
            $reinstatementDate = now();

            // Act
            $this->repository->endSuspension($referee, $reinstatementDate);

            // Assert
            expect($referee->fresh()->suspensions)->toHaveCount(1);
            expect($referee->fresh()->suspensions->first()->ended_at)->eq($reinstatementDate);

            $this->assertDatabaseHas('referees_suspensions', [
                'referee_id' => $referee->id,
                'ended_at' => $reinstatementDate,
            ]);
        });
    });
});