# Factory Capabilities

## Overview

The `MatchFactory` provides comprehensive match generation capabilities through the `generateFullMatch()` method, supporting 22+ different configuration scenarios. This document details all available capabilities and configuration options.

## generateFullMatch() Method

### Method Signature

```php
public function generateFullMatch(array $config): static
```

The method accepts a configuration array and returns a factory instance ready for creation.

## Configuration Options

### Match Types

Specify the type of match to generate:

```php
// Supported match types
'match_type' => 'singles'          // 1v1 wrestler match
'match_type' => 'tagteam'          // Tag team vs tag team
'match_type' => 'triple-threat'    // 3-way match
'match_type' => 'fatal-4-way'      // 4-way match
'match_type' => 'battleroyal'      // Multi-competitor elimination
'match_type' => 'royal-rumble'     // Special rumble format
```

### Competitor Configuration

#### Automatic Generation

```php
// Generate based on match type minimums
$config = [
    'match_type' => 'singles'  // Automatically creates 2 wrestlers
];

// Override competitor count
$config = [
    'match_type' => 'battleroyal',
    'competitor_count' => 15
];
```

#### Specific Competitors

```php
// Using type hints
$config = [
    'competitors' => ['wrestler', 'tag_team']
];

// Using model instances
$config = [
    'competitors' => [$wrestler1, $tagTeam1]
];

// Using names (creates wrestlers)
$config = [
    'competitors' => ['John Cena', 'The Rock']
];

// Mixed types
$config = [
    'competitors' => [$existingWrestler, 'wrestler', 'New Wrestler Name']
];
```

### Winner Strategies

Control how winners and losers are determined:

```php
'winner_strategy' => 'random'      // Random winner (default)
'winner_strategy' => 'first'       // First competitor wins
'winner_strategy' => 'last'        // Last competitor wins
'winner_strategy' => 'multiple'    // Multiple random winners
'winner_strategy' => 'all_but_one' // All but last competitor win
'winner_strategy' => 'single'      // Single random winner
```

### Decision Types

Specify how the match ends:

```php
'decision_type' => 'pinfall'       // Standard pinfall victory
'decision_type' => 'submission'    // Submission victory
'decision_type' => 'countout'      // Count-out victory
'decision_type' => 'disqualification' // DQ victory
'decision_type' => 'draw'          // Match ends in draw
'decision_type' => 'nodecision'    // No official decision
```

### Title Matches

Configure championship implications:

```php
// Single title match
$config = [
    'titles' => [$title]
];

// Multiple titles
$config = [
    'titles' => [$title1, $title2]
];

// Automatic champion inclusion
// If titles are specified, existing champions are automatically added as competitors
```

### Referee Assignment

Add referees to matches:

```php
// Create specified number of referees
$config = [
    'referees' => 2  // Creates 2 new referees
];

// Use specific referee models
$config = [
    'referees' => [$referee1, $referee2]
];
```

## Complete Configuration Examples

### Basic Singles Match

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'decision_type' => 'pinfall',
    'winner_strategy' => 'random'
])->create();
```

### Title Defense Match

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'titles' => [$worldTitle],
    'decision_type' => 'submission',
    'winner_strategy' => 'first',  // Champion retains
    'referees' => 1
])->create();
```

### Battle Royal

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'battleroyal',
    'competitor_count' => 20,
    'decision_type' => 'pinfall',
    'winner_strategy' => 'single'
])->create();
```

### Tag Team Championship Match

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'tagteam',
    'titles' => [$tagTeamTitle],
    'competitors' => [$championTeam, $challengerTeam],
    'decision_type' => 'pinfall',
    'winner_strategy' => 'last',   // Challengers win
    'referees' => [$seniorReferee]
])->create();
```

### Multi-Title Unification Match

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'titles' => [$title1, $title2],
    'decision_type' => 'pinfall',
    'winner_strategy' => 'random',
    'referees' => 2
])->create();
```

### No Contest Match

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'competitors' => ['Wrestler A', 'Wrestler B'],
    'decision_type' => 'nodecision',
    'referees' => 1
])->create();
```

## Advanced Features

### Match Type Restrictions

The factory enforces business rules:

- **Royal Rumble**: Only allows wrestlers (no tag teams)
- **Singles**: Only allows wrestlers (no tag teams)
- **Other types**: Allow both wrestlers and tag teams based on `MatchType::getAllowedCompetitorTypes()`

### Championship Integration

When titles are specified:

1. Existing champions are automatically identified
2. Champions are added to the competitor pool
3. Title defense scenarios are properly configured
4. Championship records are validated

### Winner/Loser Resolution

The factory creates comprehensive winner/loser records:

- **MatchResult**: Stores primary winner for backward compatibility
- **MatchWinner**: Individual winner records (supports multiple winners)
- **MatchLoser**: Individual loser records
- **Foreign Key Relationships**: Winners/losers link to MatchCompetitor records

### Competitor Side Assignment

Competitors are assigned to sides automatically:

```php
// Automatic side numbering
Side 0: First competitor
Side 1: Second competitor  
Side N: Nth competitor
```

## Error Handling

### Configuration Validation

The factory validates configurations and throws descriptive exceptions:

```php
// Invalid match type
'match_type' => 'invalid'  // Throws InvalidArgumentException

// Insufficient competitors for match type
'match_type' => 'tagteam',
'competitors' => ['wrestler']  // May cause business rule violations
```

### Business Rule Enforcement

- Match types enforce competitor type restrictions
- Title matches validate championship status  
- Winner strategies ensure logical outcomes

## Performance Characteristics

### Database Efficiency

- Single transaction for complete match creation
- Bulk competitor creation when possible
- Optimized relationship queries

### Memory Usage

- Configuration stored temporarily during creation
- Lazy loading of related models
- Automatic cleanup after match creation

## Testing Support

The factory supports comprehensive testing scenarios:

### Deterministic Testing

```php
// Predictable outcomes for testing
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'competitors' => [$wrestler1, $wrestler2],
    'winner_strategy' => 'first',  // $wrestler1 always wins
    'decision_type' => 'pinfall'
])->create();

expect($match->result->winner)->toBe($wrestler1);
```

### Statistical Testing

```php
// Test randomness over multiple iterations
for ($i = 0; $i < 100; $i++) {
    $match = MatchFactory::new()->generateFullMatch([
        'match_type' => 'singles',
        'winner_strategy' => 'random'
    ])->create();
    
    // Collect statistics on winners
}
```

## Integration Examples

### Event Integration

```php
$event = Event::factory()->create();

$match = MatchFactory::new()
    ->forEvent($event)
    ->generateFullMatch([
        'match_type' => 'singles',
        'match_number' => 5
    ])->create();
```

### Bulk Match Generation

```php
// Generate card for entire event
$matches = collect();

for ($i = 1; $i <= 8; $i++) {
    $matches->push(
        MatchFactory::new()->generateFullMatch([
            'match_type' => fake()->randomElement(['singles', 'tagteam']),
            'match_number' => $i
        ])->create()
    );
}
```

This comprehensive configuration system enables the creation of virtually any match scenario while maintaining data integrity and business rule compliance.