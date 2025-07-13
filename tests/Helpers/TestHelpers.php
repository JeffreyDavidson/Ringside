<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Managers\Manager;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchDecision;
use App\Models\Matches\MatchType;
use App\Models\Referees\Referee;
use App\Models\Shared\Venue;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Collection;

/**
 * Test helper functions for common testing scenarios.
 * 
 * These functions provide convenient methods for creating test data,
 * setting up common test scenarios, and performing repetitive test operations.
 */

/**
 * Create a wrestler with realistic attributes for testing.
 */
function createWrestler(array $attributes = []): Wrestler
{
    return Wrestler::factory()->create($attributes);
}

/**
 * Create an employed wrestler for testing availability scenarios.
 */
function createEmployedWrestler(array $attributes = []): Wrestler
{
    return Wrestler::factory()->employed()->create($attributes);
}

/**
 * Create a bookable wrestler for match testing.
 */
function createBookableWrestler(array $attributes = []): Wrestler
{
    return Wrestler::factory()->bookable()->create($attributes);
}

/**
 * Create a manager with realistic attributes for testing.
 */
function createManager(array $attributes = []): Manager
{
    return Manager::factory()->create($attributes);
}

/**
 * Create a referee with realistic attributes for testing.
 */
function createReferee(array $attributes = []): Referee
{
    return Referee::factory()->create($attributes);
}

/**
 * Create a tag team with realistic attributes for testing.
 */
function createTagTeam(array $attributes = []): TagTeam
{
    return TagTeam::factory()->create($attributes);
}

/**
 * Create a stable with realistic attributes for testing.
 */
function createStable(array $attributes = []): Stable
{
    return Stable::factory()->create($attributes);
}

/**
 * Create a title with realistic attributes for testing.
 */
function createTitle(array $attributes = []): Title
{
    return Title::factory()->create($attributes);
}

/**
 * Create a venue with realistic attributes for testing.
 */
function createVenue(array $attributes = []): Venue
{
    return Venue::factory()->create($attributes);
}

/**
 * Create an event with realistic attributes for testing.
 */
function createEvent(array $attributes = []): Event
{
    return Event::factory()->create($attributes);
}

/**
 * Create a match with realistic attributes for testing.
 */
function createMatch(array $attributes = []): EventMatch
{
    return EventMatch::factory()->create($attributes);
}

/**
 * Create a match type for testing.
 */
function createMatchType(array $attributes = []): MatchType
{
    return MatchType::factory()->create($attributes);
}

/**
 * Create a match decision for testing.
 */
function createMatchDecision(array $attributes = []): MatchDecision
{
    return MatchDecision::factory()->create($attributes);
}

/**
 * Create a collection of wrestlers for testing bulk operations.
 */
function createWrestlers(int $count = 5, array $attributes = []): Collection
{
    return Wrestler::factory()->count($count)->create($attributes);
}

/**
 * Create a collection of managers for testing bulk operations.
 */
function createManagers(int $count = 5, array $attributes = []): Collection
{
    return Manager::factory()->count($count)->create($attributes);
}

/**
 * Create a collection of referees for testing bulk operations.
 */
function createReferees(int $count = 5, array $attributes = []): Collection
{
    return Referee::factory()->count($count)->create($attributes);
}

/**
 * Create a complete roster with wrestlers, managers, referees, tag teams, and stables.
 */
function createFullRoster(int $size = 10): array
{
    return [
        'wrestlers' => createWrestlers($size),
        'managers' => createManagers($size / 2),
        'referees' => createReferees($size / 5),
        'tag_teams' => TagTeam::factory()->count($size / 5)->create(),
        'stables' => Stable::factory()->count($size / 10)->create(),
    ];
}

/**
 * Create a complete event with matches and participants.
 */
function createEventWithMatches(int $matchCount = 3): array
{
    $event = createEvent();
    $matches = [];
    
    for ($i = 0; $i < $matchCount; $i++) {
        $matches[] = createMatch([
            'event_id' => $event->id,
            'match_number' => $i + 1,
        ]);
    }
    
    return [
        'event' => $event,
        'matches' => collect($matches),
    ];
}

/**
 * Create a tag team with wrestlers.
 */
function createTagTeamWithWrestlers(int $wrestlerCount = 2): array
{
    $tagTeam = createTagTeam();
    $wrestlers = createWrestlers($wrestlerCount);
    
    foreach ($wrestlers as $wrestler) {
        $tagTeam->wrestlers()->attach($wrestler->id, [
            'joined_at' => now(),
        ]);
    }
    
    return [
        'tag_team' => $tagTeam,
        'wrestlers' => $wrestlers,
    ];
}

/**
 * Create a stable with members.
 */
function createStableWithMembers(int $wrestlerCount = 3, int $tagTeamCount = 1): array
{
    $stable = createStable();
    $wrestlers = createWrestlers($wrestlerCount);
    $tagTeams = TagTeam::factory()->count($tagTeamCount)->create();
    
    foreach ($wrestlers as $wrestler) {
        $stable->wrestlers()->attach($wrestler->id, [
            'joined_at' => now(),
        ]);
    }
    
    foreach ($tagTeams as $tagTeam) {
        $stable->tagTeams()->attach($tagTeam->id, [
            'joined_at' => now(),
        ]);
    }
    
    return [
        'stable' => $stable,
        'wrestlers' => $wrestlers,
        'tag_teams' => $tagTeams,
    ];
}

/**
 * Create a wrestler with a manager relationship.
 */
function createWrestlerWithManager(): array
{
    $wrestler = createWrestler();
    $manager = createManager();
    
    $wrestler->managers()->attach($manager->id, [
        'hired_at' => now(),
    ]);
    
    return [
        'wrestler' => $wrestler,
        'manager' => $manager,
    ];
}

/**
 * Create a championship scenario with title and champion.
 */
function createChampionshipScenario(string $championType = 'wrestler'): array
{
    $title = createTitle();
    
    $champion = match ($championType) {
        'wrestler' => createWrestler(),
        'tag_team' => createTagTeam(),
        default => throw new InvalidArgumentException("Invalid champion type: {$championType}"),
    };
    
    // Create a basic event match for the championship
    $event = createEvent();
    $match = createMatch(['event_id' => $event->id]);
    
    $championship = $title->championships()->create([
        'champion_id' => $champion->id,
        'champion_type' => $championType,
        'won_at' => now(),
        'won_event_match_id' => $match->id,
    ]);
    
    return [
        'title' => $title,
        'champion' => $champion,
        'championship' => $championship,
    ];
}

/**
 * Create an injured wrestler for testing injury scenarios.
 */
function createInjuredWrestler(array $attributes = []): Wrestler
{
    return Wrestler::factory()->injured()->create($attributes);
}

/**
 * Create a suspended wrestler for testing suspension scenarios.
 */
function createSuspendedWrestler(array $attributes = []): Wrestler
{
    return Wrestler::factory()->suspended()->create($attributes);
}

/**
 * Create a retired wrestler for testing retirement scenarios.
 */
function createRetiredWrestler(array $attributes = []): Wrestler
{
    return Wrestler::factory()->retired()->create($attributes);
}

/**
 * Create a wrestler with employment history for testing timeline scenarios.
 */
function createWrestlerWithEmploymentHistory(): Wrestler
{
    $wrestler = createWrestler();
    
    // Create past employment
    $wrestler->employments()->create([
        'started_at' => now()->subYears(2),
        'ended_at' => now()->subYear(),
    ]);
    
    // Create current employment
    $wrestler->employments()->create([
        'started_at' => now()->subMonths(6),
        'ended_at' => null,
    ]);
    
    return $wrestler;
}

/**
 * Seed basic lookup data for testing.
 */
function seedBasicLookupData(): void
{
    if (MatchType::count() === 0) {
        Artisan::call('db:seed', ['--class' => 'MatchTypesTableSeeder']);
    }
    
    if (MatchDecision::count() === 0) {
        Artisan::call('db:seed', ['--class' => 'MatchDecisionsTableSeeder']);
    }
}

/**
 * Create a realistic wrestling date (not too far in past or future).
 */
function wrestlingDate(string $period = 'recent'): \Carbon\Carbon
{
    return match ($period) {
        'recent' => now()->subDays(rand(1, 30)),
        'past' => now()->subMonths(rand(1, 24)),
        'future' => now()->addDays(rand(1, 90)),
        'historical' => now()->subYears(rand(1, 10)),
        default => now(),
    };
}

/**
 * Create a realistic wrestling time period (start and end dates).
 */
function wrestlingTimePeriod(string $type = 'employment'): array
{
    $start = match ($type) {
        'employment' => now()->subMonths(rand(1, 24)),
        'injury' => now()->subWeeks(rand(1, 12)),
        'suspension' => now()->subMonths(rand(1, 6)),
        'retirement' => now()->subYears(rand(1, 5)),
        default => now()->subMonths(rand(1, 12)),
    };
    
    $end = match ($type) {
        'employment' => rand(0, 1) ? $start->copy()->addMonths(rand(1, 12)) : null,
        'injury' => rand(0, 1) ? $start->copy()->addWeeks(rand(1, 8)) : null,
        'suspension' => rand(0, 1) ? $start->copy()->addMonths(rand(1, 3)) : null,
        'retirement' => rand(0, 1) ? $start->copy()->addYears(rand(1, 3)) : null,
        default => rand(0, 1) ? $start->copy()->addMonths(rand(1, 6)) : null,
    };
    
    return [
        'started_at' => $start,
        'ended_at' => $end,
    ];
}