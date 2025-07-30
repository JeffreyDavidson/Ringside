# Pest Group System

The Ringside project uses Pest's group system to organize tests by domain, functionality, and test type for targeted execution and improved development workflow.

## Overview

Groups allow developers to:
- Run tests for specific domains during feature development
- Execute only certain types of tests (integration, unit, rendering)
- Focus on related functionality across multiple domains
- Speed up development by running relevant test subsets

## Available Groups

### Domain Groups

| Group | Description | Example Command |
|-------|-------------|-----------------|
| `managers` | All manager-related tests | `vendor/bin/pest --group=managers` |
| `wrestlers` | All wrestler-related tests | `vendor/bin/pest --group=wrestlers` |
| `matches` | All match-related tests | `vendor/bin/pest --group=matches` |
| `venues` | All venue-related tests | `vendor/bin/pest --group=venues` |
| `titles` | All title/championship tests | `vendor/bin/pest --group=titles` |
| `tagteams` | All tag team-related tests | `vendor/bin/pest --group=tagteams` |
| `stables` | All stable-related tests | `vendor/bin/pest --group=stables` |
| `events` | All event-related tests | `vendor/bin/pest --group=events` |
| `referees` | All referee-related tests | `vendor/bin/pest --group=referees` |
| `users` | All user-related tests | `vendor/bin/pest --group=users` |

### Test Type Groups

| Group | Description | Example Command |
|-------|-------------|-----------------|
| `integration` | Integration tests with real database | `vendor/bin/pest --group=integration` |
| `unit` | Unit tests for isolated components | `vendor/bin/pest --group=unit` |
| `feature` | Feature tests for user workflows | `vendor/bin/pest --group=feature` |

### Component Type Groups

| Group | Description | Example Command |
|-------|-------------|-----------------|
| `livewire` | All Livewire component tests | `vendor/bin/pest --group=livewire` |
| `tables` | All table component tests | `vendor/bin/pest --group=tables` |
| `modals` | All modal component tests | `vendor/bin/pest --group=modals` |
| `forms` | All form component tests | `vendor/bin/pest --group=forms` |

### Functionality Groups

| Group | Description | Example Command |
|-------|-------------|-----------------|
| `rendering` | Tests for component rendering | `vendor/bin/pest --group=rendering` |
| `status` | Tests for status display/management | `vendor/bin/pest --group=status` |
| `search` | Tests for search functionality | `vendor/bin/pest --group=search` |
| `filters` | Tests for filtering functionality | `vendor/bin/pest --group=filters` |
| `badges` | Tests for badge/indicator display | `vendor/bin/pest --group=badges` |
| `actions` | Tests for action buttons/dropdowns | `vendor/bin/pest --group=actions` |
| `relationships` | Tests for model relationships | `vendor/bin/pest --group=relationships` |
| `builders` | Tests for query builders | `vendor/bin/pest --group=builders` |
| `employment` | Tests for employment status | `vendor/bin/pest --group=employment` |
| `injuries` | Tests for injury management | `vendor/bin/pest --group=injuries` |
| `suspensions` | Tests for suspension management | `vendor/bin/pest --group=suspensions` |

## Usage Examples

### Basic Group Execution

```bash
# Run all manager tests
vendor/bin/pest --group=managers

# Run all table tests
vendor/bin/pest --group=tables

# Run all integration tests
vendor/bin/pest --group=integration
```

### Multiple Group Execution

```bash
# Run manager table tests only
vendor/bin/pest --group=managers,tables

# Run all rendering tests across domains
vendor/bin/pest --group=rendering

# Run integration tests for specific domains
vendor/bin/pest --group=managers,wrestlers,integration
```

### Development Workflow Examples

```bash
# Working on wrestler employment features
vendor/bin/pest --group=wrestlers,employment

# Testing status display improvements
vendor/bin/pest --group=status,badges

# Validating search functionality
vendor/bin/pest --group=search,filters

# Testing Livewire table components
vendor/bin/pest --group=livewire,tables
```

## Adding Groups to Tests

### Pest Group Syntax

Use the `->group()` method to add groups to individual tests:

```php
test('renders managers table with complete data relationships', function () {
    // Test implementation
})->group('managers', 'tables', 'rendering', 'integration');
```

### Required Groups for New Tests

When adding new tests, **ALWAYS** include appropriate groups. Every test should have:

1. **Domain group** (managers, wrestlers, matches, etc.)
2. **Test type group** (integration, unit, feature)
3. **Component group** (livewire, tables, modals, etc.) if applicable
4. **Functionality groups** (rendering, status, search, etc.) as relevant

### Example Test with Proper Groups

```php
describe('ManagersTable Component', function () {
    
    test('displays manager employment status correctly', function () {
        $employedManager = Manager::factory()->employed()->create();
        $unemployedManager = Manager::factory()->unemployed()->create();

        $component = Livewire::test(Main::class);

        $component
            ->assertSee('Currently Employed')
            ->assertSee('Currently Unemployed');
    })->group('managers', 'integration', 'livewire', 'tables', 'status', 'employment');

    test('filters managers by employment status', function () {
        // Test implementation
    })->group('managers', 'integration', 'livewire', 'tables', 'filters', 'employment');
    
    test('searches managers by name', function () {
        // Test implementation  
    })->group('managers', 'integration', 'livewire', 'tables', 'search');
});
```

## Group Guidelines

### Mandatory Rules

1. **Every test MUST have groups** - No ungrouped tests allowed
2. **Domain group is required** - Always specify which domain the test covers
3. **Test type group is required** - Always specify integration/unit/feature
4. **Use consistent naming** - Follow the established group names above

### Best Practices

1. **Be specific but not excessive** - Include 3-6 relevant groups per test
2. **Use existing groups** - Don't create new groups without documentation updates
3. **Group by what you test, not how you test** - Focus on functionality over implementation
4. **Include component type** - Add livewire, tables, modals, etc. when testing components

### Group Naming Conventions

- Use **lowercase** for all group names
- Use **plural forms** for domain groups (managers, wrestlers, matches)
- Use **descriptive terms** for functionality (employment, injuries, relationships)
- Keep names **concise** but **clear** (use 'tagteams' not 'tag-teams')

## Integration with Development Workflow

### Pre-commit Testing

```bash
# Test changes related to specific domain before committing
vendor/bin/pest --group=managers

# Test all table components after table changes
vendor/bin/pest --group=tables
```

### CI/CD Integration

Groups can be used in CI/CD pipelines for:
- Parallel test execution by domain
- Focused testing for specific feature branches
- Progressive testing (fast groups first, slow groups later)

### IDE Integration

Most IDEs support running Pest groups directly:
- Configure run configurations for common group combinations
- Set up shortcuts for frequently used group commands
- Create templates for new tests with appropriate groups

## Troubleshooting

### Common Issues

**Tests not found with group:**
```bash
vendor/bin/pest --group=nonexistent
# Result: INFO No tests found.
```
- Verify group name spelling
- Check that tests actually have the specified group

**Tests missing from group:**
- Ensure `->group()` method is properly chained to test
- Verify group names match exactly (case-sensitive)

### Debugging Groups

```bash
# List all available groups in a file
vendor/bin/pest tests/Integration/Livewire/Managers/Tables/MainTest.php --list-groups

# Run specific test file to verify groups work
vendor/bin/pest tests/Integration/Livewire/Managers/Tables/MainTest.php --group=managers
```

## Maintenance

### Adding New Domains

When adding a new domain (e.g., "merchandise"):

1. Add the domain group to this documentation
2. Update the Available Groups table
3. Add example commands
4. Create tests with the new domain group

### Updating Group Structure

When changing group organization:

1. Update this documentation first
2. Update existing tests to match new structure
3. Run full test suite to verify changes
4. Update CI/CD configurations if needed

---

**Remember:** Proper test grouping is essential for efficient development. Always add appropriate groups when creating new tests, and use groups to focus your testing during development.