# Match Type System

## Overview

The match type system in Ringside defines the rules, constraints, and behaviors for different types of wrestling matches. The system integrates tightly with the MatchFactory to ensure business rule compliance and proper match generation.

## Core Components

### MatchType Model

The `MatchType` model serves as the foundation for match rules and constraints:

```php
// Key attributes
- name: Human-readable match type name
- slug: URL-friendly identifier
- min_competitors: Minimum required competitors
- max_competitors: Maximum allowed competitors (nullable)
- description: Match type description
```

### MatchType Factory

The MatchType factory provides convenient creation methods for common match types:

```php
MatchType::factory()->singles()->create();      // 1v1 matches
MatchType::factory()->tagTeam()->create();      // Tag team matches  
MatchType::factory()->tripleThreat()->create(); // 3-way matches
MatchType::factory()->fatalFourWay()->create(); // 4-way matches
MatchType::factory()->battleRoyal()->create();  // Multi-competitor
MatchType::factory()->royalRumble()->create();  // Special rumble format
```

## Match Type Definitions

### Singles Match

```php
'name' => 'Singles Match'
'slug' => 'singles'
'min_competitors' => 2
'max_competitors' => 2
'allowed_types' => ['wrestler']
```

**Rules:**
- Exactly 2 competitors required
- Only wrestlers allowed (no tag teams)
- Standard 1v1 competition format

### Tag Team Match

```php
'name' => 'Tag Team Match'  
'slug' => 'tagteam'
'min_competitors' => 2
'max_competitors' => 2
'allowed_types' => ['tag_team']
```

**Rules:**
- Exactly 2 tag teams required
- Only tag teams allowed (no individual wrestlers)
- Team-based competition format

### Triple Threat Match

```php
'name' => 'Triple Threat Match'
'slug' => 'triple-threat'  
'min_competitors' => 3
'max_competitors' => 3
'allowed_types' => ['wrestler', 'tag_team']
```

**Rules:**
- Exactly 3 competitors required
- Wrestlers or tag teams allowed
- No disqualifications typically

### Fatal Four Way Match

```php
'name' => 'Fatal Four Way Match'
'slug' => 'fatal-4-way'
'min_competitors' => 4  
'max_competitors' => 4
'allowed_types' => ['wrestler', 'tag_team']
```

**Rules:**
- Exactly 4 competitors required
- Mixed competitor types allowed
- Elimination or pinfall format

### Battle Royal

```php
'name' => 'Battle Royal'
'slug' => 'battle-royal'
'min_competitors' => 3
'max_competitors' => null  // No upper limit
'allowed_types' => ['wrestler']
```

**Rules:**
- Minimum 3 competitors
- No maximum limit (system dependent)
- Over-the-top-rope elimination
- Only wrestlers allowed

### Royal Rumble

```php
'name' => 'Royal Rumble'
'slug' => 'royal-rumble'  
'min_competitors' => 10
'max_competitors' => 30
'allowed_types' => ['wrestler']
```

**Rules:**
- 10-30 competitors typical range
- Timed entry system
- Over-the-top-rope elimination  
- Only wrestlers allowed

## Match Type Resolution

### Factory Integration

The MatchFactory resolves match types from configuration strings:

```php
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
```

### Slug Variations

The system accepts multiple slug variations for flexibility:

```php
// Tag team variations
'tagteam' → 'tag-team'
'tag_team' → 'tag-team'

// Triple threat variations  
'triple' → 'triple-threat'
'triplethreat' → 'triple-threat'

// Fatal four way variations
'fatal4way' → 'fatal-4-way'
'fatalfourway' → 'fatal-4-way'
```

## Competitor Type Restrictions

### Business Rule Enforcement

The factory enforces match type restrictions during competitor generation:

```php
private function createCompetitorWithMatchTypeRestrictions(MatchType $matchType): Model
{
    // Royal Rumble and Singles only allow wrestlers
    if (in_array($matchType->slug, ['royal-rumble', 'singles'])) {
        return Wrestler::factory()->create();
    }
    
    // Other match types use getAllowedCompetitorTypes()
    return $this->createRandomCompetitor($matchType->getAllowedCompetitorTypes());
}
```

### Allowed Competitor Types

Each match type defines allowed competitor types:

```php
// Example MatchType method
public function getAllowedCompetitorTypes(): array
{
    return match ($this->slug) {
        'singles', 'royal-rumble', 'battle-royal' => ['wrestler'],
        'tagteam' => ['tag_team'],
        'triple-threat', 'fatal-4-way' => ['wrestler', 'tag_team'],
        default => ['wrestler', 'tag_team'],
    };
}
```

## Match Type Validation

### Competitor Count Validation

```php
public function validateCompetitorCount(int $count): bool
{
    if ($count < $this->min_competitors) {
        return false;
    }
    
    if ($this->max_competitors && $count > $this->max_competitors) {
        return false;
    }
    
    return true;
}
```

### Competitor Type Validation

```php
public function validateCompetitorType(string $type): bool
{
    return in_array($type, $this->getAllowedCompetitorTypes());
}
```

## Integration with Factory System

### Automatic Competitor Generation

When using `generateFullMatch()`, the match type determines:

1. **Minimum Competitors**: Default competitor count if not specified
2. **Allowed Types**: Which competitor types can be generated
3. **Business Rules**: Special restrictions and requirements

```php
// Battle royal with match type constraints
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'battle-royal',
    'competitor_count' => 15  // Validates against min_competitors
])->create();
```

### Match Type-Specific Logic

Some match types trigger special behavior:

```php
// Royal Rumble automatically restricts to wrestlers
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'royal-rumble',
    'competitors' => ['wrestler', 'wrestler', 'wrestler'] // Tag teams would be converted
])->create();
```

## Extensibility

### Adding New Match Types

To add a new match type:

1. **Create Factory Method**:
```php
public function handicapMatch(): static
{
    return $this->state([
        'name' => 'Handicap Match',
        'slug' => 'handicap',
        'min_competitors' => 3,
        'max_competitors' => 5,
    ]);
}
```

2. **Add Resolution Logic**:
```php
// In MatchFactory::resolveMatchType()
'handicap' => MatchType::factory()->handicapMatch()->create(),
```

3. **Define Business Rules**:
```php
// Add competitor type restrictions if needed
if ($matchType->slug === 'handicap') {
    // Special handicap match logic
}
```

### Custom Match Type Behaviors

For complex match types, implement custom behaviors:

```php
public function configureHandicapMatch(EventMatch $eventMatch, array $config): void
{
    // Custom logic for handicap matches
    // - Ensure uneven teams
    // - Apply special rules
    // - Configure unique winner strategies
}
```

## Testing Match Types

### Unit Testing

```php
test('singles match only allows wrestlers', function () {
    $matchType = MatchType::factory()->singles()->create();
    
    expect($matchType->getAllowedCompetitorTypes())
        ->toBe(['wrestler']);
});

test('battle royal validates competitor count', function () {
    $matchType = MatchType::factory()->battleRoyal()->create();
    
    expect($matchType->validateCompetitorCount(2))->toBeFalse();
    expect($matchType->validateCompetitorCount(5))->toBeTrue();
});
```

### Integration Testing

```php
test('match factory respects match type restrictions', function () {
    $match = MatchFactory::new()->generateFullMatch([
        'match_type' => 'singles',
        'competitors' => ['wrestler', 'wrestler']
    ])->create();
    
    $match->competitors->each(function ($competitor) {
        expect($competitor->competitor_type)->toBe(Wrestler::class);
    });
});
```

## Performance Considerations

### Caching Match Types

For high-volume match generation, consider caching match types:

```php
// Cache frequently used match types
$cachedTypes = Cache::remember('match_types', 3600, function () {
    return MatchType::all()->keyBy('slug');
});
```

### Batch Validation

When generating multiple matches, batch validate match types:

```php
// Validate all match types upfront
$validTypes = MatchType::whereIn('slug', $requestedTypes)->get();
```

The match type system provides the foundation for consistent, rule-compliant match generation while maintaining flexibility for future expansion and customization.