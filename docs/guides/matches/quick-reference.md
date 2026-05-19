# Dynamic Match UI Quick Reference

## For Developers

### Key Files Modified
- `app/Livewire/Matches/Forms/CreateEditForm.php` - Form logic and validation
- `app/Livewire/Matches/Modals/FormModal.php` - Modal orchestration  
- `resources/views/livewire/matches/modals/form-modal.blade.php` - Dynamic UI template

### Key Methods Added
```php
// Form class
public function updatedFormMatchTypeId($value): void
protected function initializeCompetitorStructure(int $matchTypeId): void
public function getMatchTypeName(): string

// Modal class  
public function getWrestlers(): Collection
public function getTagTeams(): Collection
```

### Blade Template Pattern
```blade
@if ($this->form->matchTypeId)
    @if (str_contains($this->matchTypeName, 'singles'))
        {{-- Singles layout --}}
    @elseif (str_contains($this->matchTypeName, 'tag'))
        {{-- Tag team layout --}}
    @endif
@else
    <p>Select a match type to configure competitors</p>
@endif
```

### Testing Pattern
```php
it('adapts UI when match type changes', function () {
    $component = Livewire::test(FormModal::class, ['eventId' => $event->id])
        ->call('openModal')
        ->set('form.matchTypeId', $matchTypeId)
        ->assertSee('expected UI element');
});
```

## For Users

### Quick Steps
1. Select match type → Form adapts automatically
2. Choose competitors from tailored interface  
3. Complete match details
4. Save match

### Match Type Layouts
- **Singles**: 2 side-by-side competitor fields
- **Tag Team**: Team A/B with wrestler + tag team options
- **Triple Threat**: 3 competitor grid
- **Fatal Four-Way**: 2x2 competitor grid  
- **Battle Royal**: Multi-select for all participants

### Pro Tips
- Select match type first to unlock competitor fields
- Form clears incompatible selections automatically
- All selections validated in real-time
- Use Tab key for fast navigation between fields