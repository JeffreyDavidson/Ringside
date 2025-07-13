<?php

declare(strict_types=1);

use App\Data\Titles\LongestReigningChampionSummary;
use App\Data\Titles\TitleData;
use App\Enums\Titles\TitleType;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Titles\TitleActivityPeriod;
use App\Models\Titles\TitleChampionship;
use App\Models\Titles\TitleRetirement;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\Concerns\ManagesActivity;
use App\Repositories\Concerns\ManagesRetirement;
use App\Repositories\Contracts\ManagesActivity as ManagesActivityContract;
use App\Repositories\Contracts\ManagesRetirement as ManagesRetirementContract;
use App\Repositories\Contracts\TitleRepositoryInterface;
use App\Repositories\TitleRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

/**
 * Unit tests for TitleRepository business logic and data operations.
 *
 * UNIT TEST SCOPE:
 * - Repository configuration and structure verification
 * - Core CRUD operations (create, update, delete, restore)
 * - Trait-based functionality (activity, retirement management)
 * - Title specific business logic (debut, pull, reinstatement)
 * - Championship query methods and data aggregation
 * - Business logic query methods
 *
 * These tests verify that the TitleRepository correctly implements
 * all business operations and data persistence requirements.
 *
 * @see TitleRepository
 */
describe('TitleRepository Unit Tests', function () {
    beforeEach(function () {
        testTime()->freeze();
        $this->repository = app(TitleRepository::class);
    });

    describe('repository configuration', function () {
        test('repository can be resolved from container', function () {
            expect($this->repository)->toBeInstanceOf(TitleRepository::class);
            expect($this->repository)->toBeInstanceOf(TitleRepositoryInterface::class);
        });

        test('repository implements all required contracts', function () {
            expect($this->repository)->toBeInstanceOf(ManagesActivityContract::class);
            expect($this->repository)->toBeInstanceOf(ManagesRetirementContract::class);
        });

        test('repository uses all required traits', function () {
            expect(TitleRepository::class)->usesTrait(ManagesActivity::class);
            expect(TitleRepository::class)->usesTrait(ManagesRetirement::class);
        });

        test('repository has all expected methods', function () {
            $methods = [
                'create', 'update', 'restore',
                'createActivity', 'endActivity', 'activate', 'deactivate',
                'createRetirement', 'endRetirement', 'retire', 'unretire',
                'createDebut', 'pull', 'createReinstatement',
                'getLongestReigningChampion'
            ];

            foreach ($methods as $method) {
                expect(method_exists($this->repository, $method))
                    ->toBeTrue("Repository should have {$method} method");
            }
        });
    });

    describe('core CRUD operations', function () {
        test('can create title with minimal data', function () {
            // Arrange
            $data = new TitleData('Example Name Title', TitleType::Singles, null);

            // Act
            $title = $this->repository->create($data);

            // Assert
            expect($title)
                ->toBeInstanceOf(Title::class)
                ->name->toEqual('Example Name Title')
                ->type->toBe(TitleType::Singles);

            $this->assertDatabaseHas('titles', [
                'name' => 'Example Name Title',
                'type' => TitleType::Singles->value,
            ]);
        });

        test('can update existing title', function () {
            // Arrange
            $title = Title::factory()->create();
            $data = new TitleData('Updated Title Name', TitleType::TagTeam, null);

            // Act
            $updatedTitle = $this->repository->update($title, $data);

            // Assert
            expect($updatedTitle->fresh())
                ->name->toBe('Updated Title Name')
                ->type->toBe(TitleType::TagTeam);

            $this->assertDatabaseHas('titles', [
                'id' => $title->id,
                'name' => 'Updated Title Name',
                'type' => TitleType::TagTeam->value,
            ]);
        });

        test('can soft delete title', function () {
            // Arrange
            $title = Title::factory()->create();

            // Act
            $this->repository->delete($title);

            // Assert
            expect($title->fresh()->deleted_at)->not->toBeNull();
            $this->assertSoftDeleted('titles', ['id' => $title->id]);
        });

        test('can restore soft deleted title', function () {
            // Arrange
            $title = Title::factory()->trashed()->create();

            // Act
            $this->repository->restore($title);

            // Assert
            expect($title->fresh()->deleted_at)->toBeNull();
            $this->assertDatabaseHas('titles', [
                'id' => $title->id,
                'deleted_at' => null,
            ]);
        });
    });

    describe('activity management', function () {
        test('can activate title', function () {
            // Arrange
            $title = Title::factory()->create();
            $datetime = now()->subDays(30);

            // Act
            $activatedTitle = $this->repository->activate($title, $datetime);

            // Assert
            expect($activatedTitle)->toBeInstanceOf(Title::class);
            expect($title->fresh()->activityPeriods)->toHaveCount(1);
            expect($title->fresh()->activityPeriods->first()->started_at)->eq($datetime);

            $this->assertDatabaseHas('titles_activations', [
                'title_id' => $title->id,
                'started_at' => $datetime,
                'ended_at' => null,
            ]);
        });

        test('can update existing activation when activating title', function () {
            // Arrange
            $originalDate = now()->subDays(30);
            $newDate = now()->subDays(15);
            $title = Title::factory()
                ->has(TitleActivityPeriod::factory()->started($originalDate), 'activations')
                ->create();

            expect($title->fresh()->activityPeriods)->toHaveCount(1);
            expect($title->fresh()->activityPeriods->first()->started_at)->eq($originalDate);

            // Act
            $this->repository->activate($title, $newDate);

            // Assert
            expect($title->fresh()->activityPeriods)->toHaveCount(1);
            expect($title->fresh()->activityPeriods->first()->started_at)->eq($newDate);
        });

        test('can deactivate title', function () {
            // Arrange
            $title = Title::factory()->active()->create();
            $datetime = now();

            // Act
            $deactivatedTitle = $this->repository->deactivate($title, $datetime);

            // Assert
            expect($deactivatedTitle)->toBeInstanceOf(Title::class);
            expect($title->fresh()->activityPeriods)->toHaveCount(1);
            expect($title->fresh()->activityPeriods->first()->ended_at)->eq($datetime);

            $this->assertDatabaseHas('titles_activations', [
                'title_id' => $title->id,
                'ended_at' => $datetime,
            ]);
        });

        test('can create debut for title', function () {
            // Arrange
            $title = Title::factory()->create();
            $debutDate = now()->subDays(30);

            // Act
            $this->repository->createDebut($title, $debutDate);

            // Assert
            expect($title->fresh()->activityPeriods)->toHaveCount(1);
            expect($title->fresh()->activityPeriods->first()->started_at)->eq($debutDate);

            $this->assertDatabaseHas('titles_activations', [
                'title_id' => $title->id,
                'started_at' => $debutDate,
                'ended_at' => null,
            ]);
        });

        test('can pull title from competition', function () {
            // Arrange
            $title = Title::factory()->active()->create();
            $pullDate = now();

            // Act
            $this->repository->pull($title, $pullDate);

            // Assert
            expect($title->fresh()->activityPeriods)->toHaveCount(1);
            expect($title->fresh()->activityPeriods->first()->ended_at)->eq($pullDate);

            $this->assertDatabaseHas('titles_activations', [
                'title_id' => $title->id,
                'ended_at' => $pullDate,
            ]);
        });

        test('can create reinstatement for title', function () {
            // Arrange
            $title = Title::factory()->create();
            $reinstateDate = now()->subDays(30);

            // Act
            $this->repository->createReinstatement($title, $reinstateDate);

            // Assert
            expect($title->fresh()->activityPeriods)->toHaveCount(1);
            expect($title->fresh()->activityPeriods->first()->started_at)->eq($reinstateDate);

            $this->assertDatabaseHas('titles_activations', [
                'title_id' => $title->id,
                'started_at' => $reinstateDate,
                'ended_at' => null,
            ]);
        });
    });

    describe('retirement management', function () {
        test('can retire title', function () {
            // Arrange
            $title = Title::factory()->active()->create();
            $retirementDate = now();

            // Act
            $retiredTitle = $this->repository->retire($title, $retirementDate);

            // Assert
            expect($retiredTitle)->toBeInstanceOf(Title::class);
            expect($title->fresh()->retirements)->toHaveCount(1);
            expect($title->fresh()->retirements->first()->started_at)->eq($retirementDate);

            // Should also end current activity period
            expect($title->fresh()->activityPeriods->first()->ended_at)->eq($retirementDate);

            $this->assertDatabaseHas('titles_retirements', [
                'title_id' => $title->id,
                'started_at' => $retirementDate,
                'ended_at' => null,
            ]);
        });

        test('can unretire title', function () {
            // Arrange
            $title = Title::factory()->retired()->create();
            $unretirementDate = now();

            // Act
            $unretiredTitle = $this->repository->unretire($title, $unretirementDate);

            // Assert
            expect($unretiredTitle)->toBeInstanceOf(Title::class);
            expect($title->fresh()->retirements)->toHaveCount(1);
            expect($title->fresh()->retirements->first()->ended_at)->eq($unretirementDate);

            $this->assertDatabaseHas('titles_retirements', [
                'title_id' => $title->id,
                'ended_at' => $unretirementDate,
            ]);
        });

        test('retirement ends current activity period', function () {
            // Arrange
            $title = Title::factory()->active()->create();
            $retirementDate = now();

            // Ensure title has current activity
            $freshTitle = $title->fresh();
            expect($freshTitle->currentActivityPeriod)->not->toBeNull();
            expect($freshTitle->currentActivityPeriod->ended_at)->toBeNull();

            // Act
            $this->repository->retire($title, $retirementDate);

            // Assert
            $retiredTitle = $title->fresh();
            if ($retiredTitle->currentActivityPeriod) {
                expect($retiredTitle->currentActivityPeriod->ended_at)->eq($retirementDate);
            } else {
                // If no current activity period, verify retirement was created
                expect($retiredTitle->retirements)->toHaveCount(1);
            }
        });
    });

    describe('trait integration', function () {
        test('integrates ManagesActivity trait correctly', function () {
            // Arrange
            $title = Title::factory()->create();
            $startDate = now()->subDays(30);

            // Act
            $this->repository->createActivity($title, $startDate);

            // Assert
            expect($title->fresh()->activityPeriods)->toHaveCount(1);
            expect($title->fresh()->activityPeriods->first())
                ->toBeInstanceOf(TitleActivityPeriod::class)
                ->started_at->eq($startDate)
                ->ended_at->toBeNull();
        });

        test('integrates ManagesRetirement trait correctly', function () {
            // Arrange
            $title = Title::factory()->create();
            $retirementDate = now()->subDays(30);

            // Act
            $this->repository->createRetirement($title, $retirementDate);

            // Assert
            expect($title->fresh()->retirements)->toHaveCount(1);
            expect($title->fresh()->retirements->first())
                ->toBeInstanceOf(TitleRetirement::class)
                ->started_at->eq($retirementDate)
                ->ended_at->toBeNull();
        });
    });

    describe('business logic query methods', function () {
        test('can get longest reigning champion for wrestler', function () {
            // Arrange
            $title = Title::factory()->create();
            $wrestler = Wrestler::factory()->create(['name' => 'Test Champion']);
            
            // Create a championship with specific dates for testing
            $wonAt = now()->subDays(100);
            $lostAt = now()->subDays(10);
            TitleChampionship::factory()
                ->for($title)
                ->for($wrestler, 'champion')
                ->create([
                    'won_at' => $wonAt,
                    'lost_at' => $lostAt,
                ]);

            // Act
            $longestChampion = $this->repository->getLongestReigningChampion($title);

            // Assert
            expect($longestChampion)->toBeInstanceOf(LongestReigningChampionSummary::class);
            expect($longestChampion->championName)->toBe('Test Champion');
            expect($longestChampion->reignLengthInDays)->toBe(90); // 100 - 10 = 90 days
            expect($longestChampion->wonAt)->eq($wonAt);
            expect($longestChampion->lostAt)->eq($lostAt);
        });

        test('can get longest reigning champion for tag team', function () {
            // Arrange
            $title = Title::factory()->create();
            $tagTeam = TagTeam::factory()->create(['name' => 'Test Tag Team']);
            
            // Create a championship with specific dates for testing
            $wonAt = now()->subDays(200);
            $lostAt = now()->subDays(50);
            TitleChampionship::factory()
                ->for($title)
                ->for($tagTeam, 'champion')
                ->create([
                    'won_at' => $wonAt,
                    'lost_at' => $lostAt,
                ]);

            // Act
            $longestChampion = $this->repository->getLongestReigningChampion($title);

            // Assert
            expect($longestChampion)->toBeInstanceOf(LongestReigningChampionSummary::class);
            expect($longestChampion->championName)->toBe('Test Tag Team');
            expect($longestChampion->reignLengthInDays)->toBe(150); // 200 - 50 = 150 days
            expect($longestChampion->wonAt)->eq($wonAt);
            expect($longestChampion->lostAt)->eq($lostAt);
        });

        test('calculates current reign length for active championship', function () {
            // Arrange
            $title = Title::factory()->create();
            $wrestler = Wrestler::factory()->create(['name' => 'Current Champion']);
            
            // Create current championship (no lost_at date)
            $wonAt = now()->subDays(30);
            TitleChampionship::factory()
                ->for($title)
                ->for($wrestler, 'champion')
                ->create([
                    'won_at' => $wonAt,
                    'lost_at' => null, // Current champion
                ]);

            // Act
            $longestChampion = $this->repository->getLongestReigningChampion($title);

            // Assert
            expect($longestChampion)->toBeInstanceOf(LongestReigningChampionSummary::class);
            expect($longestChampion->championName)->toBe('Current Champion');
            expect($longestChampion->reignLengthInDays)->toBe(30); // Current reign length
            expect($longestChampion->wonAt)->eq($wonAt);
            expect($longestChampion->lostAt)->toBeNull();
        });

        test('returns longest reign among multiple championships', function () {
            // Arrange
            $title = Title::factory()->create();
            $shortReignWrestler = Wrestler::factory()->create(['name' => 'Short Reign']);
            $longReignWrestler = Wrestler::factory()->create(['name' => 'Long Reign']);
            
            // Create short reign (10 days)
            TitleChampionship::factory()
                ->for($title)
                ->for($shortReignWrestler, 'champion')
                ->create([
                    'won_at' => now()->subDays(50),
                    'lost_at' => now()->subDays(40),
                ]);

            // Create long reign (100 days)
            TitleChampionship::factory()
                ->for($title)
                ->for($longReignWrestler, 'champion')
                ->create([
                    'won_at' => now()->subDays(150),
                    'lost_at' => now()->subDays(50),
                ]);

            // Act
            $longestChampion = $this->repository->getLongestReigningChampion($title);

            // Assert
            expect($longestChampion->championName)->toBe('Long Reign');
            expect($longestChampion->reignLengthInDays)->toBe(100);
        });

        test('returns null when title has no championships', function () {
            // Arrange
            $title = Title::factory()->create();

            // Act
            $longestChampion = $this->repository->getLongestReigningChampion($title);

            // Assert
            expect($longestChampion)->toBeNull();
        });

        test('handles missing championships gracefully', function () {
            // Arrange
            $title = Title::factory()->create();
            
            // Don't create any championships for this title

            // Act
            $longestChampion = $this->repository->getLongestReigningChampion($title);

            // Assert
            expect($longestChampion)->toBeNull();
        });
    });
});