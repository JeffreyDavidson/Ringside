# Match Generation System Architecture

This document outlines the comprehensive match generation system that handles all 14 wrestling match types with proper competitor validation, title matches, and complex scenarios.

## System Overview

The match generation system provides a flexible factory-based approach to creating complete wrestling matches with:
- Proper competitor type validation
- Title match scenarios with championship implications  
- Winner/loser assignment based on match decisions
- Support for all 14 match types
- Complex multi-competitor scenarios

## Core Architecture Components

### 1. Match Type System

#### MatchType Model Configuration
```php
// Competitor types stored as JSON arrays
'competitor_types' => ['wrestler', 'tag_team'] // Mixed allowed
'competitor_types' => ['wrestler']            // Wrestler-only
```

#### Match Type Categories

**Wrestler-Only Match Types:**
- **Singles** (`singles`): Only wrestler vs wrestler
- **Royal Rumble** (`royalrumble`): Only individual wrestlers

**Mixed Competitor Match Types:**
- **Tag Team** (`tagteam`): Wrestlers, tag teams, or mixed
- **Triple Threat** (`triple`): Wrestlers, tag teams, or mixed
- **Triangle** (`triangle`): Wrestlers, tag teams, or mixed  
- **Fatal 4 Way** (`fatal4way`): Wrestlers, tag teams, or mixed
- **6/8/10 Man Tag Team** (`6man`, `8man`, `10man`): Wrestlers, tag teams, or mixed
- **Handicap Matches** (`21handicap`, `32handicap`): Wrestlers, tag teams, or mixed
- **Battle Royal** (`battleroyal`): Wrestlers, tag teams, or mixed
- **Tornado Tag Team** (`tornadotag`): Wrestlers, tag teams, or mixed
- **Gauntlet** (`gauntlet`): Wrestlers, tag teams, or mixed

### 2. Winner/Loser Architecture

#### Model Structure
- **EventMatchResult**: Central result linking match to decision
- **EventMatchWinner**: Polymorphic model for all match winners
- **EventMatchLoser**: Polymorphic model for all match losers
- **MatchDecision**: Determines outcome scenarios

#### Multiple Winners/Losers Support
```php
// Tag team match: Both tag team members can be winners
EventMatchWinner::create([
    'event_match_result_id' => $result->id,
    'winner_type' => 'wrestler',
    'winner_id' => $wrestler1->id,
]);

EventMatchWinner::create([
    'event_match_result_id' => $result->id,  
    'winner_type' => 'wrestler',
    'winner_id' => $wrestler2->id,
]);
```

#### No-Outcome Scenarios
Some match decisions result in no winners or losers:
- **Time Limit Draw** (`draw`)
- **No Decision** (`nodecision`)
- **Reverse Decision** (`revdecision`)

### 3. Championship Integration

#### Title Type Validation
```php
// Title model provides type checking
$title->isSinglesTitle()  // Only wrestlers can hold
$title->isTagTeamTitle()  // Only tag teams can hold
```

#### Champion Management
```php
// Current champion queries
$title->currentChampion()     // Current title holder
$title->previousChampion()    // Previous title holder
$title->isVacant()           // No current champion
```

## EventMatch Factory Capabilities

The EventMatch factory provides multiple layers of match generation capabilities, from basic scenarios to complex championship storylines.

### Basic Match Generation

#### Complete Match Creation
```php
// Creates match with competitors, results, and winners/losers
$match = EventMatch::factory()->complete()->create();
```

#### Specific Match Types
```php
// Singles match (wrestler vs wrestler only)
$match = EventMatch::factory()->singles()->create();

// Tag team match (mixed competitors allowed)
$match = EventMatch::factory()->tagTeam()->create();

// Battle royal (multiple competitors)
$match = EventMatch::factory()->battleRoyal(15)->create();
```

### Comprehensive Match Generation

#### Full Configuration Method
```php
// Ultimate flexibility with all options
$match = EventMatch::factory()->generateFullMatch([
    'match_type' => 'singles',
    'competitor_count' => 2,
    'competitor_types' => ['wrestler'],
    'competitors' => [$wrestler1, $wrestler2],
    'titles' => [$singlesTitle],
    'decision_type' => 'pinfall',
    'winner_strategy' => 'first',
    'referees' => 2,
])->create();
```

### Convenience Methods for Common Scenarios

#### Championship Scenarios
```php
// Championship defense with current champion
$match = EventMatch::factory()
    ->championshipDefense($title, $challenger)
    ->create();

// Number one contender match
$match = EventMatch::factory()
    ->numberOneContender($title, 4)
    ->create();

// Title unification match
$match = EventMatch::factory()
    ->titleUnification([$title1, $title2])
    ->create();
```

#### Storyline Matches
```php
// Grudge match between rivals
$match = EventMatch::factory()
    ->grudgeMatch($wrestler1, $wrestler2, 'no_holds_barred')
    ->create();

// Debut match for new talent
$match = EventMatch::factory()
    ->debutMatch($newWrestler, $veteran)
    ->create();

// Retirement match (loser retires)
$match = EventMatch::factory()
    ->retirementMatch($veteran1, $veteran2)
    ->create();

// Faction warfare match
$match = EventMatch::factory()
    ->factionWarfare(4, 'elimination')
    ->create();
```

### Tournament Generation

#### Individual Tournament Matches
```php
// Tournament bracket match
$match = EventMatch::factory()
    ->tournamentMatch(1, [$wrestler1, $wrestler2], 'King of the Ring')
    ->create();
```

#### Complete Tournament Generation
```php
// Generate entire tournament bracket
$competitors = [
    $wrestler1, $wrestler2, $wrestler3, $wrestler4,
    $wrestler5, $wrestler6, $wrestler7, $wrestler8
];

$tournamentMatches = EventMatch::factory()
    ->generateTournament($competitors, 'Championship Tournament');

// Returns array of rounds, each containing match arrays
// Round 1: 4 matches (8 → 4)
// Round 2: 2 matches (4 → 2)  
// Round 3: 1 match (2 → 1)
```

### Special Stipulation Matches

#### Gimmick Matches
```php
// Ladder match for titles
$match = EventMatch::factory()
    ->ladderMatch([$title], 4)
    ->create();

// Steel cage match
$match = EventMatch::factory()
    ->cageMatch($wrestler1, $wrestler2)
    ->create();

// Tables, Ladders & Chairs match
$match = EventMatch::factory()
    ->tlcMatch([$title], 6)
    ->create();

// No disqualification match
$match = EventMatch::factory()
    ->noDqMatch($wrestler1, $wrestler2)
    ->create();

// Iron man match with time limit
$match = EventMatch::factory()
    ->ironManMatch($wrestler1, $wrestler2, 60)
    ->create();
```

### Testing and Development Helpers

#### Quick Setup Methods
```php
// Quick test match with minimal setup
$match = EventMatch::factory()->quickTest('singles')->create();

// Match with all relationships for testing
$match = EventMatch::factory()->withAllRelationships()->create();

// Guaranteed winner for outcome testing
$match = EventMatch::factory()->withGuaranteedWinner()->create();

// No outcome scenario for draw testing
$match = EventMatch::factory()->withNoOutcome()->create();

// Complex showcase match with multiple elements
$match = EventMatch::factory()->showcaseMatch()->create();
```

### Advanced Match Configuration

#### Custom Competitors
```php
// Specify exact competitors with side grouping
$match = EventMatch::factory()
    ->withCompetitors([
        1 => $wrestler1,    // Side 1
        1 => $wrestler2,    // Side 1 (tag team)
        2 => $tagTeam,      // Side 2
    ])
    ->create();
```

#### Match Context
```php
// Associate with specific event and configure details
$match = EventMatch::factory()
    ->forEvent($event)
    ->withMatchNumber(3)
    ->withPreview('Championship main event')
    ->withReferees(2)
    ->create();
```

## Competitor Validation Logic

### Match Type Compatibility
```php
// MatchType helper methods
$matchType->getAllowedCompetitorTypes()  // ['wrestler', 'tag_team'] 
$matchType->allowsWrestlers()           // true/false
$matchType->allowsTagTeams()            // true/false
$matchType->allowsCompetitorType('wrestler') // true/false
```

### Side-Based Grouping
Competitors are grouped by `side_number` for team-based matches:
- **Side 1**: First team/group
- **Side 2**: Second team/group  
- **Side 3+**: Additional sides for multi-way matches

### Competitor Generation Strategy
```php
// Generate competitors based on match type rules
$allowedTypes = $matchType->getAllowedCompetitorTypes();

for ($i = 0; $i < $competitorCount; $i++) {
    $competitorType = fake()->randomElement($allowedTypes);
    
    $competitor = $competitorType === 'wrestler' 
        ? Wrestler::factory()->create()
        : TagTeam::factory()->create();
}
```

## Match Result Generation

### Winner/Loser Assignment Logic

#### Standard Matches
- Random selection of winners and losers from competitors
- Ensures at least one winner (unless no-outcome decision)
- Remaining competitors become losers

#### Battle Royal Logic  
- Single winner selected randomly
- All other competitors become losers
- Simulates elimination-style format

#### No-Outcome Matches
- Check `MatchDecision::hasNoOutcome()` 
- Skip winner/loser creation entirely
- Match result exists but no winners or losers

### Championship Implications
```php
// When match involves titles
if ($match->titles->isNotEmpty()) {
    // Update championship records based on winners
    // Create new TitleChampionship for winner
    // End current championship for loser
}
```

## Match Validation and Analysis Helpers

### Validation Methods

#### withValidation()
Create matches with built-in validation rules:
```php
// Create match with custom validation rules
$match = EventMatch::factory()
    ->complete()
    ->withValidation([
        'min_competitors' => 2,
        'max_competitors' => 10,
        'require_result' => true,
        'require_decision' => true,
    ])
    ->create();
```

#### forBusinessRuleTesting()
Generate matches designed to test specific business rules:
```php
// Test booking rules
$bookingMatch = EventMatch::factory()->forBusinessRuleTesting('booking')->create();

// Test championship rules
$titleMatch = EventMatch::factory()->forBusinessRuleTesting('championship')->create();

// Test stipulation rules
$stipulationMatch = EventMatch::factory()->forBusinessRuleTesting('stipulation')->create();

// Test multi-competitor rules
$multiMatch = EventMatch::factory()->forBusinessRuleTesting('multi_competitor')->create();
```

### Edge Case Testing

#### withEdgeCaseScenario()
Create matches that test boundary conditions:
```php
// Minimum viable match
$minimal = EventMatch::factory()->withEdgeCaseScenario('minimum')->create();

// Maximum competitor count
$maximum = EventMatch::factory()->withEdgeCaseScenario('maximum')->create();

// No outcome scenario
$draw = EventMatch::factory()->withEdgeCaseScenario('no_outcome')->create();

// Multiple titles scenario
$multiTitle = EventMatch::factory()->withEdgeCaseScenario('multiple_titles')->create();
```

### Conflict Testing

#### withConflictScenario()
Generate matches with deliberate conflicts for error handling tests:
```php
// Test competitor type mismatches
$typeConflict = EventMatch::factory()->withConflictScenario('competitor_type_mismatch')->create();

// Test title type mismatches
$titleConflict = EventMatch::factory()->withConflictScenario('title_mismatch')->create();

// Test date conflicts
$dateConflict = EventMatch::factory()->withConflictScenario('date_conflict')->create();
```

### Performance Testing

#### forPerformanceAnalysis()
Create matches optimized for performance metric analysis:
```php
$performanceMatch = EventMatch::factory()->forPerformanceAnalysis()->create();
// Includes metadata for tracking and analysis
```

#### optimizedForTesting()
Create matches with optimized generation for test performance:
```php
// Fast generation with minimal relationships
$fastMatch = EventMatch::factory()->optimizedForTesting([
    'skip_relationships' => false,
    'minimal_data' => true,
    'fast_generation' => true,
])->create();
```

## Planned Enhancements

### 1. Match Generator Strategy Interface
```php
interface MatchGeneratorStrategy 
{
    public function generateMatch(MatchGenerationRequest $request): EventMatch;
    public function supportsMatchType(MatchType $matchType): bool;
    public function validateCompetitors(array $competitors): bool;
}
```

### 2. Advanced Validation Patterns
```php
// Enhanced validation with custom rules
$match = EventMatch::factory()->generateFullMatch([
    'match_type' => 'singles',
    'titles' => [$singlesTitle],
    'competitors' => ['wrestler', 'wrestler'],
    'decision_type' => 'pinfall',
    'special_stipulations' => ['no_dq', 'falls_count_anywhere'],
]);
```

### 3. Advanced Scenarios
- **Tournament Brackets**: Generate connected tournament matches
- **Multi-Night Events**: Matches spanning multiple events
- **Faction Warfare**: Stable vs stable scenarios  
- **Title Unification**: Multiple titles in single match
- **Special Stipulations**: Cage matches, ladder matches, etc.

## Testing Strategy

### Factory Test Coverage
- All 14 match types generation
- Title match scenarios with validation
- No-outcome match handling
- Competitor type validation
- Side-based grouping logic

### Integration Test Scenarios  
- Complete match workflows
- Championship timeline validation
- Complex multi-title scenarios
- Tournament progression logic

## Technical Considerations

### Performance Optimization
- Batch competitor creation for large matches
- Efficient relationship loading for complex matches
- Caching for match type validation rules

### Data Integrity
- Foreign key constraints on all relationships
- Validation at model and business logic levels
- Consistent timestamp handling across models

### Extensibility
- Interface-based design for new match types
- Plugin architecture for special stipulations
- Configurable competitor validation rules

## Related Documentation
- [Business Rules](business-rules.md#match-system-architecture)
- [Factory Testing Guidelines](../testing/factory-testing.md)
- [Domain Structure](domain-structure.md)