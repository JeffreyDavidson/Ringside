# Class Naming Conventions

Naming conventions for models, actions, repositories, controllers, and validation rules in Ringside.

## Overview

Consistent class naming ensures clear, maintainable code structure.

## Models

### Model Naming Standards
- **Singular Form**: Use singular names for models
- **PascalCase**: Use PascalCase for all model names
- **Descriptive Names**: Names should clearly indicate the entity

```php
// ✅ CORRECT
class Wrestler extends Model {}
class TagTeam extends Model {}
class WrestlerEmployment extends Model {}
class TitleChampionship extends Model {}

// ❌ INCORRECT
class wrestlers extends Model {}           // Not singular
class tag_team extends Model {}           // Not PascalCase
class Emp extends Model {}                // Not descriptive
```

## Actions

### Action Naming Standards
- **Verb + Noun**: Use action verb followed by subject noun
- **Suffix**: Always end with "Action"
- **Descriptive**: Name should clearly indicate the operation

```php
// ✅ CORRECT
class EmployWrestlerAction {}
class RetireWrestlerAction {}
class CreateStableAction {}
class MergeStablesAction {}

// ❌ INCORRECT
class WrestlerEmploy {}                   // Wrong order
class Employ {}                           // Missing noun
class WrestlerEmploymentCreator {}        // Wrong suffix
```

## Repositories

### Repository Naming Standards
- **Noun + Repository**: Entity name followed by "Repository"
- **Singular**: Use singular entity name
- **Interface**: Repository interfaces follow same pattern with "Interface" suffix

```php
// ✅ CORRECT
class WrestlerRepository {}
class TagTeamRepository {}
interface WrestlerRepositoryInterface {}

// ❌ INCORRECT
class WrestlersRepository {}              // Not singular
class WrestlerRepo {}                     // Abbreviated
class WrestlerService {}                  // Wrong suffix
```

## Controllers

### Controller Naming Standards
- **Domain Organization**: Group controllers by domain/entity
- **Resource Controllers**: Use a plural resource name for endpoints belonging to one resource
- **Invokable Controllers**: Use an action-oriented name for standalone endpoints that do not fit a resource method

```php
// ✅ CORRECT
namespace App\Http\Controllers\Events;
class EventsController {
    public function index(): View {}
    public function show(Event $event): View {}
}

class EventMatchesController {
    public function index(Event $event): View {}
}

class DashboardController {
    public function __invoke(): View {}
}

// ❌ INCORRECT
class IndexController {}                  // Resource operation split into its own controller
class ShowController {}                   // Resource operation split into its own controller
class EventController {}                  // Resource controller name is singular
class ProcessController {}                // Standalone action name is vague
class EventsIndexController {}            // Redundant domain prefix
```

## Validation Rules

### Validation Rule Naming Standards
- **Descriptive Names**: Clearly indicate validation purpose
- **Domain Context**: Include domain context when needed
- **Verb Form**: Use verb forms for action-based validation

```php
// ✅ CORRECT
class IsActive {}
class IsBookable {}
class CanChangeEmploymentDate {}
class HasMinimumMembers {}

// ❌ INCORRECT
class Active {}                           // Not descriptive
class Validator {}                        // Too generic
class WrestlerBookable {}                 // Redundant domain
```

## Related Documentation
- [Naming Conventions](naming.md)
- [Structural Patterns](structure.md)
- [Testing Conventions](testing.md)
