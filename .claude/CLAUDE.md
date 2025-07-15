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

When suggesting git commit commands, use this exact format:

```
git commit -m "type: brief description

- Detailed change 1
- Detailed change 2
- Detailed change 3
- Additional context or reasoning"
```

**Important Notes:**
- Do NOT include "🤖 Generated with [Claude Code](https://claude.ai/code)" 
- Do NOT include "Co-Authored-By: Claude <noreply@anthropic.com>"
- Keep the format clean and professional
- Use conventional commit types (feat, fix, docs, style, refactor, test, chore, etc.)

### TodoList Management Workflow

**CRITICAL: Commit and PR Before Context Switches**

When managing todos:
1. **Complete related tasks together** - Finish all tasks that are logically related
2. **Commit and PR before context switches** - When the next todo is unrelated to current work, commit current changes and create PR
3. **Resource-specific evaluation** - Before proceeding, evaluate if todos pertain to specific resources and should be organized differently
4. **Clean separation** - Don't mix unrelated changes in the same branch/PR

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

#### Stable Membership (joined/left)  
- Stables ↔ Wrestlers: `stables_wrestlers` table with `joined_at`/`left_at`
- Stables ↔ Tag Teams: `stables_tag_teams` table with `joined_at`/`left_at`
- These represent stable membership relationships

### Key Architecture Decisions
- NO direct stable-manager relationships
- Separate tables approach over polymorphic for better performance
- Employment status uses `App\Enums\Shared\EmploymentStatus`
- Domain-organized builders in `app/Builders/{Domain}/`
- Domain-organized enums in `app/Enums/{Domain}/`

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

#### Test Types
- **Structural Tests** (preferred): Test model configuration, traits, relationships
- **Functional Tests**: Test business logic and behavior
- **Integration Tests**: Test component interactions

#### Remove Duplicates
- Keep comprehensive domain-organized tests
- Remove simple functional tests that duplicate structural coverage

### Livewire Component Naming

**Class to View Mapping:**
- Class: `MatchesTable` → Component: `matches.tables.matches-table`
- Class: `EventMatchesTable` → Component: `matches.tables.event-matches-table`
- **Pattern:** PascalCase class → kebab-case with namespace dots

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