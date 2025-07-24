# Tournament Generation

## Overview

The Tournament Generation system is designed to support complex multi-match tournament scenarios including elimination brackets, round-robin competitions, and championship tournaments. While the core match generation system is complete, this document outlines the architecture for future tournament features.

## Tournament Types

### Single Elimination Bracket

Traditional knockout tournament format:

```
Round 1:        Round 2:      Finals:
A vs B    ──────→
                  Winner 1 ──────→
C vs D    ──────→              Final Winner
                  Winner 2 ──────→
E vs F    ──────→
                  Winner 3 ──────→
G vs H    ──────→
```

### Double Elimination

Advanced bracket with winner's and loser's brackets:

```
Winner's Bracket:
A vs B ──→ Winner 1 ──→ WB Final ──→ Grand Final
C vs D ──→ Winner 2 ──→         ──→

Loser's Bracket:
Loser 1 ──→ LB Semi ──→ LB Final ──→ Grand Final
Loser 2 ──→         ──→         ──→ (if needed)
```

### Round Robin

Every competitor faces every other competitor:

```php
// 4-person round robin
Matches:
A vs B, A vs C, A vs D
B vs C, B vs D  
C vs D

// Winner determined by record (wins/losses)
```

### King of the Ring Style

Multi-stage tournament with different rules per round:

```php
// First Round: Standard matches
// Semi-Finals: No DQ matches  
// Finals: Special stipulation
```

## Core Tournament Architecture

### TournamentFactory (Future Implementation)

```php
class TournamentFactory extends Factory
{
    public function singleElimination(array $competitors, array $config = []): Tournament
    {
        return $this->createTournament('single_elimination', $competitors, $config);
    }
    
    public function doubleElimination(array $competitors, array $config = []): Tournament
    {
        return $this->createTournament('double_elimination', $competitors, $config);
    }
    
    public function roundRobin(array $competitors, array $config = []): Tournament
    {
        return $this->createTournament('round_robin', $competitors, $config);
    }
}
```

### Tournament Model Structure

```php
class Tournament extends Model
{
    protected $fillable = [
        'name',
        'type',              // single_elimination, double_elimination, round_robin
        'status',            // upcoming, in_progress, completed
        'start_date',
        'end_date',
        'bracket_data',      // JSON field for bracket structure
        'rules',             // JSON field for tournament rules
    ];
    
    public function competitors(): BelongsToMany
    {
        return $this->belongsToMany(Model::class, 'tournament_competitors')
            ->using(TournamentCompetitor::class);
    }
    
    public function matches(): HasMany
    {
        return $this->hasMany(EventMatch::class);
    }
    
    public function rounds(): HasMany
    {
        return $this->hasMany(TournamentRound::class);
    }
}
```

### Tournament Round Management

```php
class TournamentRound extends Model
{
    protected $fillable = [
        'tournament_id',
        'round_number',
        'round_name',        // "Quarterfinals", "Semifinals", "Finals"
        'status',
        'start_date',
        'match_configuration', // JSON field for round-specific rules
    ];
    
    public function matches(): HasMany
    {
        return $this->hasMany(EventMatch::class);
    }
}
```

## Tournament Generation Process

### 1. Bracket Creation

```php
public function generateBracket(array $competitors, string $type): array
{
    return match($type) {
        'single_elimination' => $this->createSingleEliminationBracket($competitors),
        'double_elimination' => $this->createDoubleEliminationBracket($competitors),
        'round_robin' => $this->createRoundRobinSchedule($competitors),
        default => throw new InvalidArgumentException("Unknown tournament type: {$type}")
    };
}

private function createSingleEliminationBracket(array $competitors): array
{
    // Ensure power of 2 competitors (add byes if needed)
    $paddedCompetitors = $this->padToPowerOfTwo($competitors);
    
    // Create first round matchups
    $firstRound = [];
    for ($i = 0; $i < count($paddedCompetitors); $i += 2) {
        $firstRound[] = [
            'competitor_1' => $paddedCompetitors[$i],
            'competitor_2' => $paddedCompetitors[$i + 1] ?? null, // bye
            'round' => 1
        ];
    }
    
    return $this->generateSubsequentRounds($firstRound);
}
```

### 2. Match Scheduling

```php
public function scheduleMatches(Tournament $tournament, Event $event): void
{
    $bracket = $tournament->bracket_data;
    $currentRound = $tournament->getCurrentRound();
    
    foreach ($currentRound->plannedMatches as $matchData) {
        $match = MatchFactory::new()->generateFullMatch([
            'match_type' => $matchData['match_type'] ?? 'singles',
            'competitors' => $this->resolveCompetitors($matchData),
            'tournament_id' => $tournament->id,
            'round_id' => $currentRound->id,
            'winner_strategy' => 'random', // Will be updated with actual result
        ])->forEvent($event)->create();
        
        $this->linkTournamentMatch($tournament, $match, $matchData);
    }
}
```

### 3. Advancement Logic

```php
public function advanceWinners(TournamentRound $round): void
{
    $completedMatches = $round->matches()->whereNotNull('result_id')->get();
    
    foreach ($completedMatches as $match) {
        $winner = $match->result->winners->first();
        $this->advanceCompetitor($winner, $round->tournament, $round->round_number + 1);
    }
    
    // Check if round is complete
    if ($this->isRoundComplete($round)) {
        $this->createNextRound($round->tournament);
    }
}

private function advanceCompetitor(MatchWinner $winner, Tournament $tournament, int $nextRound): void
{
    $competitor = $winner->competitor->competitor;
    
    // Find next round bracket position
    $nextRoundPosition = $this->calculateNextPosition($tournament, $winner, $nextRound);
    
    // Update tournament bracket
    $bracketData = $tournament->bracket_data;
    $bracketData['rounds'][$nextRound]['matches'][$nextRoundPosition]['competitors'][] = $competitor;
    
    $tournament->update(['bracket_data' => $bracketData]);
}
```

## Championship Tournament Integration

### Title Tournament Generation

```php
public function createChampionshipTournament(Title $title, array $competitors): Tournament
{
    return TournamentFactory::new()->singleElimination($competitors, [
        'championship' => $title,
        'finals_config' => [
            'match_type' => $title->type->value === 'tag-team' ? 'tagteam' : 'singles',
            'titles' => [$title],
            'decision_type' => 'pinfall'
        ]
    ]);
}
```

### Tournament Finals with Title

```php
public function generateTournamentFinal(Tournament $tournament): EventMatch
{
    $finalists = $this->getTournamentFinalists($tournament);
    
    $config = [
        'match_type' => $tournament->rules['finals_match_type'] ?? 'singles',
        'competitors' => $finalists,
        'winner_strategy' => 'random'
    ];
    
    // Add championship if tournament is for a title
    if ($tournament->championship) {
        $config['titles'] = [$tournament->championship];
    }
    
    return MatchFactory::new()->generateFullMatch($config)->create();
}
```

## Advanced Tournament Features

### Seeded Tournaments

```php
public function createSeededBracket(array $seededCompetitors): array
{
    // Sort by seed (1 is highest seed)
    usort($seededCompetitors, fn($a, $b) => $a['seed'] <=> $b['seed']);
    
    // Create bracket with strategic placement
    return $this->arrangeSeedsInBracket($seededCompetitors);
}

private function arrangeSeedsInBracket(array $seeds): array
{
    // Traditional seeding: 1 vs lowest, 2 vs second-lowest, etc.
    $bracket = [];
    $count = count($seeds);
    
    for ($i = 0; $i < $count / 2; $i++) {
        $bracket[] = [
            'high_seed' => $seeds[$i],
            'low_seed' => $seeds[$count - 1 - $i]
        ];
    }
    
    return $bracket;
}
```

### Multi-Stage Tournaments

```php
public function createMultiStageTournament(array $config): Tournament
{
    $tournament = Tournament::create($config['tournament_data']);
    
    // Stage 1: Qualifying rounds
    $qualifiers = $this->createQualifyingRounds($config['qualifiers']);
    
    // Stage 2: Main bracket  
    $qualifiedCompetitors = $this->getQualifiedCompetitors($qualifiers);
    $mainBracket = $this->createMainBracket($qualifiedCompetitors);
    
    return $tournament;
}
```

### Round Robin Scheduling

```php
public function generateRoundRobinMatches(array $competitors): array
{
    $matches = [];
    $count = count($competitors);
    
    // Generate all possible pairings
    for ($i = 0; $i < $count; $i++) {
        for ($j = $i + 1; $j < $count; $j++) {
            $matches[] = [
                'competitor_1' => $competitors[$i],
                'competitor_2' => $competitors[$j],
                'round' => $this->calculateRoundRobinRound($i, $j, $count)
            ];
        }
    }
    
    return $matches;
}
```

## Tournament Reporting

### Bracket Visualization

```php
public function generateBracketVisualization(Tournament $tournament): array
{
    $bracket = $tournament->bracket_data;
    
    return [
        'tournament_name' => $tournament->name,
        'type' => $tournament->type,
        'rounds' => $this->formatRoundsForDisplay($bracket['rounds']),
        'current_round' => $tournament->getCurrentRound()->round_number,
        'status' => $tournament->status
    ];
}
```

### Tournament Statistics

```php
public function getTournamentStats(Tournament $tournament): array
{
    return [
        'total_competitors' => $tournament->competitors()->count(),
        'matches_played' => $tournament->matches()->whereNotNull('result_id')->count(),
        'matches_remaining' => $tournament->matches()->whereNull('result_id')->count(),
        'rounds_completed' => $tournament->rounds()->where('status', 'completed')->count(),
        'current_leader' => $this->getCurrentTournamentLeader($tournament)
    ];
}
```

## Testing Tournament Generation

### Unit Tests

```php
test('single elimination bracket creates correct number of matches', function () {
    $competitors = Wrestler::factory()->count(8)->create();
    $tournament = TournamentFactory::new()->singleElimination($competitors);
    
    // 8 competitors = 7 matches total (4 + 2 + 1)
    expect($tournament->calculateTotalMatches())->toBe(7);
});

test('round robin creates all necessary pairings', function () {
    $competitors = Wrestler::factory()->count(4)->create();
    $tournament = TournamentFactory::new()->roundRobin($competitors);
    
    // 4 competitors = 6 matches (4C2)
    expect($tournament->matches()->count())->toBe(6);
});
```

### Integration Tests

```php
test('tournament final crowns champion', function () {
    $title = Title::factory()->create();
    $competitors = Wrestler::factory()->count(4)->create();
    
    $tournament = TournamentFactory::new()->createChampionshipTournament($title, $competitors);
    
    // Simulate tournament progression
    $this->playTournamentToCompletion($tournament);
    
    $winner = $tournament->getWinner();
    expect($title->currentChampion())->toBe($winner);
});
```

## Performance Considerations

### Bracket Caching

Large tournaments benefit from bracket caching:

```php
public function getCachedBracket(Tournament $tournament): array
{
    return Cache::remember(
        "tournament_bracket_{$tournament->id}",
        3600,
        fn() => $this->generateBracketVisualization($tournament)
    );
}
```

### Batch Match Creation

Create tournament matches efficiently:

```php
public function createTournamentMatches(Tournament $tournament, array $matchData): void
{
    $matches = collect($matchData)->map(function ($data) use ($tournament) {
        return MatchFactory::new()->generateFullMatch($data)->make();
    });
    
    $tournament->matches()->saveMany($matches);
}
```

The Tournament Generation system provides a comprehensive framework for complex multi-match competitions while leveraging the existing match generation infrastructure for consistent match creation and result handling.