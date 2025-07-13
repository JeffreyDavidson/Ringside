# Database Factory Testing Guidelines

**CRITICAL PRINCIPLE: Database Factory tests provide genuine business value and should REMAIN as Unit tests.**

## Factory Test Location and Structure
**Location**: `tests/Unit/Database/Factories/{Domain}/{FactoryName}Test.php`
**Total Coverage**: 37 Database Factory tests (100% coverage maintained)
**Test Category**: Unit Tests (appropriate level for isolated data generation testing)

## Why Database Factory Tests Are Valuable

### 1. Test Actual Business Logic
Factory tests verify realistic data generation and business rule compliance:
```php
// Tests realistic wrestling data generation
test('wrestler factory creates wrestler with realistic data', function () {
    $wrestler = Wrestler::factory()->make();
    
    expect($wrestler->height)->toBeInstanceOf(Height::class);
    expect($wrestler->weight)->toBeGreaterThan(0);
    expect($wrestler->status)->toBeInstanceOf(EmploymentStatus::class);
});
```

### 2. Verify Business Rule Compliance
```php
// Tests championship timeline integrity
test('creates realistic championship timeline', function () {
    $championship = TitleChampionship::factory()->make();
    
    expect($championship->won_at->isPast())->toBeTrue();
    expect($championship->won_at->isAfter(now()->subYear()))->toBeTrue();
    expect($championship->lost_at->isAfter($championship->won_at))->toBeTrue();
});
```

### 3. Test Critical Factory State Methods
```php
// Tests employment states that drive comprehensive testing
test('wrestler factory can create employed wrestlers', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    
    expect($wrestler->currentEmployment)->not->toBeNull();
    expect($wrestler->currentEmployment->ended_at)->toBeNull();
});
```

## Factory vs Deleted Livewire Test Comparison

| Aspect | Database Factory Tests | Deleted Livewire Tests |
|--------|----------------------|----------------------|
| **Purpose** | Test data generation logic | Test PHP class structure |
| **Business Value** | High - ensures realistic test data | Low - tested language mechanics |
| **Maintainability** | Low maintenance - stable data patterns | High maintenance - brittle reflection |
| **Testing Focus** | Business rule compliance | Method existence |
| **ROI** | High - supports all other tests | Low - no functional benefit |

## Comprehensive Factory Test Structure

**Required Factory Test Coverage:**
```php
<?php

declare(strict_types=1);

use App\Models\EntityType\EntityModel;
use App\Enums\EntityStatus;

/**
 * Unit tests for EntityFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (realistic data patterns)
 * - Factory state methods (unemployed, employed, injured, suspended, retired, etc.)
 * - Factory relationship creation (withManagers, withTagTeams, etc.)
 * - Custom factory methods and configurations
 * - Data consistency and business rule compliance
 *
 * These tests verify that the EntityFactory generates consistent,
 * realistic test data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\EntityType\EntityFactory
 */
describe('EntityFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates entity with correct default attributes', function () {
            $entity = EntityModel::factory()->make();
            
            expect($entity->required_field)->toBeString();
            expect($entity->status)->toBeInstanceOf(EntityStatus::class);
            expect($entity->nullable_field)->toBeNull(); // Default nullable state
        });

        test('generates realistic business data', function () {
            $entity = EntityModel::factory()->make();
            
            expect($entity->business_field)->toBeString();
            expect(strlen($entity->business_field))->toBeGreaterThan(3);
            expect($entity->business_field)->toBe(ucwords($entity->business_field));
        });
    });

    describe('factory state methods', function () {
        test('active state works correctly', function () {
            $entity = EntityModel::factory()->active()->make();
            
            expect($entity->status)->toBe(EntityStatus::Active);
        });

        test('inactive state works correctly', function () {
            $entity = EntityModel::factory()->inactive()->make();
            
            expect($entity->status)->toBe(EntityStatus::Inactive);
        });

        test('with relationships state works correctly', function () {
            $entity = EntityModel::factory()->withRelatedModels()->create();
            
            expect($entity->relatedModels)->not->toBeEmpty();
            expect($entity->relatedModels->first())->toBeInstanceOf(RelatedModel::class);
        });
    });

    describe('data consistency and integrity', function () {
        test('generates unique data across multiple instances', function () {
            $entity1 = EntityModel::factory()->make();
            $entity2 = EntityModel::factory()->make();
            
            expect($entity1->unique_field)->not->toBe($entity2->unique_field);
        });

        test('generates consistent data format', function () {
            $entities = collect(range(1, 5))->map(fn() => EntityModel::factory()->make());
            
            foreach ($entities as $entity) {
                expect($entity->formatted_field)->toBeString();
                expect($entity->formatted_field)->not->toBeEmpty();
                expect($entity->formatted_field)->toBe(ucwords($entity->formatted_field));
            }
        });

        test('database creation works correctly', function () {
            $entity = EntityModel::factory()->create();
            
            expect($entity->exists)->toBeTrue();
            expect($entity->id)->toBeGreaterThan(0);
        });
    });

    describe('business rule compliance', function () {
        test('enforces business constraints', function () {
            $entity = EntityModel::factory()->make();
            
            // Test domain-specific business rules
            expect($entity->business_metric)->toBeGreaterThan(0);
            expect($entity->business_metric)->toBeLessThan(1000);
        });

        test('maintains relationship integrity', function () {
            $entity = EntityModel::factory()->withRequiredRelation()->create();
            
            expect($entity->requiredRelation)->not->toBeNull();
            expect($entity->requiredRelation->entity_id)->toBe($entity->id);
        });
    });
});
```

## Factory Testing Best Practices

### ✅ ALWAYS Test in Factory Tests:
- **Default Attribute Generation**: Verify realistic default values
- **Factory State Methods**: Test all factory states (employed, suspended, etc.)
- **Relationship Creation**: Test factory relationship building
- **Data Consistency**: Verify consistent data patterns across generations
- **Business Rule Compliance**: Ensure generated data follows domain rules
- **Database Persistence**: Test that factory data persists correctly

### ❌ NEVER Test in Factory Tests:
- Model structure or trait usage (belongs in Model tests)
- Business logic methods (belongs in Action/Repository tests)
- Complex business workflows (belongs in Integration tests)
- Authorization logic (belongs in Policy/Feature tests)

## Critical Field Naming Conventions

### Time-Based Tracking Model Fields
**IMPORTANT**: All time-based tracking models use consistent field naming:

- **Start Fields**: Always `started_at` (not `injured_at`, `suspended_at`, `retired_at`)
- **End Fields**: Always `ended_at` (not `cleared_at`, `reinstated_at`, `unretired_at`)

### Factory Test Field References
When writing factory tests, always reference the correct field names:

```php
// ✅ CORRECT - Use consistent field names
test('creates injury with correct default attributes', function () {
    $injury = ManagerInjury::factory()->make();
    
    expect($injury->manager_id)->toBeInt();
    expect($injury->started_at)->toBeInstanceOf(Carbon::class);
    expect($injury->ended_at)->toBeNull(); // Default is current injury
});

// ❌ INCORRECT - Don't use inconsistent field names
test('creates injury with correct default attributes', function () {
    $injury = ManagerInjury::factory()->make();
    
    expect($injury->injured_at)->toBeInstanceOf(Carbon::class); // Wrong field name
    expect($injury->cleared_at)->toBeNull(); // Wrong field name
});
```

### Common Field Name Corrections
- `injured_at` → `started_at`
- `suspended_at` → `started_at`
- `retired_at` → `started_at`
- `cleared_at` → `ended_at`
- `reinstated_at` → `ended_at`
- `unretired_at` → `ended_at`

## Pivot Model Factory Testing Limitations

### Laravel Pivot Model ID Assertion Issues
**CRITICAL DISCOVERY**: Laravel Pivot and MorphPivot models have ID assertion limitations in factory tests.

#### Problem Description
```php
// ❌ PROBLEMATIC - Will fail for Pivot models
test('creates competitor with correct attributes', function () {
    $competitor = EventMatchCompetitor::factory()->create();
    
    expect($competitor->id)->toBeGreaterThan(0); // FAILS: Pivot models don't reliably return IDs
});
```

#### Solution: Avoid ID Assertions on Pivot Models
```php
// ✅ CORRECT - Test existence without ID assertions
test('creates competitor with correct attributes', function () {
    $competitor = EventMatchCompetitor::factory()->create();
    
    expect($competitor->exists)->toBeTrue();
    expect($competitor->event_match_id)->toBeInt();
    expect($competitor->competitor_id)->toBeInt();
    expect($competitor->competitor_type)->toBeString();
    // Note: Pivot models don't reliably return IDs after create() due to Laravel limitations
});
```

#### Affected Pivot Models
- **EventMatchCompetitor** (Pivot model)
- **TagTeamWrestler** (Pivot model) 
- **EventMatchWinner** (Standard model - ID assertions work)
- **EventMatchLoser** (Standard model - ID assertions work)

#### Standard vs Pivot Model Testing
```php
// Standard models (EventMatchWinner, EventMatchLoser) - ID assertions work
test('creates winner with valid ID', function () {
    $winner = EventMatchWinner::factory()->create();
    
    expect($winner->id)->toBeGreaterThan(0); // ✅ Works for standard models
    expect($winner->exists)->toBeTrue();
});

// Pivot models (EventMatchCompetitor, TagTeamWrestler) - avoid ID assertions
test('creates competitor with valid data', function () {
    $competitor = EventMatchCompetitor::factory()->create();
    
    expect($competitor->exists)->toBeTrue(); // ✅ Use exists instead
    expect($competitor->event_match_id)->toBeInt();
    // Note: Pivot models don't reliably return IDs after create() due to Laravel limitations
});
```

#### Architecture Decision: EventMatchWinner vs EventMatchCompetitor
- **EventMatchWinner/Loser**: Standard models with reliable ID handling
- **EventMatchCompetitor**: Pivot model with Laravel ID limitations
- **Design Rationale**: Winner/loser architecture required standard models for proper relationship handling

## Why These ARE Appropriate Unit Tests

### ✅ Isolated Functionality Testing:
- Test factory data generation in isolation
- No complex business workflows or integrations
- Focus on single responsibility (data creation)

### ✅ Realistic Data Verification:
- Ensure factories generate business-appropriate data
- Verify data consistency across multiple generations
- Test edge cases and state variations

### ✅ Support Comprehensive Testing:
- Enable other tests to use realistic, consistent data
- Prevent test data from becoming stale or unrealistic
- Ensure factory states match business requirements

## Factory Testing Value Proposition

**Database Factory tests provide HIGH VALUE because they:**

1. **Test Genuine Business Logic** - Data generation patterns matter for realistic testing
2. **Are True Unit Tests** - Test isolated factory functionality without dependencies  
3. **Provide High ROI** - Enable comprehensive testing scenarios across the application
4. **Have Low Maintenance** - Data patterns are stable and rarely change
5. **Prevent Regression** - Catch when factories generate unrealistic or inconsistent data
6. **Support Test Ecosystem** - All other tests depend on realistic factory data

**Unlike deleted reflection-based Livewire tests, Database Factory tests verify actual business functionality that supports the entire testing ecosystem.**

## Current Factory Test Status

**✅ RECOMMENDATION: KEEP ALL 37 Database Factory Tests**

**Location**: `tests/Unit/Database/Factories/`
**Coverage**: 100% of application factories (37/37)
**Value Assessment**: HIGH - Essential for comprehensive testing
**Maintenance Cost**: LOW - Stable data generation patterns
**Business Impact**: CRITICAL - Supports all other testing levels

## Related Documentation
- [Unit Testing Guidelines](unit-testing.md)
- [Testing Overview](overview.md)
- [Model Testing](unit-testing.md#model-testing-architecture--guidelines)