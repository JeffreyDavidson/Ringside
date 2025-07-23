<?php

declare(strict_types=1);

namespace Database\Factories\Matches;

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchResult;
use App\Models\Matches\MatchDecision;
use App\Models\Matches\MatchType;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<EventMatch>
 */
class MatchFactory extends Factory
{
    // Constants for competitor types
    private const COMPETITOR_TYPE_WRESTLER = 'wrestler';
    private const COMPETITOR_TYPE_TAG_TEAM = 'tag_team';

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = \App\Models\Matches\EventMatch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'match_number' => fake()->randomDigitNotZero(),
            'match_type_id' => MatchType::factory()->singles(),
            'preview' => null,
        ];
    }

    /**
     * Create a complete event match with competitors, results, and winners/losers.
     */
    public function complete(): static
    {
        return $this->afterCreating(function (EventMatch $eventMatch) {
            $this->addCompetitors($eventMatch);
            $this->addResult($eventMatch);
        });
    }

    /**
     * Create a title match with championship implications.
     */
    public function titleMatch($champion = null, $challenger = null, $title = null): static
    {
        // Backward compatibility: if first param is a Title, treat it as the title
        if ($champion instanceof \App\Models\Titles\Title) {
            $title = $champion;
            $champion = null;
        }

        return $this->afterCreating(function (EventMatch $eventMatch) use ($title, $champion, $challenger) {
            $title ??= Title::factory()->create();
            $eventMatch->titles()->attach($title);

            $this->addCompetitors($eventMatch, $champion);
            $this->addResult($eventMatch);
        });
    }

    /**
     * Create a title match with an existing champion defending.
     */
    public function titleDefense(?Title $title = null, ?Model $champion = null): static
    {
        return $this->afterCreating(function (EventMatch $eventMatch) use ($title, $champion) {
            $title ??= Title::factory()->create();
            $eventMatch->titles()->attach($title);

            $champion = $this->resolveChampion($title, $champion);
            $this->addCompetitors($eventMatch, $champion);
            $this->addResult($eventMatch);
        });
    }

    /**
     * Create a singles match (wrestler vs wrestler).
     */
    public function singles(): static
    {
        return $this->createMatchType('singles');
    }

    /**
     * Create a tag team match (tag team vs tag team).
     */
    public function tagTeam(): static
    {
        return $this->createMatchType('tagTeam');
    }

    /**
     * Create a triple threat match (3 wrestlers).
     */
    public function tripleThreat(): static
    {
        return $this->createMatchType('tripleThreat');
    }

    /**
     * Create a fatal four way match (4 wrestlers).
     */
    public function fatalFourWay(): static
    {
        return $this->createMatchType('fatalFourWay');
    }

    /**
     * Create a battle royal match (multiple wrestlers).
     */
    public function battleRoyal(int $competitorCount = 10): static
    {
        return $this->state([
            'match_type_id' => MatchType::factory()->battleRoyal()->create()->id,
        ])->afterCreating(function (EventMatch $eventMatch) use ($competitorCount) {
            $this->addCompetitors($eventMatch, null, $competitorCount);
            $this->addResult($eventMatch);
        });
    }

    /**
     * Add referees to the match.
     */
    public function withReferees(int $count = 1): static
    {
        return $this->afterCreating(function (EventMatch $eventMatch) use ($count) {
            $referees = Referee::factory()->count($count)->create();
            $eventMatch->referees()->attach($referees);
        });
    }

    /**
     * Add specific competitors to the match.
     */
    public function withCompetitors(array $competitors): static
    {
        return $this->afterCreating(function (EventMatch $eventMatch) use ($competitors) {
            foreach ($competitors as $sideNumber => $competitor) {
                MatchCompetitor::factory()->create([
                    'match_id' => $eventMatch->id,
                    'competitor_type' => get_class($competitor),
                    'competitor_id' => $competitor->id,
                    'side_number' => $sideNumber,
                ]);
            }
        });
    }

    /**
     * Create a match with a specific event.
     */
    public function forEvent(Event $event): static
    {
        return $this->state([
            'event_id' => $event->id,
        ]);
    }

    /**
     * Create a match with a specific match type.
     */
    public function withMatchType(MatchType $matchType): static
    {
        return $this->state([
            'match_type_id' => $matchType->id,
        ]);
    }

    /**
     * Create a match with a specific match number.
     */
    public function withMatchNumber(int $matchNumber): static
    {
        return $this->state([
            'match_number' => $matchNumber,
        ]);
    }

    /**
     * Create a match with a preview.
     */
    public function withPreview(string $preview): static
    {
        return $this->state([
            'preview' => $preview,
        ]);
    }

    /**
     * Create a match type with the given factory method.
     */
    private function createMatchType(string $factoryMethod): static
    {
        return $this->state([
            'match_type_id' => MatchType::factory()->{$factoryMethod}()->create()->id,
        ])->afterCreating(function (EventMatch $eventMatch) {
            $this->addCompetitors($eventMatch);
            $this->addResult($eventMatch);
        });
    }

    /**
     * Resolve or create a champion for the given title.
     */
    private function resolveChampion(Title $title, ?Model $champion): Model
    {
        if ($champion !== null) {
            return $champion;
        }

        $champion = $title->type->value === 'tag-team'
            ? TagTeam::factory()->create()
            : Wrestler::factory()->create();

        $this->createChampionshipRecord($title, $champion);

        return $champion;
    }

    /**
     * Create a championship record for the given title and champion.
     */
    private function createChampionshipRecord(Title $title, Model $champion): void
    {
        /** @var string $championClass */
        $championClass = get_class($champion);

        TitleChampionship::factory()->create([
            'title_id' => $title->id,
            'champion_type' => $championClass,
            'champion_id' => $champion->id,
            'won_at' => now()->subMonths(3),
        ]);
    }

    /**
     * Add competitors to the match based on match type.
     */
    private function addCompetitors(EventMatch $eventMatch, ?Model $existingChampion = null, ?int $competitorCount = null): void
    {
        $matchType = $eventMatch->matchType;
        $competitorCount ??= $matchType->getMinimumCompetitors();

        $competitors = $this->generateCompetitors($matchType, $existingChampion, $competitorCount);
        $this->createCompetitorRecords($eventMatch, $competitors);
    }

    /**
     * Generate competitor data based on match type and requirements.
     */
    private function generateCompetitors(MatchType $matchType, ?Model $existingChampion, int $competitorCount): array
    {
        $competitors = [];
        $sideNumber = 0;

        // Add existing champion first if provided
        if ($existingChampion) {
            $competitors[] = $this->createCompetitorData($existingChampion, $sideNumber++);
            $competitorCount--; // Reduce count since we added the champion
        }

        // Generate remaining competitors
        $allowedTypes = $matchType->getAllowedCompetitorTypes();
        for ($i = 0; $i < $competitorCount; $i++) {
            $competitor = $this->createRandomCompetitor($allowedTypes);
            $competitors[] = $this->createCompetitorData($competitor, $sideNumber++);
        }

        return $competitors;
    }

    /**
     * Create a random competitor based on allowed types.
     */
    private function createRandomCompetitor(array $allowedTypes): Model
    {
        $competitorType = fake()->randomElement($allowedTypes);

        return match ($competitorType) {
            self::COMPETITOR_TYPE_WRESTLER => Wrestler::factory()->create(),
            self::COMPETITOR_TYPE_TAG_TEAM => TagTeam::factory()->create(),
            default => throw new \InvalidArgumentException("Unknown competitor type: {$competitorType}"),
        };
    }

    /**
     * Create competitor data array.
     */
    private function createCompetitorData(Model $competitor, int $sideNumber): array
    {
        return [
            'competitor_type' => get_class($competitor),
            'competitor_id' => $competitor->id,
            'side_number' => $sideNumber,
        ];
    }

    /**
     * Create competitor records in the database.
     */
    private function createCompetitorRecords(EventMatch $eventMatch, array $competitors): void
    {
        $competitorData = array_map(function ($competitor) use ($eventMatch) {
            return [
                'match_id' => $eventMatch->id,
                'competitor_type' => $competitor['competitor_type'],
                'competitor_id' => $competitor['competitor_id'],
                'side_number' => $competitor['side_number'],
            ];
        }, $competitors);

        MatchCompetitor::factory()->createMany($competitorData);
    }

    /**
     * Add match result with winner stored directly in result.
     */
    private function addResult(EventMatch $eventMatch): void
    {
        $competitors = $eventMatch->competitors;

        if ($competitors->isEmpty()) {
            return;
        }

        $this->createMatchResult($eventMatch, $competitors);
    }

    /**
     * Create a match result record with winner.
     */
    private function createMatchResult(EventMatch $eventMatch, $competitors): MatchResult
    {
        // Pick a random winner from the competitors
        $winner = $competitors->random();
        
        return MatchResult::factory()->create([
            'match_id' => $eventMatch->id,
            'match_decision_id' => MatchDecision::factory()->create()->id,
            'winner_type' => $winner->competitor_type,
            'winner_id' => $winner->competitor_id,
        ]);
    }
}