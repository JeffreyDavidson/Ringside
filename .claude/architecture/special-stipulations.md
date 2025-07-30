# Special Stipulations

## Overview

Special stipulations add unique rules, conditions, and consequences to wrestling matches beyond standard competition. The match generation system is designed to support various stipulation types while maintaining data integrity and business rule compliance.

## Stipulation Categories

### Match Environment Stipulations

#### No Disqualification
Removes disqualification rules, allowing extreme tactics:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'decision_type' => 'pinfall', // DQ not possible
    'stipulations' => ['no_disqualification'],
    'special_rules' => [
        'weapons_allowed' => true,
        'outside_interference' => true
    ]
])->create();
```

#### No Count Out
Eliminates count-out losses:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'stipulations' => ['no_count_out'],
    'decision_type' => 'pinfall' // Count out not possible
])->create();
```

#### Falls Count Anywhere
Pinfall attempts valid throughout venue:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'stipulations' => ['falls_count_anywhere'],
    'special_rules' => [
        'venue_restrictions' => false,
        'referee_mobility' => 'follows_action'
    ]
])->create();
```

### Winning Condition Stipulations

#### Last Man Standing
Victory by incapacitating opponent for 10-count:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'decision_type' => 'last_man_standing',
    'stipulations' => ['last_man_standing'],
    'victory_conditions' => [
        'ten_count_required' => true,
        'pinfall_disabled' => true,
        'submission_disabled' => true
    ]
])->create();
```

#### Submission Match
Victory only by submission:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'decision_type' => 'submission',
    'stipulations' => ['submission_only'],
    'victory_conditions' => [
        'pinfall_disabled' => true,
        'count_out_disabled' => true,
        'dq_disabled' => true
    ]
])->create();
```

#### Iron Man Match
Most falls in time limit wins:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'stipulations' => ['iron_man'],
    'time_limit' => 60, // minutes
    'victory_conditions' => [
        'multiple_falls' => true,
        'time_limit_required' => true
    ],
    'special_tracking' => [
        'fall_count' => true,
        'time_remaining' => true
    ]
])->create();
```

### Career Stipulations

#### Loser Leaves Company
Losing competitor must leave promotion:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'stipulations' => ['loser_leaves_company'],
    'consequences' => [
        'loser_action' => 'terminate_contract',
        'effective_date' => 'immediate'
    ]
])->create();
```

#### Hair vs Hair
Losing competitor gets head shaved:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'stipulations' => ['hair_vs_hair'],
    'consequences' => [
        'loser_action' => 'hair_removal',
        'stipulation_enforcement' => 'post_match'
    ]
])->create();
```

#### Career vs Career
Both competitors' careers on the line:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'stipulations' => ['career_vs_career'],
    'consequences' => [
        'loser_action' => 'retirement',
        'stakes' => 'both_careers'
    ]
])->create();
```

### Championship Stipulations

#### Title Changes on Any Finish
Championship changes hands even on DQ/countout:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'titles' => [$championship],
    'stipulations' => ['title_changes_on_any_finish'],
    'decision_type' => 'disqualification',
    'winner_strategy' => 'last', // Challenger can win title via DQ
    'championship_rules' => [
        'dq_title_change' => true,
        'countout_title_change' => true
    ]
])->create();
```

#### Ladder Match
Title suspended above ring, must climb to retrieve:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'titles' => [$championship],
    'stipulations' => ['ladder_match'],
    'victory_conditions' => [
        'retrieve_object' => 'championship',
        'pinfall_disabled' => true,
        'submission_disabled' => true
    ],
    'equipment' => ['ladders', 'suspended_title']
])->create();
```

### Gimmick Stipulations

#### Cage Match
Match inside steel cage structure:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'stipulations' => ['steel_cage'],
    'victory_conditions' => [
        'escape_cage' => true,
        'pinfall' => true,
        'submission' => true
    ],
    'environment' => [
        'cage_type' => 'steel',
        'escape_methods' => ['door', 'over_top']
    ]
])->create();
```

#### Handicap Match
Uneven competitor advantage:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'handicap',
    'competitors' => [$single_wrestler, $wrestler2, $wrestler3], // 2-on-1
    'stipulations' => ['handicap_match'],
    'match_rules' => [
        'simultaneous_legal' => false,
        'tag_rules_apply' => true
    ]
])->create();
```

#### Tables Match
Victory by putting opponent through table:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'stipulations' => ['tables_match'],
    'victory_conditions' => [
        'break_table_with_opponent' => true,
        'pinfall_disabled' => true,
        'submission_disabled' => true
    ],
    'equipment' => ['tables']
])->create();
```

## Stipulation Implementation

### Configuration Structure

```php
// Complete stipulation configuration
$stipulationConfig = [
    'match_type' => 'singles',
    'stipulations' => [
        'no_disqualification',
        'falls_count_anywhere',
        'title_changes_on_any_finish'
    ],
    'victory_conditions' => [
        'pinfall' => true,
        'submission' => true,
        'count_out' => false,
        'disqualification' => false
    ],
    'consequences' => [
        'loser_action' => null,
        'winner_reward' => null
    ],
    'special_rules' => [
        'weapons_allowed' => true,
        'outside_interference' => true,
        'referee_discretion' => 'limited'
    ]
];
```

### Factory Integration

```php
private function applyStipulations(EventMatch $eventMatch, array $config): void
{
    if (!isset($config['stipulations'])) {
        return;
    }
    
    foreach ($config['stipulations'] as $stipulation) {
        $this->processStipulation($eventMatch, $stipulation, $config);
    }
}

private function processStipulation(EventMatch $eventMatch, string $stipulation, array $config): void
{
    match ($stipulation) {
        'no_disqualification' => $this->applyNoDQRules($eventMatch, $config),
        'falls_count_anywhere' => $this->applyFallsCountAnywhereRules($eventMatch, $config),
        'ladder_match' => $this->applyLadderMatchRules($eventMatch, $config),
        'cage_match' => $this->applyCageMatchRules($eventMatch, $config),
        default => $this->applyGenericStipulation($eventMatch, $stipulation, $config)
    };
}
```

### Decision Type Modifications

Stipulations can override normal decision type restrictions:

```php
private function resolveDecisionWithStipulations(array $config): MatchDecision
{
    $stipulations = $config['stipulations'] ?? [];
    
    // Certain stipulations force specific decision types
    if (in_array('submission_only', $stipulations)) {
        return $this->resolveMatchDecision('submission');
    }
    
    if (in_array('last_man_standing', $stipulations)) {
        return $this->resolveMatchDecision('last_man_standing');
    }
    
    // Filter available decision types based on stipulations
    $availableDecisions = $this->getAvailableDecisions($stipulations);
    $requestedDecision = $config['decision_type'] ?? 'pinfall';
    
    return in_array($requestedDecision, $availableDecisions)
        ? $this->resolveMatchDecision($requestedDecision)
        : $this->resolveMatchDecision($availableDecisions[0]);
}
```

## Advanced Stipulation Scenarios

### Multi-Stipulation Matches

Combining multiple stipulations:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'titles' => [$championship],
    'stipulations' => [
        'no_disqualification',
        'no_count_out', 
        'falls_count_anywhere',
        'title_changes_on_any_finish'
    ],
    'decision_type' => 'pinfall'
])->create();
```

### Conditional Stipulations

Stipulations that activate based on conditions:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'stipulations' => ['escalating_violence'],
    'conditional_rules' => [
        'after_10_minutes' => ['no_disqualification'],
        'after_20_minutes' => ['falls_count_anywhere'],
        'overtime' => ['sudden_death']
    ]
])->create();
```

### Tournament Stipulations

Special rules for tournament matches:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'tournament_id' => $tournament->id,
    'stipulations' => ['tournament_rules'],
    'special_rules' => [
        'time_limit' => 15, // Tournament time limits
        'no_interference' => true,
        'mandatory_winner' => true // No draws allowed
    ]
])->create();
```

## Stipulation Tracking

### Match Stipulation Records

```php
class MatchStipulation extends Model
{
    protected $fillable = [
        'match_id',
        'stipulation_type',
        'stipulation_details', // JSON field
        'enforcement_status',  // pending, active, completed
        'outcome_notes'
    ];
    
    public function match(): BelongsTo
    {
        return $this->belongsTo(EventMatch::class);
    }
}
```

### Stipulation Outcomes

Track stipulation-specific results:

```php
private function recordStipulationOutcomes(EventMatch $match, array $stipulations): void
{
    foreach ($stipulations as $stipulation) {
        MatchStipulation::create([
            'match_id' => $match->id,
            'stipulation_type' => $stipulation,
            'enforcement_status' => 'completed',
            'outcome_notes' => $this->generateStipulationOutcome($match, $stipulation)
        ]);
    }
}
```

## Testing Stipulation Matches

### Unit Tests

```php
test('no disqualification stipulation prevents DQ finish', function () {
    $match = MatchFactory::new()->generateFullMatch([
        'match_type' => 'singles',
        'stipulations' => ['no_disqualification'],
        'decision_type' => 'disqualification' // Should be overridden
    ])->create();
    
    expect($match->result->decision->slug)->not->toBe('disqualification');
});

test('title changes on any finish allows DQ title change', function () {
    $title = Title::factory()->create();
    $champion = Wrestler::factory()->create();
    
    TitleChampionship::factory()->create([
        'title_id' => $title->id,
        'champion_id' => $champion->id
    ]);
    
    $match = MatchFactory::new()->generateFullMatch([
        'titles' => [$title],
        'stipulations' => ['title_changes_on_any_finish'],
        'decision_type' => 'disqualification',
        'winner_strategy' => 'last' // Challenger wins
    ])->create();
    
    expect($title->currentChampion()->id)->not->toBe($champion->id);
});
```

### Integration Tests

```php
test('multiple stipulations work together', function () {
    $match = MatchFactory::new()->generateFullMatch([
        'match_type' => 'singles',
        'stipulations' => [
            'no_disqualification',
            'falls_count_anywhere',
            'no_count_out'
        ]
    ])->create();
    
    $allowedDecisions = ['pinfall', 'submission', 'draw', 'nodecision'];
    expect($match->result->decision->slug)->toBeIn($allowedDecisions);
});
```

## Future Stipulation Extensions

### Custom Stipulations

Framework for promotion-specific stipulations:

```php
public function addCustomStipulation(string $name, array $rules): void
{
    $this->customStipulations[$name] = $rules;
}

// Usage
$factory->addCustomStipulation('company_specific_rule', [
    'victory_conditions' => ['custom_condition'],
    'special_rules' => ['unique_requirement']
]);
```

### Interactive Stipulations

Stipulations requiring mid-match decisions:

```php
// Future: Interactive stipulation resolution
$match = MatchFactory::new()->generateFullMatch([
    'stipulations' => ['choose_your_weapon'],
    'interactive_points' => [
        'weapon_selection' => 'user_choice',
        'stipulation_escalation' => 'random'
    ]
])->create();
```

### Seasonal Stipulations

Event-specific stipulation variants:

```php
$match = MatchFactory::new()->generateFullMatch([
    'match_type' => 'singles',
    'stipulations' => ['halloween_horror'],
    'seasonal_modifiers' => [
        'theme' => 'horror',
        'special_effects' => true,
        'costume_requirements' => true
    ]
])->create();
```

The special stipulations system provides extensive customization options for creating unique match scenarios while maintaining the integrity of the core match generation framework. Stipulations can be combined, customized, and extended to support any wrestling promotion's creative requirements.