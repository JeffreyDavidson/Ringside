<?php

declare(strict_types=1);

namespace Database\Factories\Matches;

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchDecision;
use App\Models\Matches\MatchLoser;
use App\Models\Matches\MatchResult;
use App\Models\Matches\MatchType;
use App\Models\Matches\MatchWinner;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

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
     * @var class-string<Model>
     */
    protected $model = EventMatch::class;

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
        if ($champion instanceof Title) {
            $title = $champion;
            $champion = null;
        }

        return $this->afterCreating(function (EventMatch $eventMatch) use ($title, $champion) {
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
            default => throw new InvalidArgumentException("Unknown competitor type: {$competitorType}"),
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

    /**
     * Generate a complete match with comprehensive configuration.
     *
     * @param  array<string, mixed>  $config  Configuration options
     */
    public function generateFullMatch(array $config): static
    {
        // Store config for use in afterCreating callback
        $this->matchConfig = $config;

        // Set match type
        $matchType = $this->resolveMatchType($config['match_type'] ?? 'singles');

        return $this->state([
            'match_type_id' => $matchType->id,
        ])->afterCreating(function (EventMatch $eventMatch) {
            $this->configureFullMatch($eventMatch, $this->matchConfig);
        });
    }

    /** @var array<string, mixed> */
    private array $matchConfig = [];

    /**
     * Configure the complete match based on provided config.
     */
    private function configureFullMatch(EventMatch $eventMatch, array $config): void
    {
        // Add titles if specified
        if (isset($config['titles'])) {
            $this->attachTitles($eventMatch, $config['titles']);
        }

        // Add competitors
        $competitors = $this->generateFullMatchCompetitors($eventMatch, $config);
        $this->createCompetitorRecords($eventMatch, $competitors);

        // Add referees if specified
        if (isset($config['referees'])) {
            $this->attachReferees($eventMatch, $config['referees']);
        }

        // Add result with winner/loser strategy
        $this->createFullMatchResult($eventMatch, $config);
    }

    /**
     * Resolve match type from string slug.
     */
    private function resolveMatchType(string $matchType): MatchType
    {
        return match ($matchType) {
            'singles' => MatchType::factory()->singles()->create(),
            'tagteam', 'tag-team' => MatchType::factory()->tagTeam()->create(),
            'triple', 'triple-threat' => MatchType::factory()->tripleThreat()->create(),
            'fatal4way', 'fatal-4-way' => MatchType::factory()->fatalFourWay()->create(),
            'battleroyal', 'battle-royal' => MatchType::factory()->battleRoyal()->create(),
            'royalrumble', 'royal-rumble' => MatchType::factory()->royalRumble()->create(),
            default => MatchType::factory()->singles()->create(),
        };
    }

    /**
     * Generate competitors for full match based on configuration.
     */
    private function generateFullMatchCompetitors(EventMatch $eventMatch, array $config): array
    {
        $competitors = [];
        $sideNumber = 0;

        // Handle specific competitors provided
        if (isset($config['competitors'])) {
            $competitors = $this->processSpecificCompetitors($config['competitors'], $sideNumber);
        } else {
            // Generate competitors based on count and match type
            $competitorCount = $config['competitor_count'] ?? $eventMatch->matchType->getMinimumCompetitors();
            $competitors = $this->generateCompetitorsByCount($eventMatch->matchType, $competitorCount, $config, $sideNumber);
        }

        return $competitors;
    }

    /**
     * Process specific competitors provided in config.
     */
    private function processSpecificCompetitors(array $competitors, int &$sideNumber): array
    {
        $result = [];

        foreach ($competitors as $competitor) {
            if (is_string($competitor)) {
                // Handle type hints or names
                if (in_array($competitor, ['wrestler', 'tag_team'])) {
                    $model = $competitor === 'wrestler'
                        ? Wrestler::factory()->create()
                        : TagTeam::factory()->create();
                } else {
                    // Assume it's a wrestler name
                    $model = Wrestler::factory()->create(['name' => $competitor]);
                }
            } else {
                // Assume it's a model instance
                $model = $competitor;
            }

            $result[] = $this->createCompetitorData($model, $sideNumber++);
        }

        return $result;
    }

    /**
     * Generate competitors by count and type.
     */
    private function generateCompetitorsByCount(MatchType $matchType, int $count, array $config, int &$sideNumber): array
    {
        $competitors = [];
        $existingChampions = $this->getExistingChampions($config);

        // Add existing champions first
        foreach ($existingChampions as $champion) {
            $competitors[] = $this->createCompetitorData($champion, $sideNumber++);
            $count--;
        }

        // Generate remaining competitors
        for ($i = 0; $i < $count; $i++) {
            $competitor = $this->createCompetitorWithMatchTypeRestrictions($matchType);
            $competitors[] = $this->createCompetitorData($competitor, $sideNumber++);
        }

        return $competitors;
    }

    /**
     * Get existing champions from titles in config.
     */
    private function getExistingChampions(array $config): array
    {
        $champions = [];

        if (isset($config['titles'])) {
            foreach ($config['titles'] as $title) {
                $championship = TitleChampionship::where('title_id', $title->id)
                    ->whereNull('lost_at')
                    ->first();

                if ($championship) {
                    $championModel = $championship->champion_type::find($championship->champion_id);
                    if ($championModel) {
                        $champions[] = $championModel;
                    }
                }
            }
        }

        return array_unique($champions, SORT_REGULAR);
    }

    /**
     * Attach titles to the match.
     */
    private function attachTitles(EventMatch $eventMatch, array $titles): void
    {
        $titleIds = array_map(fn ($title) => $title->id, $titles);
        $eventMatch->titles()->attach($titleIds);
    }

    /**
     * Attach referees to the match.
     */
    private function attachReferees(EventMatch $eventMatch, $referees): void
    {
        if (is_numeric($referees)) {
            // Create the specified number of referees
            $refereeModels = Referee::factory()->count($referees)->create();
            $eventMatch->referees()->attach($refereeModels->pluck('id'));
        } elseif (is_array($referees)) {
            // Use specific referee models
            $refereeIds = array_map(fn ($referee) => $referee->id, $referees);
            $eventMatch->referees()->attach($refereeIds);
        }
    }

    /**
     * Create match result with winner/loser strategy.
     */
    private function createFullMatchResult(EventMatch $eventMatch, array $config): void
    {
        $competitors = $eventMatch->competitors;

        if ($competitors->isEmpty()) {
            return;
        }

        // Get or create match decision
        $decision = $this->resolveMatchDecision($config['decision_type'] ?? 'pinfall');

        // Handle no-outcome scenarios
        if (in_array($decision->slug, ['draw', 'nodecision'])) {
            MatchResult::factory()->create([
                'match_id' => $eventMatch->id,
                'match_decision_id' => $decision->id,
                'winner_type' => null,
                'winner_id' => null,
            ]);

            return;
        }

        // Resolve winners and losers based on strategy
        $winnerStrategy = $config['winner_strategy'] ?? 'random';
        [$winners, $losers] = $this->resolveWinnersAndLosers($competitors, $winnerStrategy);

        // Create the match result with first winner (for backward compatibility)
        $primaryWinner = $winners->first();
        $matchResult = MatchResult::factory()->create([
            'match_id' => $eventMatch->id,
            'match_decision_id' => $decision->id,
            'winner_type' => $primaryWinner->competitor_type,
            'winner_id' => $primaryWinner->competitor_id,
        ]);

        // Create winner records
        foreach ($winners as $winner) {
            MatchWinner::factory()->create([
                'match_result_id' => $matchResult->id,
                'match_competitor_id' => $winner->id,
            ]);
        }

        // Create loser records
        foreach ($losers as $loser) {
            MatchLoser::factory()->create([
                'match_result_id' => $matchResult->id,
                'match_competitor_id' => $loser->id,
            ]);
        }
    }

    /**
     * Resolve match decision from type.
     */
    private function resolveMatchDecision(string $decisionType): MatchDecision
    {
        // Try to find existing decision first
        $decision = MatchDecision::where('slug', $decisionType)->first();

        if ($decision) {
            return $decision;
        }

        // Create new decision if it doesn't exist
        return MatchDecision::factory()->create([
            'name' => str($decisionType)->title()->value(),
            'slug' => $decisionType,
        ]);
    }

    /**
     * Resolve winners and losers based on strategy.
     *
     * @return array{0: Collection, 1: Collection}
     */
    private function resolveWinnersAndLosers($competitors, string $strategy): array
    {
        $competitorsCollection = collect($competitors);

        return match ($strategy) {
            'first' => [
                collect([$competitorsCollection->first()]),
                $competitorsCollection->skip(1),
            ],
            'last' => [
                collect([$competitorsCollection->last()]),
                $competitorsCollection->take($competitorsCollection->count() - 1),
            ],
            'multiple' => [
                $winners = $competitorsCollection->random(fake()->numberBetween(1, $competitorsCollection->count() - 1)),
                $competitorsCollection->diff($winners),
            ],
            'all_but_one' => [
                $competitorsCollection->take($competitorsCollection->count() - 1),
                collect([$competitorsCollection->last()]),
            ],
            'single', 'random' => [
                $winner = collect([$competitorsCollection->random()]),
                $competitorsCollection->diff($winner),
            ],
            default => [
                $winner = collect([$competitorsCollection->random()]),
                $competitorsCollection->diff($winner),
            ],
        };
    }

    /**
     * Create a random competitor based on allowed types and match type restrictions.
     */
    private function createCompetitorWithMatchTypeRestrictions(MatchType $matchType): Model
    {
        // Royal Rumble and Singles only allow wrestlers
        if (in_array($matchType->slug, ['royal-rumble', 'singles'])) {
            return Wrestler::factory()->create();
        }

        // Other match types use the original logic
        return $this->createRandomCompetitor($matchType->getAllowedCompetitorTypes());
    }
}
