# Claude Code Documentation

## Git Workflow Requirements

### **CRITICAL: Always Use Feature Branches**
- **NEVER commit directly to development branch**
- **ALL code changes must be on properly named feature branches**
- **ALL changes require PR approval before merging**

### Branch Naming Convention
- `feat/feature-name` - New features
- `fix/bug-description` - Bug fixes  
- `tests/fix-description` - Test-related fixes
- `tests/add-description` - Adding new tests
- `refactor/code-improvement` - Code refactoring
- `docs/documentation-update` - Documentation changes
- `chore/task-description` - Maintenance tasks

**Examples:**
- `tests/fix-test-failures` - Fixing failing tests
- `tests/add-policy-coverage` - Adding new test coverage
- `refactor/organize-model-tests` - Restructuring test organization

### Git Commit Message Format

**Always create focused, logical commits separated by concern rather than bundling unrelated changes.**

Use this exact format:

```
type: brief description

- Detailed change 1
- Detailed change 2  
- Additional context or reasoning
```

**Conventional Commit Types for Subject Line:**
- `fix:` - Bug fixes
- `feat:` - New features  
- `docs:` - Documentation updates
- `chore:` - Maintenance tasks
- `refactor:` - Code refactoring
- `test:` - Test additions/improvements
- `style:` - Code formatting changes
- `perf:` - Performance improvements
- `ci:` - CI/CD changes

**Example Commits:**
```bash
git commit -m "fix: correct TagTeams IndexController view name

- Change 'tagteams.index' to 'tag-teams.index' to match actual view file location
- Resolves test failure where view was not found
- Follows kebab-case naming convention for view directories"
```

```bash
git commit -m "docs: enhance CLAUDE.md with comprehensive development patterns

- Add Code Organization Patterns section with Bookable interface guidelines
- Document Policy Pattern with before hook examples  
- Add Controller Patterns and View Naming Convention guidelines
- Include Trait Naming Guidelines and Interface Implementation Strategy
- Add Testing and Debugging Workflow with systematic failure resolution

These additions capture architectural decisions and patterns established
during test failure resolution and code organization work."
```

**Important Notes:**
- Do NOT include "🤖 Generated with [Claude Code](https://claude.ai/code)" 
- Do NOT include "Co-Authored-By: Claude <noreply@anthropic.com>"
- Keep the format clean and professional
- Each commit should focus on a single concern or related group of changes

### TodoList Management Workflow

**CRITICAL: Commit and PR Before Context Switches**

When managing todos:
1. **Complete related tasks together** - Finish all tasks that are logically related
2. **Commit and PR before context switches** - When the next todo is unrelated to current work, commit current changes and create PR
3. **Resource-specific evaluation** - Before proceeding, evaluate if todos pertain to specific resources and should be organized differently
4. **Clean separation** - Don't mix unrelated changes in the same branch/PR

### Pull Request Creation Requirements

**MANDATORY: Always ask for explicit approval before creating PRs**

Before creating any pull request:
1. **Ask the user**: "Is it okay for me to create a PR for these changes?"
2. **Wait for explicit "Yes"** - Do not proceed without clear approval
3. **Only create PR after receiving approval** - Never assume permission

**Example:**
```
The work is complete. Is it okay for me to create a PR for these changes?
```

**Wait for user response before using `gh pr create` command.**

**Example Decision Points:**
- Just finished Rules organization → Next todo is Builders restructure = **COMMIT & PR**
- Just finished Repository work → Next todo is Policy cleanup = **COMMIT & PR** 
- Working on multiple Model tests → Next todo is also Model tests = **CONTINUE**

**Benefits:**
- Cleaner git history with focused PRs
- Easier code review and rollback
- Logical separation of concerns
- Better tracking of architectural changes

## Architecture Documentation

For detailed architecture information, see the documentation in `docs/architecture/`:

- **[Builders](docs/architecture/builders.md)**: Query builder patterns and domain organization
- **[Enums](docs/architecture/enums.md)**: Enum organization standards and usage guidelines
- **[Business Rules](docs/architecture/business-rules.md)**: Wrestling business logic and constraints
- **[Match Generation](docs/architecture/match-generation.md)**: Match creation and validation

## Quick Reference

### Relationship Patterns

#### Employment Relationships (hired/fired)
- Managers ↔ Wrestlers: `managers_wrestlers` table with `hired_at`/`fired_at`
- Managers ↔ Tag Teams: `managers_tag_teams` table with `hired_at`/`fired_at`
- These represent business employment contracts
- **Business Rule**: Both entities must be employed, but managed entity doesn't need to be bookable
  - Injured/suspended wrestlers can still have managers
  - Managers provide career guidance regardless of competition availability

#### Stable Membership (joined/left)  
- Stables ↔ Wrestlers: `stables_wrestlers` table with `joined_at`/`left_at`
- Stables ↔ Tag Teams: `stables_tag_teams` table with `joined_at`/`left_at`
- These represent stable membership relationships
- **DECISION: Use separate tables (not polymorphic)** for type safety and clear relationships

### Key Architecture Decisions
- NO direct stable-manager relationships
- Separate tables approach over polymorphic for better performance
- Employment status uses `App\Enums\Shared\EmploymentStatus`
- Domain-organized builders in `app/Builders/{Domain}/`
- Domain-organized enums in `app/Enums/{Domain}/`

### Computed Status Pattern
- **Status fields are computed, not stored** - eliminates data inconsistency
- Models use computed attributes: `protected function status(): Attribute`
- Factory methods NEVER set status fields manually
- Status computed from relationships (employment, retirement, injury, suspension)
- Priority order: Retired > Employed > FutureEmployment > Released > Unemployed

### Factory Method Patterns
- **Employable entities**: `employed()`, `unemployed()`, `retired()`, `released()`, `suspended()`, `injured()`
- **Bookable entities**: `bookable()` (alias for employed() for competitors and officials)
- **Non-bookable entities**: NO `bookable()` method (Managers, Stables, etc.)
- **Activation entities**: `active()`, `inactive()`, `unactivated()`
- **User entities**: `verified()`, `unverified()`
- **Relationships**: Set via `has()` relationships, never direct field assignment

### Essential Enum Usage
- **Employment Status**: `App\Enums\Shared\EmploymentStatus` for pure employment states
- **Activation Status**: `App\Enums\Shared\ActivationStatus` for general activation
- **Title Status**: `App\Enums\Titles\TitleStatus` for title-specific states
- **User Enums**: `App\Enums\Users\Role` and `App\Enums\Users\UserStatus`

## Code Organization Patterns

### Bookable Interface Implementation

**Two distinct patterns for match participation:**

#### Competitors (Wrestlers, Tag Teams)
- Use `IsBookableCompetitor` trait
- Relationship: Many-to-many polymorphic through `event_match_competitors` table
- Method: `$this->morphToMany(EventMatch::class, 'competitor', 'event_match_competitors')`

#### Officials (Referees)
- Use `OfficiatesMatches` trait  
- Relationship: Many-to-many direct through `events_matches_referees` table
- Method: `$this->belongsToMany(EventMatch::class, 'events_matches_referees')`

**Key Principle:** Different entity types have different relationships with matches - competitors participate, officials officiate.

#### Non-Bookable Entities (Managers)
- **Managers are NOT bookable** - they manage other entities but don't participate in matches
- Factory pattern: Use `employed()`, `suspended()`, `injured()`, etc., but NO `bookable()` method
- Relationship pattern: Managers have employment relationships with wrestlers/tag teams, not match participation
- **Key Business Rule**: Wrestlers don't need to be bookable to have a manager - only employed
  - Manager ↔ Wrestler relationship requires both to be employed
  - Wrestler bookability is separate (affected by injury, suspension, etc.)
  - An injured wrestler can still have a manager managing their career

### Policy Pattern

**All policies use before hook pattern:**
```php
public function before(User $user, string $ability): ?bool
{
    if ($user->isAdministrator()) {
        return true; // Bypass all checks for admins
    }
    return null; // Continue to individual method checks
}

public function viewList(User $user): bool
{
    return false; // Will be bypassed by before hook for administrators
}
```

**Benefits:** Eliminates repetitive administrator checks in every method.

### Controller Patterns

#### Invokable Controllers
- Domain-organized in `app/Http/Controllers/{Domain}/`
- Always authorize using `Gate::authorize()` before business logic
- Return views with explicit data arrays when needed
- Example: `return view('tag-teams.index', ['data' => $data]);`

#### View Naming Convention
- Controller view names use dot notation: `tag-teams.index`
- Maps to file path: `resources/views/tag-teams/index.blade.php`
- **Always use kebab-case** for view directories and files

### Trait Naming Guidelines

**Avoid redundant names:**
- ❌ `IsBookableReferee` (redundant if only used by Referee model)
- ✅ `OfficiatesMatches` (descriptive and potentially reusable)

**Use descriptive verbs:**
- `OfficiatesMatches` - for entities that officiate matches
- `IsBookableCompetitor` - for entities that compete in matches
- `ManagesEntities` - for entities that manage other entities

### Test Organization

#### Mirror Application Structure
```
app/Models/Events/Event.php → tests/Unit/Models/Events/EventTest.php
app/Builders/Events/ → tests/Unit/Builders/Events/
app/Rules/Events/ → tests/Unit/Rules/Events/
```

#### Integration Test Structure
```
app/Models/Wrestlers/WrestlerManager.php → tests/Integration/Models/Wrestlers/WrestlerManagerTest.php
app/Models/TagTeams/TagTeamWrestler.php → tests/Integration/Models/TagTeams/TagTeamWrestlerTest.php
app/Models/Stables/StableMember.php → tests/Integration/Models/Stables/StableMemberTest.php
app/Models/Titles/TitleChampionship.php → tests/Integration/Models/Titles/TitleChampionshipTest.php
```

#### Test Types
- **Structural Tests** (preferred): Test model configuration, traits, relationships
- **Functional Tests**: Test business logic and behavior
- **Integration Tests**: Test component interactions with real database
- **Relationship Tests**: Test pivot models and complex relationships

#### Directory Consolidation
- **Remove redundant directories**: `tests/Integration/Relationships/` → `tests/Integration/Models/{Domain}/`
- **Consolidate scattered tests**: Multiple championship directories → Single `Models/Titles/` location  
- **Focus on primary model**: Test the actual model class, not the relationship description
- **Maintain UI separation**: Livewire tests stay in `tests/Integration/Livewire/{Domain}/`

#### Remove Duplicates
- Keep comprehensive domain-organized tests
- Remove simple functional tests that duplicate structural coverage
- Consolidate similar tests using parameterization

### Livewire Component Architecture Standardization

#### ✅ **COMPLETED - Phase 5: Component Standardization**

Successfully implemented standardized naming conventions across all Livewire components:

#### Implemented Changes:
- **✅ Actions Components**: Renamed `ActionsComponent.php` → `Actions.php` across all domains
- **✅ Form Components**: Renamed `EventMatchForm.php` → `CreateEditForm.php` for consistency
- **✅ Table Components**: Renamed `{Entity}Table.php` → `Main.php` for primary entity tables
- **✅ Relationship Tables**: Renamed `Previous{Entity}Table.php` → `Previous{Entity}.php`
- **✅ Test Files**: Updated all test files to match new component names
- **✅ Documentation**: Updated architecture and example documentation

#### Final Structure:

```
app/Livewire/{Domain}/
├── Components/
│   └── Actions.php              ✅ (standardized naming)
├── Forms/
│   └── CreateEditForm.php       ✅ (descriptive purpose)
├── Modals/
│   └── FormModal.php            ✅ (consistent pattern)
└── Tables/
    ├── Main.php                 ✅ (primary entity table)
    ├── PreviousManagers.php     ✅ (relationship tables)
    ├── PreviousMatches.php      ✅ (descriptive names)
    └── PreviousEvents.php       ✅ (consistent pattern)
```

#### Achieved Benefits:
1. **✅ Eliminated redundant suffixes** - folder context provides component type
2. **✅ Descriptive purposes** - `Main.php`, `Actions.php`, `CreateEditForm.php`
3. **✅ Scalable structure** - supports multiple components per domain
4. **✅ Consistent patterns** - same naming rules across all domains
5. **✅ Improved maintainability** - clearer component organization

### Livewire Component Naming (Legacy Reference)

**Class to View Mapping:**
- Class: `MatchesTable` → Component: `matches.tables.matches-table`
- Class: `EventMatchesTable` → Component: `matches.tables.event-matches-table`
- **Pattern:** PascalCase class → kebab-case with namespace dots

**Avoid Redundant Domain Prefixes:**
- ❌ `WrestlerActionsComponent` (inside `app/Livewire/Wrestlers/Components/`)
- ✅ `ActionsComponent` (directory context makes domain clear)
- ❌ `WrestlerFormModal` → ✅ `FormModal` (when inside Wrestlers directory)
- **Rule:** Domain context from directory structure eliminates need for domain prefix in class names

### Interface Implementation Strategy

**When to use traits vs direct implementation:**

#### Use Traits When:
- Multiple models need the same functionality
- Code would be duplicated across models
- Behavior is cohesive and reusable

#### Direct Implementation When:
- Only one model uses the interface
- Implementation is model-specific
- Trait would be overly specific

**Example:** `EventMatchPolicy` is implemented directly since only EventMatch needs it, while `IsBookableCompetitor` is a trait since multiple competitor types use it.

## Testing and Debugging Workflow

### Systematic Test Failure Resolution

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

### Debugging Strategy

1. **Read error messages carefully** - Often contain exact file paths and line numbers
2. **Use debug output when needed** - `dump()` or `dd()` in tests to understand state
3. **Check file existence** - Many errors are missing files that need to be created
4. **Verify naming conventions** - Class names, view names, component names must match patterns
5. **Follow the stack trace** - Understanding error origins helps identify root causes

### Common Error Patterns

#### Missing Files
- **Policy files**: Create using existing policy as template with before hook pattern
- **View files**: Check kebab-case naming and directory structure
- **Component files**: Verify PascalCase class names map to kebab-case component names

#### Interface Implementation Issues  
- **Missing methods**: Check if trait provides required methods or implement directly
- **Wrong return types**: Ensure interface contracts are satisfied
- **Relationship mismatches**: Verify polymorphic vs direct relationships

#### View/Controller Mismatches
- **Wrong view names**: Controller returns `tag-teams.index` → file at `tag-teams/index.blade.php`
- **Missing variables**: Controller must pass data that view expects
- **Component naming**: Livewire class `MatchesTable` → component `matches.tables.matches-table`

### Test Helper Functions

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

### TodoList for Test Fixing

**Create todos for systematic approach:**
1. Run tests to identify current failures
2. Create specific todo for each type of failure found
3. Fix related issues together (e.g., all policy issues)  
4. Commit and PR before switching to unrelated failure types
5. Continue until all failures resolved

**Example todo structure:**
- Fix Bookable interface implementation issues
- Create missing policy files  
- Resolve view naming inconsistencies
- Fix controller parameter handling