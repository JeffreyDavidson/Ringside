# Winner/Loser Architecture

## Overview

The winner/loser architecture in Ringside has been modernized to use foreign key relationships instead of polymorphic columns, providing better data integrity, performance, and referential consistency. This architecture supports complex match scenarios with multiple winners and losers.

## Architectural Evolution

### Previous Architecture (Polymorphic)

The original design used polymorphic relationships:

```php
// Old schema (deprecated)  
events_matches_winners:
- id
- match_result_id
- winner_type (morph column)
- winner_id (morph column)

events_matches_losers:
- id  
- match_result_id
- loser_type (morph column)
- loser_id (morph column)
```

### Current Architecture (Foreign Key)

The modernized design uses proper foreign key relationships:

```php
// New schema (current)
events_matches_winners:
- id
- match_result_id → events_matches_results.id
- match_competitor_id → events_matches_competitors.id

events_matches_losers:
- id
- match_result_id → events_matches_results.id  
- match_competitor_id → events_matches_competitors.id
```

## Benefits of Foreign Key Architecture

### Data Integrity

1. **Referential Integrity**: Database-level constraints prevent orphaned records
2. **Cascading Deletes**: Automatic cleanup when parent records are deleted
3. **Type Safety**: Eliminates polymorphic type mismatches

### Performance Improvements

1. **Optimized Queries**: Foreign key joins are more efficient than polymorphic queries
2. **Better Indexing**: Standard foreign key indexes improve query performance
3. **Query Planning**: Database optimizers work better with foreign key relationships

### Maintenance Benefits

1. **Simplified Relationships**: Cleaner Eloquent relationship definitions
2. **Consistent Data Model**: All match-related entities use consistent foreign key patterns
3. **Easier Debugging**: Clearer data relationships and query paths

## Database Schema

### Migration Structure

```php
// Winner table migration
Schema::create('events_matches_winners', function (Blueprint $table) {
    $table->id();
    $table->foreignId('match_result_id')
        ->constrained('events_matches_results')
        ->cascadeOnDelete();
    $table->foreignId('match_competitor_id')
        ->constrained('events_matches_competitors')
        ->cascadeOnDelete();
    $table->timestamps();
    
    // Indexes for performance
    $table->index(['match_result_id', 'match_competitor_id']);
});

// Loser table migration  
Schema::create('events_matches_losers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('match_result_id')
        ->constrained('events_matches_results')
        ->cascadeOnDelete();
    $table->foreignId('match_competitor_id')
        ->constrained('events_matches_competitors')
        ->cascadeOnDelete();
    $table->timestamps();
    
    // Indexes for performance
    $table->index(['match_result_id', 'match_competitor_id']);
});
```

### Relationship Chain

The complete relationship chain provides full context:

```
EventMatch
    ↓
MatchCompetitor (competitor_type, competitor_id → Wrestler/TagTeam)
    ↓
MatchWinner/MatchLoser (match_competitor_id → MatchCompetitor)
    ↓
MatchResult (match_result_id → MatchResult)
```

## Model Implementation

### MatchWinner Model

```php
class MatchWinner extends Model
{
    protected $table = 'events_matches_winners';
    
    protected $fillable = [
        'match_result_id',
        'match_competitor_id',
    ];
    
    // Core relationships
    public function matchResult(): BelongsTo
    {
        return $this->belongsTo(MatchResult::class);
    }
    
    public function competitor(): BelongsTo
    {
        return $this->belongsTo(MatchCompetitor::class, 'match_competitor_id');
    }
    
    // Convenience methods
    public function winner(): Wrestler|TagTeam
    {
        return $this->competitor->competitor;
    }
    
    // Backward compatibility accessors
    public function getWinnerTypeAttribute(): string
    {
        return $this->competitor->competitor_type;
    }
    
    public function getWinnerIdAttribute(): int
    {
        return $this->competitor->competitor_id;
    }
}
```

### MatchLoser Model

```php
class MatchLoser extends Model
{
    protected $table = 'events_matches_losers';
    
    protected $fillable = [
        'match_result_id', 
        'match_competitor_id',
    ];
    
    // Core relationships
    public function matchResult(): BelongsTo
    {
        return $this->belongsTo(MatchResult::class);
    }
    
    public function competitor(): BelongsTo
    {
        return $this->belongsTo(MatchCompetitor::class, 'match_competitor_id');
    }
    
    // Convenience methods  
    public function loser(): Wrestler|TagTeam
    {
        return $this->competitor->competitor;
    }
    
    // Backward compatibility accessors
    public function getLoserTypeAttribute(): string
    {
        return $this->competitor->competitor_type;
    }
    
    public function getLoserIdAttribute(): int
    {
        return $this->competitor->competitor_id;
    }
}
```

## Factory Integration

### Winner/Loser Creation

The MatchFactory creates winner/loser records through the foreign key architecture:

```php
private function createFullMatchResult(EventMatch $eventMatch, array $config): void
{
    $competitors = $eventMatch->competitors;
    [$winners, $losers] = $this->resolveWinnersAndLosers($competitors, $winnerStrategy);
    
    // Create match result
    $matchResult = MatchResult::factory()->create([
        'match_id' => $eventMatch->id,
        'match_decision_id' => $decision->id,
        'winner_type' => $primaryWinner->competitor_type,
        'winner_id' => $primaryWinner->competitor_id,
    ]);
    
    // Create winner records via foreign keys
    foreach ($winners as $winner) {
        MatchWinner::factory()->create([
            'match_result_id' => $matchResult->id,
            'match_competitor_id' => $winner->id,  // Foreign key to competitor
        ]);
    }
    
    // Create loser records via foreign keys
    foreach ($losers as $loser) {
        MatchLoser::factory()->create([
            'match_result_id' => $matchResult->id,
            'match_competitor_id' => $loser->id,  // Foreign key to competitor
        ]);
    }
}
```

### Multiple Winners/Losers Support

The architecture naturally supports multiple winners and losers:

```php
// Triple threat with 2 winners, 1 loser
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'triple-threat',
    'winner_strategy' => 'multiple'  // Randomly selects 1-2 winners
])->create();

// Battle royal with single winner, many losers
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'battle-royal',
    'competitor_count' => 20,
    'winner_strategy' => 'single'    // 1 winner, 19 losers
])->create();
```

## Backward Compatibility

### Accessor Methods

To maintain compatibility with existing code, accessor methods provide the old polymorphic interface:

```php
$winner = MatchWinner::first();

// New foreign key approach
$competitor = $winner->competitor->competitor;  // Wrestler or TagTeam model

// Old polymorphic interface (still works)
$winnerType = $winner->winner_type;  // 'App\Models\Wrestlers\Wrestler'
$winnerId = $winner->winner_id;      // 123
```

### Migration Strategy

The migration preserves existing functionality:

1. **Create New Tables**: New foreign key tables are created
2. **Preserve Old Columns**: Original morph columns remain (for rollback)
3. **Accessor Compatibility**: Getters maintain old interface
4. **Gradual Migration**: Code can be updated incrementally

## Query Patterns

### Eager Loading

The foreign key architecture enables efficient eager loading:

```php
// Load winners with competitors and their models
$winners = MatchWinner::with([
    'competitor.competitor',  // Load the actual wrestler/tag team
    'matchResult.match'       // Load match context
])->get();

// Load match with all winners and losers
$match = EventMatch::with([
    'result.winners.competitor.competitor',
    'result.losers.competitor.competitor'
])->find($matchId);
```

### Complex Queries

Foreign keys enable complex queries:

```php
// Find all matches where a specific wrestler won
$wrestlerWins = MatchWinner::whereHas('competitor', function ($query) use ($wrestler) {
    $query->where('competitor_type', Wrestler::class)
          ->where('competitor_id', $wrestler->id);
})->get();

// Find matches with multiple winners  
$multiWinnerMatches = MatchResult::whereHas('winners', function ($query) {
    $query->select('match_result_id')
          ->groupBy('match_result_id')
          ->havingRaw('COUNT(*) > 1');
})->get();
```

### Performance Queries

```php
// Count wins for a wrestler efficiently
$winCount = MatchWinner::join('events_matches_competitors', 
    'events_matches_winners.match_competitor_id', '=', 
    'events_matches_competitors.id')
    ->where('competitor_type', Wrestler::class)
    ->where('competitor_id', $wrestler->id)
    ->count();
```

## Testing Architecture

### Unit Tests

```php
test('winner has proper foreign key relationship', function () {
    $match = MatchFactory::new()->complete()->create(); 
    $winner = $match->result->winners->first();
    
    expect($winner->competitor)->toBeInstanceOf(MatchCompetitor::class);
    expect($winner->competitor->competitor)->toBeInstanceOf(Wrestler::class);
});

test('backward compatibility accessors work', function () {
    $winner = MatchWinner::factory()->create();
    
    expect($winner->winner_type)->toBe($winner->competitor->competitor_type);
    expect($winner->winner_id)->toBe($winner->competitor->competitor_id);
});
```

### Integration Tests

```php
test('multiple winners are properly created', function () {
    $match = MatchFactory::new()->generateFullMatch([
        'match_type' => 'fatal-4-way',
        'winner_strategy' => 'multiple'
    ])->create();
    
    expect($match->result->winners)->toHaveCount(
        fake()->numberBetween(1, 3)
    );
    
    $match->result->winners->each(function ($winner) {
        expect($winner->competitor)->toBeInstanceOf(MatchCompetitor::class);
    });
});
```

## Migration Path

### Data Migration

When migrating from polymorphic to foreign key architecture:

```php
// Migration to populate new foreign key columns
foreach (MatchWinner::all() as $winner) {
    $competitor = MatchCompetitor::where('match_id', $winner->match_result->match_id)
        ->where('competitor_type', $winner->winner_type)
        ->where('competitor_id', $winner->winner_id)
        ->first();
        
    if ($competitor) {
        $winner->update(['match_competitor_id' => $competitor->id]);
    }
}
```

### Rollback Strategy

The architecture maintains rollback capability:

1. **Keep Morph Columns**: Don't drop original columns immediately
2. **Dual Population**: Populate both old and new columns during transition
3. **Feature Flags**: Use flags to switch between old/new queries
4. **Gradual Rollout**: Test new architecture with subset of matches

The foreign key architecture provides a robust, performant, and maintainable foundation for winner/loser tracking while preserving backward compatibility and enabling future enhancements.