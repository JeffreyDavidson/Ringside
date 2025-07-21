# Dynamic Match UI Architecture

## Technical Overview

The Dynamic Match Competitor UI System represents a significant architectural enhancement to the Matches FormModal component, implementing real-time form adaptation based on match type selection. This document provides technical details for developers working with or extending the system.

## Architecture Components

### Core Classes

#### `App\Livewire\Matches\Forms\CreateEditForm`
- **Purpose**: Backend form logic and validation
- **Key Methods**:
  - `updatedFormMatchTypeId()`: Handles match type changes
  - `initializeCompetitorStructure()`: Sets up competitor data structure
  - `getMatchTypeName()`: Computed property for match type identification

#### `App\Livewire\Matches\Modals\FormModal`
- **Purpose**: Modal component orchestration
- **Key Methods**:
  - `getWrestlers()`: Provides wrestler options for dropdowns
  - `getTagTeams()`: Provides tag team options for dropdowns

#### Blade Template: `resources/views/livewire/matches/modals/form-modal.blade.php`
- **Purpose**: Dynamic UI rendering
- **Key Features**: Conditional sections based on match type

## Implementation Details

### Real-Time Updates

```php
// Form property with live updating
public function updatedFormMatchTypeId($value): void
{
    if (!$value) return;
    
    // Clear incompatible competitor data
    $this->form->competitors = [];
    
    // Initialize structure for new match type
    $this->initializeCompetitorStructure($value);
}
```

**Wire Model Configuration:**
```blade
<x-form.inputs.select 
    label="Match Type" 
    wire:model.live="form.matchTypeId"
    :options="$this->getMatchTypes" />
```

### Competitor Structure Initialization

```php
protected function initializeCompetitorStructure(int $matchTypeId): void
{
    $matchType = MatchType::find($matchTypeId);
    $matchTypeName = strtolower($matchType->name ?? '');
    
    // Dynamic structure based on match type
    if (str_contains($matchTypeName, 'singles')) {
        $this->competitors = [
            0 => ['wrestlers' => []],
            1 => ['wrestlers' => []]
        ];
    } elseif (str_contains($matchTypeName, 'triple')) {
        $this->competitors = [
            0 => ['wrestlers' => []],
            1 => ['wrestlers' => []],
            2 => ['wrestlers' => []]
        ];
    }
    // Additional match types...
}
```

### Blade Template Structure

```blade
@if ($this->form->matchTypeId)
    @if (str_contains($this->matchTypeName, 'singles'))
        {{-- Singles Match Layout --}}
        <div class="grid grid-cols-2 gap-4">
            <x-form.inputs.select 
                label="Competitor 1" 
                wire:model="form.competitors.0.wrestlers.0" 
                :options="$this->getWrestlers" />
            <x-form.inputs.select 
                label="Competitor 2" 
                wire:model="form.competitors.1.wrestlers.0" 
                :options="$this->getWrestlers" />
        </div>
    @elseif (str_contains($this->matchTypeName, 'tag'))
        {{-- Tag Team Match Layout --}}
        <div class="grid grid-cols-2 gap-6">
            <div class="space-y-4">
                <h4 class="font-medium">Team A</h4>
                <x-form.inputs.select 
                    label="Wrestlers" 
                    wire:model="form.competitors.0.wrestlers" 
                    multiple 
                    :options="$this->getWrestlers" />
                <x-form.inputs.select 
                    label="Tag Teams" 
                    wire:model="form.competitors.0.tag_teams.0" 
                    :options="$this->getTagTeams" />
            </div>
            {{-- Team B similar structure --}}
        </div>
    @endif
@else
    <p class="text-gray-500">Select a match type to configure competitors</p>
@endif
```

## Data Flow Architecture

### 1. User Interaction
```
User selects match type → wire:model.live triggers → updatedFormMatchTypeId() called
```

### 2. Backend Processing
```php
updatedFormMatchTypeId($matchTypeId) {
    // Clear existing competitor data
    $this->competitors = [];
    
    // Initialize new structure
    $this->initializeCompetitorStructure($matchTypeId);
    
    // Livewire automatically updates frontend
}
```

### 3. Frontend Update
```
Blade template re-renders → Conditional sections evaluate → New UI displayed
```

## Validation Architecture

### Dynamic Validation Rules

```php
protected function rules(): array
{
    $rules = [
        'matchTypeId' => ['required', 'exists:match_types,id'],
        // Base rules...
    ];
    
    // Add match-type-specific validation
    if ($this->matchTypeId) {
        $matchType = MatchType::find($this->matchTypeId);
        $rules = array_merge($rules, $this->getMatchTypeSpecificRules($matchType));
    }
    
    return $rules;
}

private function getMatchTypeSpecificRules(MatchType $matchType): array
{
    $matchTypeName = strtolower($matchType->name);
    
    if (str_contains($matchTypeName, 'singles')) {
        return [
            'competitors.0.wrestlers.0' => ['required', 'exists:wrestlers,id'],
            'competitors.1.wrestlers.0' => ['required', 'exists:wrestlers,id'],
        ];
    }
    // Additional match type rules...
}
```

### Competitor Validation

```php
// Custom validation rule for competitor uniqueness
'competitors.*.*' => [
    'required_if:form.matchTypeId,!=,null',
    new CompetitorsNotDuplicated(),
    new CorrectNumberOfSides($this->matchTypeId)
]
```

## Performance Considerations

### Optimizations Implemented

1. **Lazy Loading**: Wrestler and tag team data loaded only when needed
2. **Conditional Rendering**: Only relevant UI sections are rendered
3. **Efficient Updates**: Livewire updates only changed components
4. **Computed Properties**: Match type names cached for template use

### Database Queries

```php
// Efficient loading with relationships
public function getWrestlers(): Collection
{
    return Cache::remember('bookable_wrestlers', 300, function () {
        return Wrestler::bookable()
            ->select(['id', 'first_name', 'last_name'])
            ->orderBy('last_name')
            ->get()
            ->mapWithKeys(fn($wrestler) => [$wrestler->id => $wrestler->full_name]);
    });
}
```

## Testing Strategy

### Integration Tests

```php
// Test dynamic UI updates
it('updates competitor fields when match type changes', function () {
    $singlesType = MatchType::factory()->create(['name' => 'Singles']);
    $tagTeamType = MatchType::factory()->create(['name' => 'Tag Team']);
    
    $component = Livewire::test(FormModal::class, ['eventId' => $this->event->id])
        ->call('openModal')
        ->set('form.matchTypeId', $singlesType->id)
        ->assertSee('Competitor 1')
        ->assertSee('Competitor 2')
        ->set('form.matchTypeId', $tagTeamType->id)
        ->assertSee('Team A')
        ->assertSee('Team B');
});
```

### Unit Tests

```php
// Test competitor structure initialization
it('initializes correct competitor structure for match types', function () {
    $form = new CreateEditForm();
    $singlesType = MatchType::factory()->create(['name' => 'Singles']);
    
    $form->initializeCompetitorStructure($singlesType->id);
    
    expect($form->competitors)->toHaveCount(2);
    expect($form->competitors[0])->toHaveKey('wrestlers');
});
```

## Extension Guidelines

### Adding New Match Types

1. **Database Setup**: Add match type to `match_types` table
2. **Form Logic**: Update `initializeCompetitorStructure()` method
3. **Template**: Add conditional section in Blade template
4. **Validation**: Implement match-type-specific rules
5. **Tests**: Add comprehensive test coverage

### Custom Match Configurations

```php
// Example: Custom elimination match type
if (str_contains($matchTypeName, 'elimination')) {
    $this->competitors = collect(range(0, $this->participantCount - 1))
        ->mapWithKeys(fn($i) => [$i => ['wrestlers' => []]]);
}
```

## Security Considerations

### Input Validation
- All competitor selections validated against database
- Match type existence verified before processing
- Authorization checks for all participants

### Data Integrity
- Competitor uniqueness enforced
- Match type compatibility validated
- Proper sanitization of all inputs

## Monitoring and Debugging

### Logging
```php
Log::info('Match type changed', [
    'match_type_id' => $matchTypeId,
    'previous_competitors' => $this->competitors,
    'user_id' => auth()->id()
]);
```

### Debug Information
- Component state tracking via Livewire debug tools
- Form validation error logging
- Performance monitoring for UI updates

## Future Architectural Considerations

### Planned Enhancements
1. **Component Composition**: Breaking down into smaller, reusable components
2. **State Management**: Enhanced state persistence across form interactions
3. **API Integration**: Support for external wrestling promotion systems
4. **Real-time Collaboration**: Multiple users editing matches simultaneously

### Scalability
- Caching strategies for large wrestler rosters
- Lazy loading for complex match configurations
- Database optimization for competitor queries

---

*Technical Documentation Version: 2.0*
*Last Updated: July 2025*
*Maintained by: Development Team*