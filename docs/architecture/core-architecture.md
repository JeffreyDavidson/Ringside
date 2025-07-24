# Core Architecture

## Overview

The match generation system in Ringside is built around Laravel Eloquent factories with a sophisticated configuration-driven approach. The core architecture enables flexible match creation while maintaining data integrity and business rule compliance.

## Primary Components

### MatchFactory (`database/factories/Matches/MatchFactory.php`)

The `MatchFactory` is the central orchestrator for match generation, providing both simple factory methods and the comprehensive `generateFullMatch()` method.

#### Key Design Principles

1. **Configuration-Driven**: Matches are created through structured configuration arrays
2. **Extensible**: New match types and scenarios can be added without breaking existing functionality
3. **Type Safety**: Strong typing ensures data integrity across all match generation
4. **Backward Compatibility**: New features maintain compatibility with existing code

#### Core Factory Methods

```php
// Simple match creation
MatchFactory::new()->singles()->create();
MatchFactory::new()->tagTeam()->create();

// Advanced configuration
MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'competitors' => ['wrestler', 'wrestler'],
    'winner_strategy' => 'first',
    'decision_type' => 'pinfall'
])->create();
```

### Model Relationships

The factory integrates with several key models:

- **EventMatch**: Core match entity
- **MatchCompetitor**: Links competitors to matches
- **MatchResult**: Stores match outcomes
- **MatchWinner/MatchLoser**: Tracks individual winners and losers
- **MatchType**: Defines match rules and competitor requirements
- **MatchDecision**: Specifies how matches end

## Architecture Patterns

### Factory State Pattern

The factory uses Laravel's state pattern extensively:

```php
public function singles(): static
{
    return $this->createMatchType('singles');
}

private function createMatchType(string $factoryMethod): static
{
    return $this->state([
        'match_type_id' => MatchType::factory()->{$factoryMethod}()->create()->id,
    ])->afterCreating(function (EventMatch $eventMatch) {
        $this->addCompetitors($eventMatch);
        $this->addResult($eventMatch);
    });
}
```

### Configuration Resolution

The `generateFullMatch()` method uses a resolution pattern to convert configuration arrays into database entities:

1. **Match Type Resolution**: String slugs → MatchType models
2. **Competitor Resolution**: Mixed types → Competitor models
3. **Decision Resolution**: String types → MatchDecision models
4. **Strategy Resolution**: Winner strategies → Competitor collections

### Callback Architecture

The factory uses Laravel's `afterCreating()` callbacks to build complex relationships:

```php
public function generateFullMatch(array $config): static
{
    $this->matchConfig = $config;
    
    return $this->state([
        'match_type_id' => $matchType->id,
    ])->afterCreating(function (EventMatch $eventMatch) {
        $this->configureFullMatch($eventMatch, $this->matchConfig);
    });
}
```

## Data Flow

### Match Creation Process

1. **Configuration Parsing**: Input configuration is validated and parsed
2. **Match Type Creation**: MatchType is resolved and assigned
3. **Base Match Creation**: EventMatch record is created
4. **Competitor Generation**: Competitors are created and linked
5. **Result Generation**: Match results, winners, and losers are created
6. **Relationship Binding**: All entities are properly linked

### Competitor Resolution Flow

```
Configuration Input → Type Detection → Model Creation → Relationship Binding
     ↓                    ↓               ↓                ↓
['wrestler']  →    Wrestler Type  →  Wrestler Model  → MatchCompetitor
['tag_team']  →    TagTeam Type   →  TagTeam Model   → MatchCompetitor  
[Model]       →    Direct Use     →  Existing Model  → MatchCompetitor
```

## Error Handling

### Validation Layers

1. **Configuration Validation**: Invalid configurations throw descriptive exceptions
2. **Business Rule Validation**: Match type restrictions are enforced
3. **Database Constraints**: Foreign key relationships prevent orphaned records
4. **Type Safety**: PHP 8+ union types prevent type errors

### Match Type Restrictions

The factory enforces business rules through match type validation:

```php
private function createCompetitorWithMatchTypeRestrictions(MatchType $matchType): Model
{
    // Royal Rumble and Singles only allow wrestlers
    if (in_array($matchType->slug, ['royal-rumble', 'singles'])) {
        return Wrestler::factory()->create();
    }
    
    return $this->createRandomCompetitor($matchType->getAllowedCompetitorTypes());
}
```

## Extensibility Points

### Adding New Match Types

1. Create new MatchType factory method
2. Add match type resolution in `resolveMatchType()`
3. Define competitor restrictions if needed
4. Add any specific business logic

### Adding New Winner Strategies

1. Add new case to `resolveWinnersAndLosers()` match expression
2. Implement strategy logic
3. Update documentation

### Adding New Configuration Options

1. Extend configuration validation
2. Add resolution logic in `configureFullMatch()`
3. Update PHPDoc and tests

## Performance Considerations

### Database Efficiency

- Bulk creation methods reduce query count
- Foreign key relationships optimize joins
- Proper indexing on match relationships

### Memory Management

- Lazy loading of relationships
- Configuration stored temporarily during creation
- Cleanup of temporary state after creation

## Testing Architecture

The factory supports comprehensive testing through:

- Deterministic configuration testing
- Statistical uniqueness validation
- Business rule compliance testing
- Integration scenario testing

See `tests/Unit/database/Factories/Matches/MatchGenerationTest.php` for comprehensive test examples covering all 22 generation scenarios.