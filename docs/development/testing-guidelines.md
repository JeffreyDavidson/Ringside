# Testing and Debugging Guidelines

## Systematic Test Failure Resolution

**Use `--stop-on-failure` for systematic fixing:**
```bash
./vendor/bin/pest --stop-on-failure
```

**Benefits:**
- Address one failure at a time
- Avoid being overwhelmed by multiple issues  
- Ensure each fix is complete before moving on
- Maintain clear focus on current problem

**CRITICAL: When Tests Fail - App Directory is Authoritative**

**IMPORTANT:** If tests fail and expect different behavior than the app directory implementation:
1. **DO NOT automatically fix code to match test expectations**
2. **Discuss with the user before making changes** 
3. **The app directory structure is considered the authoritative source**
4. **Update tests to match the correct app implementation**

At this point in development, the application structure is well-established, so failing tests likely need to be updated rather than the application code being "wrong".

## Debugging Strategy

1. **Read error messages carefully** - Often contain exact file paths and line numbers
2. **Use debug output when needed** - `dump()` or `dd()` in tests to understand state
3. **Check file existence** - Many errors are missing files that need to be created
4. **Verify naming conventions** - Class names, view names, component names must match patterns
5. **Follow the stack trace** - Understanding error origins helps identify root causes

## Common Error Patterns

### Missing Files
- **Policy files**: Create using existing policy as template with before hook pattern
- **View files**: Check kebab-case naming and directory structure
- **Component files**: Verify PascalCase class names map to kebab-case component names

### Interface Implementation Issues  
- **Missing methods**: Check if trait provides required methods or implement directly
- **Wrong return types**: Ensure interface contracts are satisfied
- **Relationship mismatches**: Verify polymorphic vs direct relationships

### View/Controller Mismatches
- **Wrong view names**: Controller returns `tag-teams.index` → file at `tag-teams/index.blade.php`
- **Missing variables**: Controller must pass data that view expects
- **Component naming**: Livewire class `MatchesTable` → component `matches.tables.matches-table`

## Test Helper Functions

**Integration Test Helpers (`tests/Helpers/IntegrationTestHelpers.php`):**
- `createManagementRelationship()` - Set up manager-wrestler relationships
- `createTagTeamMembership()` - Set up tag team memberships  
- `createManagementHistory()` - Multiple management periods
- `createTagTeamHistory()` - Multiple tag team periods
- `createOverlappingManagementPeriods()` - For validation testing
- `createComplexRelationshipScenario()` - Comprehensive setups

**Status Test Expectations (`tests/Helpers/StatusTestExpectations.php`):**
- `expectRelationshipCounts()` - Validate relationship counts
- `expectManagerRelationship()` - Validate manager relationship data
- `expectTagTeamMembership()` - Validate tag team membership data
- `expectCurrentRelationshipsActive()` - Ensure current relationships have no end dates
- `expectValidRelationshipDates()` - Validate chronological order
- `expectNoOverlappingRelationships()` - Business rule validation

**Pattern:** Create helpers for repetitive operations, use expectations for complex validations.

## Test Organization

### Mirror Application Structure
```
app/Models/Events/Event.php → tests/Unit/Models/Events/EventTest.php
app/Builders/Events/ → tests/Unit/Builders/Events/
app/Rules/Events/ → tests/Unit/Rules/Events/
```

### Integration Test Structure  
```
app/Models/Wrestlers/WrestlerManager.php → tests/Integration/Models/Wrestlers/WrestlerManagerTest.php
app/Models/TagTeams/TagTeamWrestler.php → tests/Integration/Models/TagTeams/TagTeamWrestlerTest.php
app/Models/Stables/StableMember.php → tests/Integration/Models/Stables/StableMemberTest.php
app/Models/Titles/TitleChampionship.php → tests/Integration/Models/Titles/TitleChampionshipTest.php
```

### Test Types
- **Structural Tests** (preferred): Test model configuration, traits, relationships
- **Functional Tests**: Test business logic and behavior
- **Integration Tests**: Test component interactions with real database
- **Relationship Tests**: Test pivot models and complex relationships

### Directory Consolidation
- **Remove redundant directories**: `tests/Integration/Relationships/` → `tests/Integration/Models/{Domain}/`
- **Consolidate scattered tests**: Multiple championship directories → Single `Models/Titles/` location  
- **Focus on primary model**: Test the actual model class, not the relationship description
- **Maintain UI separation**: Livewire tests stay in `tests/Integration/Livewire/{Domain}/`

### Remove Duplicates
- Keep comprehensive domain-organized tests
- Remove simple functional tests that duplicate structural coverage
- Consolidate similar tests using parameterization

## TodoList for Test Fixing

**Create todos for systematic approach:**
1. Run tests to identify current failures
2. Create specific todo for each type of failure found
3. Fix related issues together (e.g., all policy issues)  
4. Commit and PR before switching to unrelated failure types
5. Continue until all failures resolved

**Example todo structure:**
- Fix Bookable interface implementation issues
- Create missing policy files  
- Update view names to match controller expectations
- Resolve relationship mapping issues