# Quick Reference: Test Groups

## Mandatory Checklist

When adding ANY test, ensure it includes:

- [ ] **Domain Group** (managers, wrestlers, matches, venues, titles, etc.)
- [ ] **Test Type Group** (unit, integration, feature)

## Common Group Combinations

### Table Component Tests
```php
->group('DOMAIN', 'integration', 'livewire', 'tables', 'FUNCTIONALITY')
```
**Examples:**
- `->group('managers', 'integration', 'livewire', 'tables', 'rendering')`
- `->group('wrestlers', 'integration', 'livewire', 'tables', 'search')`
- `->group('venues', 'integration', 'livewire', 'tables', 'filters')`

### Modal Component Tests  
```php
->group('DOMAIN', 'integration', 'livewire', 'modals', 'FUNCTIONALITY')
```
**Examples:**
- `->group('matches', 'integration', 'livewire', 'modals', 'forms')`
- `->group('titles', 'integration', 'livewire', 'modals', 'validation')`

### Action/Business Logic Tests
```php
->group('DOMAIN', 'unit', 'actions', 'FUNCTIONALITY')
```
**Examples:**
- `->group('wrestlers', 'unit', 'actions', 'employment')`
- `->group('managers', 'unit', 'actions', 'injuries')`
- `->group('tagteams', 'unit', 'actions', 'suspensions')`

### Status Display Tests
```php
->group('DOMAIN', 'integration', 'livewire', 'COMPONENT', 'status', 'SPECIFIC')
```
**Examples:**
- `->group('managers', 'integration', 'livewire', 'tables', 'status', 'employment')`
- `->group('wrestlers', 'integration', 'livewire', 'tables', 'status', 'injuries')`
- `->group('venues', 'integration', 'livewire', 'tables', 'status', 'badges')`

## Domain Groups (Pick One)

| Domain | Group Name |
|--------|------------|
| Managers | `managers` |
| Wrestlers | `wrestlers` |
| Matches | `matches` |
| Venues | `venues` |
| Titles/Championships | `titles` |
| Tag Teams | `tagteams` |
| Stables | `stables` |
| Events | `events` |
| Referees | `referees` |
| Users | `users` |

## Test Type Groups (Pick One)

| Type | Group Name | Description |
|------|------------|-------------|
| Unit | `unit` | Testing isolated components/classes |
| Integration | `integration` | Testing component interactions with database |
| Feature | `feature` | Testing complete user workflows |

## Component Groups (If Applicable)

| Component | Group Name |
|-----------|------------|
| Livewire Components | `livewire` |
| Table Components | `tables` |
| Modal Components | `modals` |
| Form Components | `forms` |

## Functionality Groups (As Relevant)

| Functionality | Group Name |
|---------------|------------|
| Rendering/Display | `rendering` |
| Status Management | `status` |
| Employment | `employment` |
| Injuries | `injuries` |
| Suspensions | `suspensions` |
| Search | `search` |
| Filtering | `filters` |
| Actions/Buttons | `actions` |
| Badges/Indicators | `badges` |
| Relationships | `relationships` |
| Validation | `validation` |

## Examples by Test Type

### Unit Test Example
```php
test('can employ wrestler with valid employment date', function () {
    $wrestler = Wrestler::factory()->create();
    $result = EmployAction::run($wrestler, now());
    expect($result)->toBeInstanceOf(WrestlerEmployment::class);
})->group('wrestlers', 'unit', 'actions', 'employment');
```

### Integration Test Example  
```php
test('displays wrestler employment status in table', function () {
    $wrestler = Wrestler::factory()->employed()->create(['name' => 'Test Wrestler']);
    $component = Livewire::test(WrestlersTable::class);
    $component->assertSee('Test Wrestler');
})->group('wrestlers', 'integration', 'livewire', 'tables', 'status', 'employment');
```

### Feature Test Example
```php
test('user can create new match through form modal', function () {
    $user = User::factory()->administrator()->create();
    $response = $this->actingAs($user)->get('/matches');
    // Full workflow test
})->group('matches', 'feature', 'livewire', 'modals', 'forms');
```

## Quick Commands

```bash
# Test specific domain during development
vendor/bin/pest --group=wrestlers

# Test specific functionality across domains  
vendor/bin/pest --group=employment

# Test all table components
vendor/bin/pest --group=tables

# Test domain-specific components
vendor/bin/pest --group=managers,tables
```

---

**Remember:** Every test MUST have at minimum a domain group and test type group. Add more groups based on what the test covers.