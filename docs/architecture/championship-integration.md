# Championship Integration

## Overview

The championship integration system in Ringside seamlessly connects title management with match generation, enabling realistic championship scenarios including title defenses, unification matches, and tournament finals. The system maintains championship lineage and validates title requirements.

## Core Components

### Title Management

#### Title Model Structure

```php
// Key Title attributes
- name: Championship name (e.g., "World Heavyweight Championship")
- slug: URL-friendly identifier  
- type: TitleType enum (singles, tag-team, etc.)
- status: Active, inactive, retired
- activated_at: When title became active
- deactivated_at: When title was deactivated
```

#### TitleChampionship Model

```php
// Championship tracking
- title_id: Foreign key to Title
- champion_type: Wrestler or TagTeam class
- champion_id: Foreign key to champion
- won_at: Championship victory date
- lost_at: Championship loss date (nullable for current champion)
```

### Factory Integration Points

The MatchFactory integrates with the title system at multiple levels:

1. **Automatic Champion Detection**: Identifies current champions
2. **Title Match Generation**: Creates matches with championship implications
3. **Championship Validation**: Ensures title requirements are met
4. **Lineage Management**: Maintains championship history

## Championship Scenarios

### Title Defense Match

Basic championship defense where the current champion defends against a challenger:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'titles' => [$worldTitle],
    'winner_strategy' => 'first'  // Champion retains
])->create();
```

**System Behavior:**
1. Identifies current champion of `$worldTitle`
2. Adds champion as first competitor
3. Generates challenger as second competitor
4. Creates match with title implications
5. Updates championship record based on result

### Title Change Match

Championship match where the title changes hands:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles', 
    'titles' => [$worldTitle],
    'competitors' => [$currentChampion, $challenger],
    'winner_strategy' => 'last',      // Challenger wins
    'decision_type' => 'pinfall'
])->create();
```

**System Behavior:**
1. Uses specified champion and challenger
2. Challenger wins based on strategy
3. Updates `TitleChampionship` record:
   - Sets `lost_at` on current championship
   - Creates new championship record for challenger

### Multi-Title Match

Matches involving multiple championships:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'titles' => [$worldTitle, $intercontinentalTitle],
    'winner_strategy' => 'random'
])->create();
```

**System Behavior:**
1. Identifies champions of both titles
2. Adds both champions as competitors if different
3. If same wrestler holds both titles, adds challenger
4. Winner takes all titles in the match

### Unification Match

Special scenario where multiple titles are unified:

```php
// Both wrestlers are champions of different titles
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'titles' => [$brandATitle, $brandBTitle],
    'competitors' => [$championA, $championB],
    'winner_strategy' => 'first'  // Champion A unifies titles
])->create();
```

### Tag Team Championship

Tag team title scenarios with team-specific validation:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'tagteam',
    'titles' => [$tagTeamTitle],
    'winner_strategy' => 'last'   // Challengers win titles
])->create();
```

**Validation:**
- Ensures tag team title only involves tag teams
- Validates team composition and eligibility
- Maintains tag team championship lineage

## Implementation Details

### Champion Resolution

The factory automatically identifies current champions:

```php
private function getExistingChampions(array $config): array
{
    $champions = [];
    
    if (isset($config['titles'])) {
        foreach ($config['titles'] as $title) {
            $championship = TitleChampionship::where('title_id', $title->id)
                ->whereNull('lost_at')  // Current champion
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
```

### Title Attachment

Titles are attached to matches through pivot relationships:

```php
private function attachTitles(EventMatch $eventMatch, array $titles): void
{
    $titleIds = array_map(fn($title) => $title->id, $titles);
    $eventMatch->titles()->attach($titleIds);
}
```

### Championship Validation

The system validates championship requirements:

```php
public function validateTitleMatch(Title $title, array $competitors): bool
{
    // Validate title type matches competitor types
    if ($title->type->value === 'tag-team') {
        foreach ($competitors as $competitor) {
            if (!$competitor instanceof TagTeam) {
                return false;
            }
        }
    }
    
    // Validate champion participation
    $currentChampion = $title->currentChampion();
    if ($currentChampion && !in_array($currentChampion, $competitors)) {
        return false; // Champion must participate in title match
    }
    
    return true;
}
```

## Championship Logic

### Title Defense Scenarios

#### Successful Defense
```php
// Champion retains title
$match = MatchFactory::new()->generateFullMatch([
    'titles' => [$title],
    'winner_strategy' => 'first'  // Assumes champion is first competitor
])->create();

// Championship record remains unchanged (lost_at stays null)
```

#### Title Change
```php
// Title changes hands
$match = MatchFactory::new()->generateFullMatch([
    'titles' => [$title],
    'winner_strategy' => 'last'   // Challenger wins
])->create();

// Updates championship lineage:
// 1. Current championship: lost_at = match date
// 2. New championship: created for winner
```

### Vacant Title Scenarios

When no current champion exists:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'titles' => [$vacantTitle],
    'competitors' => [$wrestler1, $wrestler2],
    'winner_strategy' => 'random'
])->create();

// Creates new championship record for winner
TitleChampionship::create([
    'title_id' => $vacantTitle->id,
    'champion_type' => get_class($winner),
    'champion_id' => $winner->id,
    'won_at' => $match->event->date
]);
```

## Advanced Championship Features

### Tournament Finals

Championship tournaments with bracket progression:

```php
// Tournament final for vacant title
$finalMatch = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'titles' => [$vacantTitle],
    'competitors' => [$finalist1, $finalist2],
    'winner_strategy' => 'random',
    'decision_type' => 'pinfall'
])->create();
```

### Multi-Way Title Matches

Championships in multi-competitor matches:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'fatal-4-way',
    'titles' => [$title],
    'competitor_count' => 4,
    'winner_strategy' => 'single'  // Only one winner takes title
])->create();
```

### Championship Stipulations

Special match stipulations affecting titles:

```php
// Title can change hands on DQ/countout
$match = MatchFactory::new()->generateFullMatch([
    'titles' => [$title],
    'decision_type' => 'disqualification',
    'winner_strategy' => 'last',  // Challenger wins via DQ
    'special_stipulations' => ['title_changes_on_any_finish']
])->create();
```

## Testing Championship Integration

### Unit Tests

```php
test('title defense maintains championship when champion wins', function () {
    $title = Title::factory()->create();
    $champion = Wrestler::factory()->create();
    
    // Create championship
    TitleChampionship::factory()->create([
        'title_id' => $title->id,
        'champion_type' => get_class($champion),
        'champion_id' => $champion->id,
        'won_at' => now()->subMonths(3)
    ]);
    
    $match = MatchFactory::new()->generateFullMatch([
        'titles' => [$title],
        'winner_strategy' => 'first'  // Champion retains
    ])->create();
    
    $championship = $title->currentChampionship();
    expect($championship->lost_at)->toBeNull();
    expect($championship->champion_id)->toBe($champion->id);
});

test('title changes hands when challenger wins', function () {
    $title = Title::factory()->create();
    $champion = Wrestler::factory()->create();
    $challenger = Wrestler::factory()->create();
    
    // Create existing championship
    $oldChampionship = TitleChampionship::factory()->create([
        'title_id' => $title->id,
        'champion_type' => get_class($champion),
        'champion_id' => $champion->id
    ]);
    
    $match = MatchFactory::new()->generateFullMatch([
        'titles' => [$title],
        'competitors' => [$champion, $challenger],
        'winner_strategy' => 'last'  // Challenger wins
    ])->create();
    
    // Old championship should be ended
    $oldChampionship->refresh();
    expect($oldChampionship->lost_at)->not->toBeNull();
    
    // New championship should exist
    $newChampionship = $title->currentChampionship();
    expect($newChampionship->champion_id)->toBe($challenger->id);
});
```

### Integration Tests

```php
test('multi title unification match', function () {
    $title1 = Title::factory()->create();
    $title2 = Title::factory()->create();
    $champion1 = Wrestler::factory()->create();
    $champion2 = Wrestler::factory()->create();
    
    // Create separate championships
    TitleChampionship::factory()->create([
        'title_id' => $title1->id,
        'champion_id' => $champion1->id
    ]);
    
    TitleChampionship::factory()->create([
        'title_id' => $title2->id, 
        'champion_id' => $champion2->id
    ]);
    
    $match = MatchFactory::new()->generateFullMatch([
        'titles' => [$title1, $title2],
        'competitors' => [$champion1, $champion2],
        'winner_strategy' => 'first'  // Champion 1 unifies
    ])->create();
    
    expect($title1->currentChampion()->id)->toBe($champion1->id);
    expect($title2->currentChampion()->id)->toBe($champion1->id);
});
```

## Performance Considerations

### Championship Queries

Optimize championship lookups:

```php
// Eager load current championships
$titles = Title::with(['currentChampionship.champion'])->get();

// Cache frequent championship queries
$currentChampions = Cache::remember('current_champions', 3600, function () {
    return TitleChampionship::whereNull('lost_at')->with('champion')->get();
});
```

### Batch Championship Updates

For multiple title matches:

```php
// Batch update championships
DB::transaction(function () use ($titleChanges) {
    foreach ($titleChanges as $change) {
        TitleChampionship::where('title_id', $change['title_id'])
            ->whereNull('lost_at')
            ->update(['lost_at' => $change['lost_at']]);
            
        TitleChampionship::create($change['new_championship']);
    }
});
```

The championship integration system provides comprehensive title management capabilities while maintaining data integrity and supporting complex championship scenarios from simple defenses to multi-title unification matches.