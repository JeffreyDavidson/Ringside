<?php

declare(strict_types=1);

use App\Data\Wrestlers\WrestlerData;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Models\Wrestlers\WrestlerEmployment;
use App\Models\Wrestlers\WrestlerInjury;
use App\Models\Wrestlers\WrestlerRetirement;
use App\Models\Wrestlers\WrestlerSuspension;
use App\Repositories\Concerns\ManagesEmployment;
use App\Repositories\Concerns\ManagesInjury;
use App\Repositories\Concerns\ManagesRetirement;
use App\Repositories\Concerns\ManagesSuspension;
use App\Repositories\Contracts\ManagesEmployment as ManagesEmploymentContract;
use App\Repositories\Contracts\ManagesInjury as ManagesInjuryContract;
use App\Repositories\Contracts\ManagesRetirement as ManagesRetirementContract;
use App\Repositories\Contracts\ManagesSuspension as ManagesSuspensionContract;
use App\Repositories\Contracts\WrestlerRepositoryInterface;
use App\Repositories\WrestlerRepository;
use App\ValueObjects\Height;

/**
 * Unit tests for WrestlerRepository business logic and data operations.
 *
 * UNIT TEST SCOPE:
 * - Repository configuration and structure verification
 * - Core CRUD operations (create, update, delete, restore)
 * - Trait-based functionality (employment, retirement, suspension, injury management)
 * - Wrestler specific relationship management (tag teams)
 * - Business logic query methods
 *
 * These tests verify that the WrestlerRepository correctly implements
 * all business operations and data persistence requirements.
 *
 * @see WrestlerRepository
 */
describe('WrestlerRepository Unit Tests', function () {
    beforeEach(function () {
        $this->repository = app(WrestlerRepository::class);
    });

    describe('repository configuration', function () {
        test('repository can be resolved from container', function () {
            expect($this->repository)->toBeInstanceOf(WrestlerRepository::class);
            expect($this->repository)->toBeInstanceOf(WrestlerRepositoryInterface::class);
        });

        test('repository implements all required contracts', function () {
            expect($this->repository)->toBeInstanceOf(ManagesEmploymentContract::class);
            expect($this->repository)->toBeInstanceOf(ManagesInjuryContract::class);
            expect($this->repository)->toBeInstanceOf(ManagesRetirementContract::class);
            expect($this->repository)->toBeInstanceOf(ManagesSuspensionContract::class);
        });

        test('repository uses all required traits', function () {
            expect(WrestlerRepository::class)->usesTrait(ManagesEmployment::class);
            expect(WrestlerRepository::class)->usesTrait(ManagesInjury::class);
            expect(WrestlerRepository::class)->usesTrait(ManagesRetirement::class);
            expect(WrestlerRepository::class)->usesTrait(ManagesSuspension::class);
        });

        test('repository has all expected methods', function () {
            $methods = [
                'create', 'update', 'restore',
                'createEmployment', 'createRelease', 'createRetirement', 'endRetirement',
                'createSuspension', 'endSuspension', 'createInjury', 'endInjury',
                'removeFromCurrentTagTeam', 'getAvailableForNewTagTeam', 'getAvailableForExistingTagTeam'
            ];

            foreach ($methods as $method) {
                expect(method_exists($this->repository, $method))
                    ->toBeTrue("Repository should have {$method} method");
            }
        });
    });

    describe('core CRUD operations', function () {
        test('can create wrestler without signature move', function () {
            // Arrange
            $data = new WrestlerData('Example Wrestler Name', 70, 220, 'Laraville, New York', null, null);

            // Act
            $wrestler = $this->repository->create($data);

            // Assert
            expect($wrestler)
                ->toBeInstanceOf(Wrestler::class)
                ->name->toEqual('Example Wrestler Name')
                ->height->toBeInstanceOf(Height::class)
                ->weight->toEqual(220)
                ->hometown->toEqual('Laraville, New York')
                ->signature_move->toBeNull();

            $this->assertDatabaseHas('wrestlers', [
                'name' => 'Example Wrestler Name',
                'weight' => 220,
                'hometown' => 'Laraville, New York',
                'signature_move' => null,
            ]);
        });

        test('can create wrestler with signature move', function () {
            // Arrange
            $data = new WrestlerData('Example Wrestler Name', 70, 220, 'Laraville, New York', 'Powerbomb', null);

            // Act
            $wrestler = $this->repository->create($data);

            // Assert
            expect($wrestler)
                ->name->toEqual('Example Wrestler Name')
                ->height->toBeInstanceOf(Height::class)
                ->weight->toEqual(220)
                ->hometown->toEqual('Laraville, New York')
                ->signature_move->toEqual('Powerbomb');

            $this->assertDatabaseHas('wrestlers', [
                'name' => 'Example Wrestler Name',
                'signature_move' => 'Powerbomb',
            ]);
        });

        test('can update existing wrestler', function () {
            // Arrange
            $wrestler = Wrestler::factory()->create();
            $data = new WrestlerData('Updated Name', 70, 220, 'Updated City', 'Updated Move', null);

            // Act
            $updatedWrestler = $this->repository->update($wrestler, $data);

            // Assert
            expect($updatedWrestler)
                ->name->toEqual('Updated Name')
                ->height->toBeInstanceOf(Height::class)
                ->weight->toEqual(220)
                ->hometown->toEqual('Updated City')
                ->signature_move->toEqual('Updated Move');

            $this->assertDatabaseHas('wrestlers', [
                'id' => $wrestler->id,
                'name' => 'Updated Name',
                'signature_move' => 'Updated Move',
            ]);
        });

        test('can soft delete wrestler', function () {
            // Arrange
            $wrestler = Wrestler::factory()->create();

            // Act
            $this->repository->delete($wrestler);

            // Assert
            expect($wrestler->fresh()->deleted_at)->not->toBeNull();
            $this->assertSoftDeleted('wrestlers', ['id' => $wrestler->id]);
        });

        test('can restore soft deleted wrestler', function () {
            // Arrange
            $wrestler = Wrestler::factory()->trashed()->create();

            // Act
            $this->repository->restore($wrestler);

            // Assert
            expect($wrestler->fresh()->deleted_at)->toBeNull();
            $this->assertDatabaseHas('wrestlers', [
                'id' => $wrestler->id,
                'deleted_at' => null,
            ]);
        });
    });

    describe('employment management', function () {
        test('can create employment for wrestler', function () {
            // Arrange
            $wrestler = Wrestler::factory()->create();
            $employmentDate = now()->subDays(30);

            // Act
            $this->repository->createEmployment($wrestler, $employmentDate);

            // Assert
            expect($wrestler->fresh()->employments)->toHaveCount(1);
            expect($wrestler->fresh()->employments->first()->started_at)->eq($employmentDate);
            
            $this->assertDatabaseHas('wrestlers_employments', [
                'wrestler_id' => $wrestler->id,
                'started_at' => $employmentDate,
                'ended_at' => null,
            ]);
        });

        test('can release employed wrestler', function () {
            // Arrange
            $wrestler = Wrestler::factory()->bookable()->create();
            $releaseDate = now();

            // Act
            $this->repository->createRelease($wrestler, $releaseDate);

            // Assert
            expect($wrestler->fresh()->employments->first()->ended_at)->eq($releaseDate);
            
            $this->assertDatabaseHas('wrestlers_employments', [
                'wrestler_id' => $wrestler->id,
                'ended_at' => $releaseDate,
            ]);
        });

        test('updates existing employment when creating new employment', function () {
            // Arrange
            $wrestler = Wrestler::factory()
                ->has(WrestlerEmployment::factory()->started(now()->subDays(10)), 'employments')
                ->create();
            $newEmploymentDate = now()->subDays(5);

            // Act
            $this->repository->createEmployment($wrestler, $newEmploymentDate);

            // Assert
            expect($wrestler->fresh()->employments)->toHaveCount(1);
            expect($wrestler->fresh()->employments->first()->started_at)->eq($newEmploymentDate);
        });
    });

    describe('injury management', function () {
        test('can injure wrestler', function () {
            // Arrange
            $wrestler = Wrestler::factory()->bookable()->create();
            $injuryDate = now();

            // Act
            $this->repository->createInjury($wrestler, $injuryDate);

            // Assert
            expect($wrestler->fresh()->injuries)->toHaveCount(1);
            expect($wrestler->fresh()->injuries->first()->started_at)->eq($injuryDate);
            
            $this->assertDatabaseHas('wrestlers_injuries', [
                'wrestler_id' => $wrestler->id,
                'started_at' => $injuryDate,
                'ended_at' => null,
            ]);
        });

        test('can clear injured wrestler', function () {
            // Arrange
            $wrestler = Wrestler::factory()->injured()->create();
            $clearDate = now();

            // Act
            $this->repository->endInjury($wrestler, $clearDate);

            // Assert
            expect($wrestler->fresh()->injuries->first()->ended_at)->eq($clearDate);
            
            $this->assertDatabaseHas('wrestlers_injuries', [
                'wrestler_id' => $wrestler->id,
                'ended_at' => $clearDate,
            ]);
        });
    });

    describe('retirement management', function () {
        test('can retire wrestler', function () {
            // Arrange
            $wrestler = Wrestler::factory()->bookable()->create();
            $retirementDate = now();

            // Act
            $this->repository->createRetirement($wrestler, $retirementDate);

            // Assert
            expect($wrestler->fresh()->retirements)->toHaveCount(1);
            expect($wrestler->fresh()->retirements->first()->started_at)->eq($retirementDate);
            
            $this->assertDatabaseHas('wrestlers_retirements', [
                'wrestler_id' => $wrestler->id,
                'started_at' => $retirementDate,
                'ended_at' => null,
            ]);
        });

        test('can unretire wrestler', function () {
            // Arrange
            $wrestler = Wrestler::factory()->retired()->create();
            $unretirementDate = now();

            // Act
            $this->repository->endRetirement($wrestler, $unretirementDate);

            // Assert
            expect($wrestler->fresh()->retirements->first()->ended_at)->eq($unretirementDate);
            
            $this->assertDatabaseHas('wrestlers_retirements', [
                'wrestler_id' => $wrestler->id,
                'ended_at' => $unretirementDate,
            ]);
        });
    });

    describe('suspension management', function () {
        test('can suspend wrestler', function () {
            // Arrange
            $wrestler = Wrestler::factory()->bookable()->create();
            $suspensionDate = now();

            // Act
            $this->repository->createSuspension($wrestler, $suspensionDate);

            // Assert
            expect($wrestler->fresh()->suspensions)->toHaveCount(1);
            expect($wrestler->fresh()->suspensions->first()->started_at)->eq($suspensionDate);
            
            $this->assertDatabaseHas('wrestlers_suspensions', [
                'wrestler_id' => $wrestler->id,
                'started_at' => $suspensionDate,
                'ended_at' => null,
            ]);
        });

        test('can reinstate suspended wrestler', function () {
            // Arrange
            $wrestler = Wrestler::factory()->suspended()->create();
            $reinstatementDate = now();

            // Act
            $this->repository->endSuspension($wrestler, $reinstatementDate);

            // Assert
            expect($wrestler->fresh()->suspensions->first()->ended_at)->eq($reinstatementDate);
            
            $this->assertDatabaseHas('wrestlers_suspensions', [
                'wrestler_id' => $wrestler->id,
                'ended_at' => $reinstatementDate,
            ]);
        });
    });

    describe('tag team relationship management', function () {
        test('can remove wrestler from current tag team', function () {
            // Arrange
            $wrestler = Wrestler::factory()
                ->bookable()
                ->onCurrentTagTeam($tagTeam = TagTeam::factory()->bookable()->create())
                ->create();
            $removalDate = now();

            // Act
            expect($wrestler->fresh()->currentTagTeam->id)->toBe($tagTeam->id);
            expect($wrestler->fresh()->tagTeams->first()->pivot->left_at)->toBeNull();

            $this->repository->removeFromCurrentTagTeam($wrestler, $removalDate);

            // Assert
            expect($wrestler->fresh()->tagTeams->first()->pivot->left_at)->not->toBeNull();
            expect($wrestler->fresh()->currentTagTeam)->toBeNull();
        });
    });

    describe('availability query methods', function () {
        test('can query available wrestlers for new tag team', function () {
            // Arrange
            $bookableWrestler = Wrestler::factory()->bookable()->create();
            $unemployedWrestler = Wrestler::factory()->unemployed()->create();
            $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
            $tagTeamWrestler = Wrestler::factory()->bookable()->onCurrentTagTeam()->create();

            // Act
            $wrestlers = $this->repository->getAvailableForNewTagTeam();

            // Assert
            expect($wrestlers->pluck('id'))->toContain($bookableWrestler->id);
            expect($wrestlers->pluck('id'))->toContain($unemployedWrestler->id);
            expect($wrestlers->pluck('id'))->toContain($futureEmployedWrestler->id);
            expect($wrestlers->pluck('id'))->not->toContain($tagTeamWrestler->id);
        });

        test('can query available wrestlers for existing tag team', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create();
            $bookableWrestler = Wrestler::factory()->bookable()->create();
            $unemployedWrestler = Wrestler::factory()->unemployed()->create();
            $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
            $tagTeamWrestler = Wrestler::factory()->bookable()->onCurrentTagTeam($tagTeam)->create();

            // Act
            $wrestlers = $this->repository->getAvailableForExistingTagTeam($tagTeam);

            // Assert
            expect($wrestlers->pluck('id'))->toContain($bookableWrestler->id);
            expect($wrestlers->pluck('id'))->toContain($unemployedWrestler->id);
            expect($wrestlers->pluck('id'))->toContain($futureEmployedWrestler->id);
            expect($wrestlers->pluck('id'))->toContain($tagTeamWrestler->id);
        });
    });
});