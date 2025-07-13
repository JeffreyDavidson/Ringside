<?php

declare(strict_types=1);

use App\Data\Matches\EventMatchData;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchType;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\Contracts\EventMatchRepositoryInterface;
use App\Repositories\EventMatchRepository;
use Database\Seeders\MatchTypesTableSeeder;
use Illuminate\Database\Eloquent\Collection;

use function Spatie\PestPluginTestTime\testTime;

/**
 * Unit tests for EventMatchRepository business logic and data operations.
 *
 * UNIT TEST SCOPE:
 * - Repository configuration and structure verification
 * - Event match creation and match number assignment
 * - Match participant management (wrestlers, tag teams, referees, titles)
 * - Side number assignment for competitors
 * - Match data handling and persistence
 *
 * These tests verify that the EventMatchRepository correctly implements
 * all business operations and data persistence requirements.
 *
 * @see EventMatchRepository
 */
describe('EventMatchRepository Unit Tests', function () {
    beforeEach(function () {
        testTime()->freeze();
        $this->repository = app(EventMatchRepository::class);
        $this->seed(MatchTypesTableSeeder::class);
    });

    describe('repository configuration', function () {
        test('repository can be resolved from container', function () {
            expect($this->repository)->toBeInstanceOf(EventMatchRepository::class);
            expect($this->repository)->toBeInstanceOf(EventMatchRepositoryInterface::class);
        });

        test('repository has all expected methods', function () {
            $methods = [
                'createForEvent', 'addTitleToMatch', 'addRefereeToMatch',
                'addWrestlerToMatch', 'addTagTeamToMatch'
            ];

            foreach ($methods as $method) {
                expect(method_exists($this->repository, $method))
                    ->toBeTrue("Repository should have {$method} method");
            }
        });
    });

    describe('event match creation', function () {
        test('can create event match with minimal data', function () {
            // Arrange
            $event = Event::factory()->create();
            $matchType = MatchType::inRandomOrder()->first();
            $referees = Referee::factory()->count(1)->create();
            $data = new EventMatchData(
                $matchType,
                $referees,
                new Collection(),
                collect(),
                null
            );

            // Act
            $eventMatch = $this->repository->createForEvent($event, $data);

            // Assert
            expect($eventMatch)->toBeInstanceOf(EventMatch::class);
            expect($eventMatch->event_id)->toBe($event->id);
            expect($eventMatch->match_type_id)->toBe($matchType->id);
            expect($eventMatch->match_number)->toBe(1);
            expect($eventMatch->preview)->toBeNull();

            $this->assertDatabaseHas('events_matches', [
                'event_id' => $event->id,
                'match_type_id' => $matchType->id,
                'match_number' => 1,
                'preview' => null,
            ]);
        });

        test('can create event match with preview', function () {
            // Arrange
            $event = Event::factory()->create();
            $matchType = MatchType::inRandomOrder()->first();
            $referees = Referee::factory()->count(1)->create();
            $preview = 'This is a championship match with high stakes.';
            $data = new EventMatchData(
                $matchType,
                $referees,
                new Collection(),
                collect(),
                $preview
            );

            // Act
            $eventMatch = $this->repository->createForEvent($event, $data);

            // Assert
            expect($eventMatch)
                ->event_id->toBe($event->id)
                ->match_type_id->toBe($matchType->id)
                ->preview->toBe($preview);

            $this->assertDatabaseHas('events_matches', [
                'event_id' => $event->id,
                'match_type_id' => $matchType->id,
                'preview' => $preview,
            ]);
        });

        test('assigns correct match numbers for multiple matches', function () {
            // Arrange
            $event = Event::factory()->create();
            $matchType = MatchType::inRandomOrder()->first();
            $referees = Referee::factory()->count(1)->create();
            $data = new EventMatchData(
                $matchType,
                $referees,
                new Collection(),
                collect(),
                null
            );

            // Act - Create multiple matches
            $match1 = $this->repository->createForEvent($event, $data);
            $match2 = $this->repository->createForEvent($event, $data);
            $match3 = $this->repository->createForEvent($event, $data);

            // Assert
            expect($match1->match_number)->toBe(1);
            expect($match2->match_number)->toBe(2);
            expect($match3->match_number)->toBe(3);

            expect($event->fresh()->matches)->toHaveCount(3);
        });

        test('handles existing matches when calculating match number', function () {
            // Arrange
            $event = Event::factory()->create();
            $existingMatch = EventMatch::factory()->for($event)->create(['match_number' => 5]);
            $matchType = MatchType::inRandomOrder()->first();
            $referees = Referee::factory()->count(1)->create();
            $data = new EventMatchData(
                $matchType,
                $referees,
                new Collection(),
                collect(),
                null
            );

            // Act
            $newMatch = $this->repository->createForEvent($event, $data);

            // Assert
            expect($newMatch->match_number)->toBe(2); // Should be existing count (1) + 1
            expect($event->fresh()->matches)->toHaveCount(2);
        });
    });

    describe('referee management', function () {
        test('can add referee to match', function () {
            // Arrange
            $eventMatch = EventMatch::factory()->create();
            $referee = Referee::factory()->create();

            // Act
            $this->repository->addRefereeToMatch($eventMatch, $referee);

            // Assert
            expect($eventMatch->fresh()->referees)->toHaveCount(1);
            expect($eventMatch->fresh()->referees->first()->id)->toBe($referee->id);

            $this->assertDatabaseHas('events_matches_referees', [
                'event_match_id' => $eventMatch->id,
                'referee_id' => $referee->id,
            ]);
        });

        test('can add multiple referees to match', function () {
            // Arrange
            $eventMatch = EventMatch::factory()->create();
            $referee1 = Referee::factory()->create();
            $referee2 = Referee::factory()->create();

            // Act
            $this->repository->addRefereeToMatch($eventMatch, $referee1);
            $this->repository->addRefereeToMatch($eventMatch, $referee2);

            // Assert
            expect($eventMatch->fresh()->referees)->toHaveCount(2);
            expect($eventMatch->fresh()->referees->pluck('id'))
                ->toContain($referee1->id)
                ->toContain($referee2->id);
        });

        test('can add same referee multiple times without duplication', function () {
            // Arrange
            $eventMatch = EventMatch::factory()->create();
            $referee = Referee::factory()->create();

            // Act
            $this->repository->addRefereeToMatch($eventMatch, $referee);
            $this->repository->addRefereeToMatch($eventMatch, $referee);

            // Assert
            expect($eventMatch->fresh()->referees)->toHaveCount(2);
            expect($eventMatch->fresh()->referees->first()->id)->toBe($referee->id);
        });
    });

    describe('title management', function () {
        test('can add title to match', function () {
            // Arrange
            $eventMatch = EventMatch::factory()->create();
            $title = Title::factory()->create();

            // Act
            $this->repository->addTitleToMatch($eventMatch, $title);

            // Assert
            expect($eventMatch->fresh()->titles)->toHaveCount(1);
            expect($eventMatch->fresh()->titles->first()->id)->toBe($title->id);

            $this->assertDatabaseHas('events_matches_titles', [
                'event_match_id' => $eventMatch->id,
                'title_id' => $title->id,
            ]);
        });

        test('can add multiple titles to match', function () {
            // Arrange
            $eventMatch = EventMatch::factory()->create();
            $title1 = Title::factory()->create();
            $title2 = Title::factory()->create();

            // Act
            $this->repository->addTitleToMatch($eventMatch, $title1);
            $this->repository->addTitleToMatch($eventMatch, $title2);

            // Assert
            expect($eventMatch->fresh()->titles)->toHaveCount(2);
            expect($eventMatch->fresh()->titles->pluck('id'))
                ->toContain($title1->id)
                ->toContain($title2->id);
        });

        test('can add same title multiple times without duplication', function () {
            // Arrange
            $eventMatch = EventMatch::factory()->create();
            $title = Title::factory()->create();

            // Act
            $this->repository->addTitleToMatch($eventMatch, $title);
            $this->repository->addTitleToMatch($eventMatch, $title);

            // Assert
            expect($eventMatch->fresh()->titles)->toHaveCount(2);
            expect($eventMatch->fresh()->titles->first()->id)->toBe($title->id);
        });
    });

    describe('wrestler management', function () {
        test('can add wrestler to match with side number', function () {
            // Arrange
            $eventMatch = EventMatch::factory()->create();
            $wrestler = Wrestler::factory()->create();
            $sideNumber = 0;

            // Act
            $this->repository->addWrestlerToMatch($eventMatch, $wrestler, $sideNumber);

            // Assert
            expect($eventMatch->fresh()->wrestlers)->toHaveCount(1);
            expect($eventMatch->fresh()->wrestlers->first())
                ->id->toBe($wrestler->id)
                ->pivot->side_number->toBe($sideNumber);

            $this->assertDatabaseHas('events_matches_competitors', [
                'event_match_id' => $eventMatch->id,
                'competitor_id' => $wrestler->id,
                'competitor_type' => 'wrestler',
                'side_number' => $sideNumber,
            ]);
        });

        test('can add wrestlers to different sides', function () {
            // Arrange
            $eventMatch = EventMatch::factory()->create();
            $wrestler1 = Wrestler::factory()->create();
            $wrestler2 = Wrestler::factory()->create();

            // Act
            $this->repository->addWrestlerToMatch($eventMatch, $wrestler1, 0);
            $this->repository->addWrestlerToMatch($eventMatch, $wrestler2, 1);

            // Assert
            expect($eventMatch->fresh()->wrestlers)->toHaveCount(2);

            $sideZeroWrestler = $eventMatch->fresh()->wrestlers->where('pivot.side_number', 0)->first();
            $sideOneWrestler = $eventMatch->fresh()->wrestlers->where('pivot.side_number', 1)->first();

            expect($sideZeroWrestler->id)->toBe($wrestler1->id);
            expect($sideOneWrestler->id)->toBe($wrestler2->id);
        });

        test('can add multiple wrestlers to same side', function () {
            // Arrange
            $eventMatch = EventMatch::factory()->create();
            $wrestler1 = Wrestler::factory()->create();
            $wrestler2 = Wrestler::factory()->create();
            $sideNumber = 0;

            // Act
            $this->repository->addWrestlerToMatch($eventMatch, $wrestler1, $sideNumber);
            $this->repository->addWrestlerToMatch($eventMatch, $wrestler2, $sideNumber);

            // Assert
            expect($eventMatch->fresh()->wrestlers)->toHaveCount(2);
            expect($eventMatch->fresh()->wrestlers->where('pivot.side_number', $sideNumber))->toHaveCount(2);
        });
    });

    describe('tag team management', function () {
        test('can add tag team to match with side number', function () {
            // Arrange
            $eventMatch = EventMatch::factory()->create();
            $tagTeam = TagTeam::factory()->create();
            $sideNumber = 0;

            // Act
            $this->repository->addTagTeamToMatch($eventMatch, $tagTeam, $sideNumber);

            // Assert
            expect($eventMatch->fresh()->tagTeams)->toHaveCount(1);
            expect($eventMatch->fresh()->tagTeams->first())
                ->id->toBe($tagTeam->id)
                ->pivot->side_number->toBe($sideNumber);

            $this->assertDatabaseHas('events_matches_competitors', [
                'event_match_id' => $eventMatch->id,
                'competitor_id' => $tagTeam->id,
                'competitor_type' => 'tagTeam',
                'side_number' => $sideNumber,
            ]);
        });

        test('can add tag teams to different sides', function () {
            // Arrange
            $eventMatch = EventMatch::factory()->create();
            $tagTeam1 = TagTeam::factory()->create();
            $tagTeam2 = TagTeam::factory()->create();

            // Act
            $this->repository->addTagTeamToMatch($eventMatch, $tagTeam1, 0);
            $this->repository->addTagTeamToMatch($eventMatch, $tagTeam2, 1);

            // Assert
            expect($eventMatch->fresh()->tagTeams)->toHaveCount(2);

            $sideZeroTeam = $eventMatch->fresh()->tagTeams->where('pivot.side_number', 0)->first();
            $sideOneTeam = $eventMatch->fresh()->tagTeams->where('pivot.side_number', 1)->first();

            expect($sideZeroTeam->id)->toBe($tagTeam1->id);
            expect($sideOneTeam->id)->toBe($tagTeam2->id);
        });

        test('can add multiple tag teams to same side', function () {
            // Arrange
            $eventMatch = EventMatch::factory()->create();
            $tagTeam1 = TagTeam::factory()->create();
            $tagTeam2 = TagTeam::factory()->create();
            $sideNumber = 1;

            // Act
            $this->repository->addTagTeamToMatch($eventMatch, $tagTeam1, $sideNumber);
            $this->repository->addTagTeamToMatch($eventMatch, $tagTeam2, $sideNumber);

            // Assert
            expect($eventMatch->fresh()->tagTeams)->toHaveCount(2);
            expect($eventMatch->fresh()->tagTeams->where('pivot.side_number', $sideNumber))->toHaveCount(2);
        });
    });

    describe('complex match scenarios', function () {
        test('can create match with all participant types', function () {
            // Arrange
            $eventMatch = EventMatch::factory()->create();
            $wrestler = Wrestler::factory()->create();
            $tagTeam = TagTeam::factory()->create();
            $referee = Referee::factory()->create();
            $title = Title::factory()->create();

            // Act
            $this->repository->addWrestlerToMatch($eventMatch, $wrestler, 0);
            $this->repository->addTagTeamToMatch($eventMatch, $tagTeam, 1);
            $this->repository->addRefereeToMatch($eventMatch, $referee);
            $this->repository->addTitleToMatch($eventMatch, $title);

            // Assert
            $freshMatch = $eventMatch->fresh();
            expect($freshMatch->wrestlers)->toHaveCount(1);
            expect($freshMatch->tagTeams)->toHaveCount(1);
            expect($freshMatch->referees)->toHaveCount(1);
            expect($freshMatch->titles)->toHaveCount(1);

            expect($freshMatch->wrestlers->first()->pivot->side_number)->toBe(0);
            expect($freshMatch->tagTeams->first()->pivot->side_number)->toBe(1);
        });

        test('handles mixed competitors on same side', function () {
            // Arrange
            $eventMatch = EventMatch::factory()->create();
            $wrestler = Wrestler::factory()->create();
            $tagTeam = TagTeam::factory()->create();
            $sideNumber = 0;

            // Act
            $this->repository->addWrestlerToMatch($eventMatch, $wrestler, $sideNumber);
            $this->repository->addTagTeamToMatch($eventMatch, $tagTeam, $sideNumber);

            // Assert
            $freshMatch = $eventMatch->fresh();
            expect($freshMatch->wrestlers)->toHaveCount(1);
            expect($freshMatch->tagTeams)->toHaveCount(1);
            expect($freshMatch->wrestlers->first()->pivot->side_number)->toBe($sideNumber);
            expect($freshMatch->tagTeams->first()->pivot->side_number)->toBe($sideNumber);
        });

        test('can create tournament-style match with multiple participants', function () {
            // Arrange
            $eventMatch = EventMatch::factory()->create();
            $wrestlers = Wrestler::factory()->count(4)->create();

            // Act - Add wrestlers to different sides (0, 1, 2, 3 for four-way match)
            foreach ($wrestlers as $index => $wrestler) {
                $this->repository->addWrestlerToMatch($eventMatch, $wrestler, $index);
            }

            // Assert
            $freshMatch = $eventMatch->fresh();
            expect($freshMatch->wrestlers)->toHaveCount(4);

            for ($side = 0; $side < 4; $side++) {
                $wrestlersOnSide = $freshMatch->wrestlers->where('pivot.side_number', $side);
                expect($wrestlersOnSide)->toHaveCount(1);
            }
        });
    });

    describe('data persistence verification', function () {
        test('match creation persists all required data', function () {
            // Arrange
            $event = Event::factory()->create();
            $matchType = MatchType::inRandomOrder()->first();
            $referees = Referee::factory()->count(1)->create();
            $data = new EventMatchData(
                $matchType,
                $referees,
                new Collection(),
                collect(),
                'Test Preview'
            );

            // Act
            $eventMatch = $this->repository->createForEvent($event, $data);

            // Assert
            expect($eventMatch)
                ->exists->toBeTrue()
                ->id->not->toBeNull()
                ->created_at->not->toBeNull()
                ->updated_at->not->toBeNull();

            $this->assertDatabaseHas('events_matches', [
                'id' => $eventMatch->id,
                'event_id' => $event->id,
                'match_type_id' => $matchType->id,
                'preview' => 'Test Preview',
            ]);
        });

        test('participant additions persist correctly', function () {
            // Arrange
            $eventMatch = EventMatch::factory()->create();
            $wrestler = Wrestler::factory()->create();
            $tagTeam = TagTeam::factory()->create();
            $referee = Referee::factory()->create();
            $title = Title::factory()->create();

            // Act
            $this->repository->addWrestlerToMatch($eventMatch, $wrestler, 0);
            $this->repository->addTagTeamToMatch($eventMatch, $tagTeam, 1);
            $this->repository->addRefereeToMatch($eventMatch, $referee);
            $this->repository->addTitleToMatch($eventMatch, $title);

            // Assert - Verify database persistence
            $this->assertDatabaseHas('events_matches_competitors', [
                'event_match_id' => $eventMatch->id,
                'competitor_id' => $wrestler->id,
                'competitor_type' => 'wrestler',
                'side_number' => 0,
            ]);

            $this->assertDatabaseHas('events_matches_competitors', [
                'event_match_id' => $eventMatch->id,
                'competitor_id' => $tagTeam->id,
                'competitor_type' => 'tagTeam',
                'side_number' => 1,
            ]);

            $this->assertDatabaseHas('events_matches_referees', [
                'event_match_id' => $eventMatch->id,
                'referee_id' => $referee->id,
            ]);

            $this->assertDatabaseHas('events_matches_titles', [
                'event_match_id' => $eventMatch->id,
                'title_id' => $title->id,
            ]);
        });
    });
});