# Exception Writing Guide

This document provides comprehensive guidelines for creating business exceptions in the wrestling promotion management system. All exceptions should follow the established `CannotBePulledException` pattern for consistency, maintainability, and clear business communication.

## Table of Contents

- [Overview](#overview)
- [Exception Structure](#exception-structure)
- [Domain Organization](#domain-organization)
- [Class Structure](#class-structure)
- [Documentation Standards](#documentation-standards)
- [Static Factory Methods](#static-factory-methods)
- [Error Messages](#error-messages)
- [Testing Exceptions](#testing-exceptions)
- [Common Patterns](#common-patterns)
- [Examples](#examples)

## Overview

All business exceptions in this application should:

1. **Extend BaseBusinessException** - Inherit standardized utilities and patterns
2. **Use domain-specific organization** - Group exceptions by business area
3. **Follow naming conventions** - Use clear, action-oriented names
4. **Include comprehensive documentation** - Provide business context and scenarios
5. **Use model-aware parameters** - Accept model objects for type safety
6. **Generate actionable error messages** - Tell users what went wrong and what to do

## Exception Structure

### Basic Template

```php
<?php

declare(strict_types=1);

namespace App\Exceptions\[Domain];

use App\Exceptions\BaseBusinessException;
use App\Models\[Domain]\[ModelClass];

/**
 * Exception thrown when [entity] cannot [action] due to business rule violations.
 *
 * This exception handles scenarios where [action] is prevented by current state
 * or business logic constraints in wrestling promotion [domain] management.
 *
 * BUSINESS CONTEXT:
 * [Detailed explanation of the business operation, its importance, and relationship
 * to other business processes. Include domain-specific terminology and concepts.]
 *
 * COMMON SCENARIOS:
 * - [Specific business scenario 1 with context]
 * - [Specific business scenario 2 with context]
 * - [Specific business scenario 3 with context]
 * - [Additional scenarios as needed]
 *
 * BUSINESS IMPACT:
 * - [How this exception protects business integrity]
 * - [What operations or data consistency is maintained]
 * - [Regulatory or contractual compliance protected]
 * - [User experience or operational impact prevented]
 */
final class Cannot[Action]Exception extends BaseBusinessException
{
    /**
     * [Entity] [specific condition] and cannot [action].
     *
     * @param  ModelClass  $entity  The [entity] that cannot [action]
     * @param  string|null  $additionalContext  Optional additional context
     */
    public static function specificCondition(ModelClass $entity, ?string $additionalContext = null): static
    {
        $context = self::formatModelContext($entity);
        $extra = $additionalContext ? " ({$additionalContext})" : '';

        return new self("{$context} [specific condition description]{$extra} and cannot [action].");
    }
}
```

## Domain Organization

Exceptions are organized by business domain to improve maintainability and logical grouping:

### Directory Structure

```
app/Exceptions/
├── BaseBusinessException.php
├── BusinessRules/           # General business rule violations
├── Data/                   # Data integrity and restoration
├── Matches/                # Competition and match-related
├── Roster/                 # Personnel management
│   └── Stables/           # Stable-specific exceptions
├── Scheduling/             # Event and timing conflicts
└── Titles/                 # Championship management
```

### Domain Mapping

- **BusinessRules**: Cross-cutting business logic violations
- **Data**: Data integrity, restoration, and database operations
- **Matches**: Competition booking, competitor conflicts, match configuration
- **Roster**: Personnel management (wrestlers, referees, managers)
  - **Roster/Stables**: Stable-specific operations
- **Scheduling**: Event scheduling, venue conflicts, timing issues
- **Titles**: Championship management, title lifecycle operations

## Class Structure

### Class Declaration

```php
final class CannotBePulledException extends BaseBusinessException
```

**Key Points:**
- Use `final` to prevent inheritance
- Extend `BaseBusinessException` for utility methods
- Follow `Cannot[Action]Exception` naming convention

### Required Imports

```php
use App\Exceptions\BaseBusinessException;
use App\Models\[Domain]\[ModelClass];
use Illuminate\Support\Carbon; // Only if using dates
```

## Documentation Standards

### Class Documentation Template

```php
/**
 * Exception thrown when [entity] cannot [action] due to business rule violations.
 *
 * This exception handles scenarios where [action] is prevented by current state
 * or business logic constraints in wrestling promotion [domain] management.
 *
 * BUSINESS CONTEXT:
 * [2-3 sentences explaining the business operation, its importance in the domain,
 * and how it relates to other business processes. Use domain-specific terminology.]
 *
 * COMMON SCENARIOS:
 * - [Scenario 1: Specific business situation that triggers this exception]
 * - [Scenario 2: Another common business case with context]
 * - [Scenario 3: Edge case or complex business rule]
 * - [Continue with additional scenarios as needed]
 *
 * BUSINESS IMPACT:
 * - [Impact 1: How this exception protects business data integrity]
 * - [Impact 2: What business operations or consistency is maintained]
 * - [Impact 3: Regulatory, contractual, or compliance protection]
 * - [Impact 4: User experience or operational impact prevented]
 */
```

### Method Documentation Template

```php
/**
 * [Entity] [specific condition description] and cannot [action].
 *
 * @param  ModelClass  $entity  The [entity] that cannot [action]
 * @param  string|null  $additionalContext  Optional additional context information
 */
public static function specificCondition(ModelClass $entity, ?string $additionalContext = null): static
```

## Static Factory Methods

### Method Patterns

All exception classes should use static factory methods instead of direct instantiation:

#### Basic State Violation

```php
public static function invalidState(ModelClass $entity): static
{
    $context = self::formatModelContext($entity);
    return new self("{$context} is in [state] and cannot [action].");
}
```

#### Business Rule Violation

```php
public static function businessRule(ModelClass $entity, string $ruleDetails): static
{
    $context = self::formatModelContext($entity);
    return new self("{$context} violates business rule ({$ruleDetails}) and cannot [action].");
}
```

#### Authorization Violation

```php
public static function unauthorized(ModelClass $entity, string $requiredLevel): static
{
    $context = self::formatModelContext($entity);
    return new self("{$context} cannot [action] without {$requiredLevel} authorization.");
}
```

#### Relationship Conflict

```php
public static function relationshipConflict(ModelClass $entity, ModelClass $conflictingEntity): static
{
    $entityContext = self::formatModelContext($entity);
    $conflictContext = self::formatModelContext($conflictingEntity);
    return new self("{$entityContext} has conflict with {$conflictContext} and cannot [action].");
}
```

#### Time-Based Violation

```php
public static function outsideTimeWindow(ModelClass $entity, Carbon $eventDate, int $requiredDays): static
{
    $context = self::formatModelContext($entity);
    $dateFormatted = self::formatDateContext($eventDate);
    $daysUntilEvent = now()->diffInDays($eventDate);
    
    return new self("{$context} requires {$requiredDays} days notice but event on {$dateFormatted} is in {$daysUntilEvent} days.");
}
```

### Method Guidelines

1. **Use descriptive method names** - Method name should clearly indicate the violation
2. **Model-first parameters** - Always put the primary model as the first parameter
3. **Optional context parameters** - Allow additional context for complex scenarios
4. **Use `self::formatModelContext()`** - Leverage inherited utility for consistent formatting
5. **Return type `static`** - Use static return type for factory methods
6. **Include type hints** - Provide full type hints for all parameters

## Error Messages

### Message Structure

Error messages should follow this pattern:
```
[Entity Context] [specific condition/state] and cannot [action]. [Optional resolution guidance]
```

### Examples

```php
// Good: Clear, actionable, business-focused
"Wrestler 'John Doe' is currently injured since 2024-01-15 and cannot be booked for matches. Wait for recovery or choose available competitor."

// Good: Specific business context
"Title 'WWE Championship' is involved in Tournament Finals and cannot be pulled until the event concludes."

// Poor: Technical, not actionable
"Entity cannot perform action due to invalid state."

// Poor: Missing business context
"Operation not allowed."
```

### Message Guidelines

1. **Start with entity context** - Use `self::formatModelContext($model)`
2. **Explain the specific condition** - Why the action is blocked
3. **State the action clearly** - What cannot be done
4. **Provide resolution guidance** - What should be done instead
5. **Use business terminology** - Match language used by domain experts
6. **Be specific and actionable** - Help users understand next steps

## Testing Exceptions

### Test Structure

```php
public function test_cannot_action_when_specific_condition(): void
{
    // Arrange
    $entity = EntityFactory::new()->specificCondition()->create();
    
    // Act & Assert
    $this->expectException(CannotActionException::class);
    $this->expectExceptionMessage("EntityType 'Entity Name' [condition] and cannot [action]");
    
    $action = new ActionClass();
    $action->handle($entity);
}
```

### Testing Best Practices

1. **Test each factory method** - Ensure every static method is covered
2. **Verify message content** - Check that error messages are correct
3. **Test edge cases** - Cover boundary conditions and complex scenarios
4. **Mock dependencies** - Isolate exception logic from external dependencies
5. **Use descriptive test names** - Clearly indicate what scenario is being tested

## Common Patterns

### Already In State Pattern

```php
public static function alreadyActive(Title $title): static
{
    $context = self::formatModelContext($title);
    return new self("{$context} is already active and cannot be activated again.");
}
```

### Missing Requirements Pattern

```php
public static function missingRequirement(Wrestler $wrestler, string $requirement): static
{
    $context = self::formatModelContext($wrestler);
    return new self("{$context} does not meet requirement: {$requirement}. Complete requirement first.");
}
```

### Conflict With Another Entity Pattern

```php
public static function conflictsWith(Wrestler $wrestler, Event $event): static
{
    $wrestlerContext = self::formatModelContext($wrestler);
    $eventContext = self::formatModelContext($event);
    return new self("{$wrestlerContext} has scheduling conflict with {$eventContext}.");
}
```

### Insufficient Resources Pattern

```php
public static function insufficientMembers(TagTeam $tagTeam, int $current, int $required): static
{
    $context = self::formatModelContext($tagTeam);
    return new self("{$context} has {$current} members but requires {$required} for competition.");
}
```

## Examples

### Complete Exception Class

```php
<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

/**
 * Exception thrown when title cannot be pulled from competition due to business rule violations.
 *
 * This exception handles scenarios where title pulling is prevented by current state
 * or business logic constraints in wrestling promotion title management.
 *
 * BUSINESS CONTEXT:
 * Title pulling removes championships from active competition, typically for storyline
 * reasons, venue changes, or business restructuring. This critical operation affects
 * championship lineage, competitor booking, and event planning across the promotion.
 *
 * COMMON SCENARIOS:
 * - Attempting to pull inactive titles that are not currently in competition
 * - Pulling titles during active championship reigns without proper storyline resolution
 * - Unauthorized title pulling without proper executive approval
 * - Pulling titles involved in scheduled tournament or championship matches
 * - Removing titles during contractual obligations or promotional commitments
 *
 * BUSINESS IMPACT:
 * - Maintains championship lineage integrity and historical accuracy
 * - Protects active storylines and competitor booking consistency
 * - Ensures proper authorization for title management decisions
 * - Prevents disruption of scheduled events and tournament structures
 * - Upholds contractual obligations and promotional commitments
 */
final class CannotBePulledException extends BaseBusinessException
{
    /**
     * Title is not currently active and cannot be pulled from competition.
     *
     * @param  Title  $title  The title that cannot be pulled
     */
    public static function notActive(Title $title): static
    {
        $context = self::formatModelContext($title);
        return new self("{$context} is not currently active and cannot be pulled from competition.");
    }

    /**
     * Title cannot be pulled during active championship reign.
     *
     * @param  Title  $title  The title that cannot be pulled
     * @param  string  $championName  Name of the current champion
     */
    public static function activeChampionshipReign(Title $title, string $championName): static
    {
        $context = self::formatModelContext($title);
        return new static("{$context} is currently held by {$championName} and cannot be pulled during an active championship reign.");
    }

    /**
     * Title cannot be pulled without proper authorization.
     *
     * @param  Title  $title  The title that cannot be pulled
     * @param  string  $authorizationLevel  Required authorization level
     */
    public static function unauthorizedPull(Title $title, string $authorizationLevel): static
    {
        $context = self::formatModelContext($title);
        return new static("{$context} cannot be pulled without {$authorizationLevel} authorization.");
    }

    /**
     * Title cannot be pulled due to tournament involvement.
     *
     * @param  Title  $title  The title that cannot be pulled
     * @param  string  $eventDetails  Details about the tournament or event
     */
    public static function tournamentInvolvement(Title $title, string $eventDetails): static
    {
        $context = self::formatModelContext($title);
        return new static("{$context} is involved in {$eventDetails} and cannot be pulled until the event concludes.");
    }
}
```

### Usage in Action Classes

```php
<?php

namespace App\Actions\Titles;

use App\Actions\BaseAction;
use App\Exceptions\Titles\CannotBePulledException;
use App\Models\Titles\Title;

class PullAction extends BaseAction
{
    public function handle(Title $title): void
    {
        // Validate business rules
        if (!$title->isCurrentlyActive()) {
            throw CannotBePulledException::notActive($title);
        }

        if ($title->hasActiveChampion()) {
            $champion = $title->currentChampion();
            throw CannotBePulledException::activeChampionshipReign($title, $champion->name);
        }

        if ($title->isInvolvedInTournament()) {
            $tournament = $title->activeTournament();
            throw CannotBePulledException::tournamentInvolvement($title, $tournament->name);
        }

        // Perform the business operation
        $title->pullFromCompetition();
    }
}
```

## Best Practices Summary

1. **Follow the established pattern** - Use `CannotBePulledException` as your reference
2. **Organize by domain** - Place exceptions in appropriate domain directories
3. **Write comprehensive documentation** - Include business context, scenarios, and impact
4. **Use model-aware parameters** - Accept model objects for type safety
5. **Create actionable error messages** - Help users understand what to do next
6. **Test thoroughly** - Cover all factory methods and edge cases
7. **Use consistent naming** - Follow `Cannot[Action]Exception` convention
8. **Leverage base utilities** - Use `self::formatModelContext()` and other inherited methods
9. **Make classes final** - Prevent inheritance for better design
10. **Provide resolution guidance** - Tell users how to resolve the issue

Following these guidelines ensures consistent, maintainable, and business-focused exception handling throughout the wrestling promotion management system.