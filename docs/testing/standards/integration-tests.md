# Integration Testing Standards

Guidelines for testing component interactions and multi-system workflows.

## Overview

Integration tests verify that multiple components work together correctly (20% of test suite). They test interactions between classes, database operations, and complex workflows.

## Integration Test Scope

### Purpose
- **Component Interaction**: Test how multiple components work together
- **Database Operations**: Test with real database interactions
- **Complex Workflows**: Test multi-step business processes
- **Data Flow**: Verify data flows correctly between components

### Location
- **Directory**: `tests/Integration/`
- **Structure**: Must exactly mirror app directory structure
- **Naming**: `{ClassName}IntegrationTest.php` or `{ClassName}Test.php`
- **Seeder Tests**: `tests/Integration/Database/Seeders/{SeederName}Test.php`

## Livewire Component Integration

### Component Test Structure
```php
describe('ComponentName Integration Tests', function () {
    beforeEach(function () {
        $this->admin = administrator();
        $this->basicUser = basicUser();
    });

    describe('component configuration', function () {
        test('returns correct form class', function () {
            // Test abstract method implementations
        });
    });

    describe('component state management', function () {
        test('manages component state correctly', function () {
            // Test component properties and interactions
        });
    });

    describe('form integration', function () {
        test('handles form submission correctly', function () {
            // Test form submission workflow
        });
    });
});
```

### Form Component Testing
```php
describe('ComponentForm Integration Tests', function () {
    describe('validation workflow', function () {
        test('validates required fields correctly', function () {
            // Arrange
            $component = Livewire::test(ComponentForm::class);
            
            // Act
            $component->set('field_name', '')
                     ->call('submit');
            
            // Assert
            $component->assertHasErrors(['field_name' => 'required']);
        });
    });

    describe('data processing', function () {
        test('creates model with valid data', function () {
            // Arrange
            $component = Livewire::test(ComponentForm::class);
            
            // Act
            $component->set('field', 'value')
                     ->call('submit');
            
            // Assert
            expect(Model::where('field', 'value')->exists())->toBeTrue();
        });
    });
});
```

### Modal Component Testing
```php
describe('ComponentModal Integration Tests', function () {
    describe('modal state management', function () {
        test('opens and closes modal correctly', function () {
            // Arrange
            $component = Livewire::test(ComponentModal::class);
            
            // Act
            $component->call('openModal');
            
            // Assert
            expect($component->instance()->isModalOpen)->toBeTrue();
        });
    });

    describe('form integration', function () {
        test('handles form submission and closes modal', function () {
            // Arrange
            $component = Livewire::test(ComponentModal::class);
            
            // Act
            $component->call('openModal')
                     ->set('form.field', 'value')
                     ->call('form.submit');
            
            // Assert
            expect(Model::where('field', 'value')->exists())->toBeTrue();
            expect($component->instance()->isModalOpen)->toBeFalse();
        });
    });
});
```

## Action Integration Testing

### Action Test Organization
```php
describe('ActionName Integration Tests', function () {
    beforeEach(function () {
        $this->repository = app(EntityRepository::class);
        $this->action = app(ActionName::class);
    });

    describe('successful action execution', function () {
        test('performs action with database persistence', function () {
            // Arrange
            $entity = Entity::factory()->create();
            
            // Act
            $result = $this->action->handle($entity, now());
            
            // Assert
            expect($result)->toBeInstanceOf(Entity::class);
            $this->assertDatabaseHas('entities', [
                'id' => $entity->id,
                'status' => 'updated',
            ]);
        });
    });

    describe('error handling', function () {
        test('handles business rule violations', function () {
            // Arrange
            $entity = Entity::factory()->invalidState()->create();
            
            // Act & Assert
            expect(fn() => $this->action->handle($entity))
                ->toThrow(BusinessRuleException::class);
        });
    });
});
```

### Repository Integration Testing
```php
describe('EntityRepository Integration Tests', function () {
    beforeEach(function () {
        $this->repository = app(EntityRepository::class);
    });

    describe('employment management', function () {
        test('creates employment with proper database persistence', function () {
            // Arrange
            $entity = Entity::factory()->create();
            $employmentDate = now();
            
            // Act
            $result = $this->repository->createEmployment($entity, $employmentDate);
            
            // Assert
            expect($result->fresh()->isEmployed())->toBeTrue();
            $this->assertDatabaseHas('entity_employments', [
                'entity_id' => $entity->id,
                'started_at' => $employmentDate,
            ]);
        });
    });

    describe('relationship management', function () {
        test('manages entity relationships correctly', function () {
            // Arrange
            $entity = Entity::factory()->create();
            $related = RelatedEntity::factory()->create();
            
            // Act
            $this->repository->addRelationship($entity, $related);
            
            // Assert
            expect($entity->fresh()->relatedEntities)->toContain($related);
        });
    });
});
```

## Validation Rule Integration

### Database-Heavy Rule Testing
```php
describe('RuleName Integration Tests', function () {
    describe('validation with database queries', function () {
        test('passes validation when entity is active', function () {
            // Arrange
            $activeEntity = Entity::factory()->active()->create();
            $validator = Validator::make(['entity_id' => $activeEntity->id], [
                'entity_id' => [new RuleName()],
            ]);

            // Act & Assert
            expect($validator->passes())->toBeTrue();
        });

        test('fails validation when entity is inactive', function () {
            // Arrange
            $inactiveEntity = Entity::factory()->inactive()->create();
            $validator = Validator::make(['entity_id' => $inactiveEntity->id], [
                'entity_id' => [new RuleName()],
            ]);

            // Act & Assert
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->first('entity_id'))
                ->toBe('Expected error message.');
        });
    });
});
```

### DataAware Rule Testing
```php
describe('DataAwareRule Integration Tests', function () {
    test('validates with form data context', function () {
        // Arrange
        $validator = Validator::make([
            'field1' => 'value1',
            'field2' => 'value2',
            'target_field' => 'target_value',
        ], [
            'target_field' => [new DataAwareRuleName()],
        ]);

        // Act & Assert
        expect($validator->passes())->toBeTrue();
    });
});
```

## Database Integration Testing

### Model Relationship Testing
```php
describe('Model Relationship Integration Tests', function () {
    test('wrestler employment relationship works correctly', function () {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        $employment = WrestlerEmployment::factory()->create([
            'wrestler_id' => $wrestler->id,
            'started_at' => now(),
            'ended_at' => null,
        ]);
        
        // Act
        $currentEmployment = $wrestler->currentEmployment;
        
        // Assert
        expect($currentEmployment)->not->toBeNull();
        expect($currentEmployment->id)->toBe($employment->id);
    });

    test('polymorphic championship relationship works correctly', function () {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        $title = Title::factory()->create();
        $championship = TitleChampionship::factory()->create([
            'title_id' => $title->id,
            'champion_type' => 'wrestler',
            'champion_id' => $wrestler->id,
        ]);
        
        // Act
        $champion = $championship->champion;
        
        // Assert
        expect($champion)->toBeInstanceOf(Wrestler::class);
        expect($champion->id)->toBe($wrestler->id);
    });
});
```

### Query Builder Integration
```php
describe('Query Builder Integration Tests', function () {
    test('complex query with multiple scopes works correctly', function () {
        // Arrange
        $employedWrestler = Wrestler::factory()->employed()->create();
        $unemployedWrestler = Wrestler::factory()->unemployed()->create();
        $retiredWrestler = Wrestler::factory()->retired()->create();
        
        // Act
        $bookableWrestlers = Wrestler::query()
            ->employed()
            ->notRetired()
            ->bookable()
            ->get();
        
        // Assert
        expect($bookableWrestlers)->toHaveCount(1);
        expect($bookableWrestlers->first()->id)->toBe($employedWrestler->id);
    });
});
```

## Event Integration Testing

### Event Dispatching
```php
describe('Event Integration Tests', function () {
    test('action dispatches correct events', function () {
        // Arrange
        Event::fake();
        $wrestler = Wrestler::factory()->create();
        
        // Act
        EmployWrestlerAction::run($wrestler, now());
        
        // Assert
        Event::assertDispatched(WrestlerEmployed::class, function ($event) use ($wrestler) {
            return $event->wrestler->id === $wrestler->id;
        });
    });
});
```

### Livewire Event Testing
```php
describe('Livewire Event Integration Tests', function () {
    test('component dispatches events correctly', function () {
        // Arrange
        $component = Livewire::test(WrestlerForm::class);
        
        // Act
        $component->set('name', 'Test Wrestler')
                 ->call('submit');
        
        // Assert
        $component->assertDispatched('wrestler-created');
    });
});
```

## Database Seeder Integration Testing

### Seeder Test Structure
```php
describe('SeederName Integration Tests', function () {
    describe('seeder execution', function () {
        test('successfully runs without errors', function () {
            // Act & Assert - Should not throw any exceptions
            expect(fn() => Artisan::call('db:seed', ['--class' => 'SeederName']))
                ->not->toThrow(Exception::class);
        });

        test('creates exact number of records', function () {
            // Arrange & Act
            Artisan::call('db:seed', ['--class' => 'SeederName']);

            // Assert
            assertDatabaseCount('table_name', $expectedCount);
        });
    });

    describe('required data creation', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'SeederName']);
        });

        test('creates required records with correct data', function () {
            // Assert
            assertDatabaseHas('table_name', ['name' => 'Expected Name', 'slug' => 'expected-slug']);
        });
    });

    describe('data consistency', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'SeederName']);
        });

        test('all records have unique identifiers', function () {
            // Arrange
            $records = Model::all();

            // Assert
            expect($records->pluck('unique_field')->unique())->toHaveCount($records->count());
        });

        test('seeder can be run multiple times safely', function () {
            // Act
            Artisan::call('db:seed', ['--class' => 'SeederName']);
            Artisan::call('db:seed', ['--class' => 'SeederName']);

            // Assert - Should still have exactly expected number of records
            assertDatabaseCount('table_name', $expectedCount);
        });
    });
});
```

### Seeder Testing Requirements
- **Multi-System Integration**: Seeders test Artisan + Database + Eloquent integration
- **Data Validation**: Verify correct data structure and relationships
- **Idempotency**: Test that seeders can run multiple times safely
- **Error Handling**: Verify seeders handle errors gracefully
- **Performance**: Test seeder execution time for large datasets

### Why Seeders Are Integration Tests
- **Multiple Components**: Test interaction between Artisan commands, seeders, and database
- **Database Operations**: Require real database interactions and persistence
- **System Workflows**: Test complete data seeding workflows
- **External Dependencies**: Depend on database state and Artisan framework

### Common Seeder Test Issues
```php
// ✅ CORRECT - Test actual seeder behavior
assertDatabaseHas('match_types', ['name' => 'Tag Team', 'slug' => 'tag-team']);

// ❌ INCORRECT - Test expectations don't match seeder implementation  
assertDatabaseHas('match_types', ['name' => 'Tag Team', 'slug' => 'tagteam']);

// ✅ CORRECT - Handle idempotency issues in seeder
MatchType::updateOrCreate(['slug' => 'tag-team'], ['name' => 'Tag Team']);

// ❌ INCORRECT - Create duplicates on multiple runs
MatchType::create(['name' => 'Tag Team', 'slug' => 'tag-team']);
```

## Cache Integration Testing

### Cache Behavior Testing
```php
describe('Cache Integration Tests', function () {
    test('repository uses cache correctly', function () {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        $repository = app(WrestlerRepository::class);
        
        // Act - First call should hit database
        $result1 = $repository->findWithCache($wrestler->id);
        
        // Act - Second call should hit cache
        $result2 = $repository->findWithCache($wrestler->id);
        
        // Assert
        expect($result1->id)->toBe($wrestler->id);
        expect($result2->id)->toBe($wrestler->id);
        expect(Cache::has("wrestler.{$wrestler->id}"))->toBeTrue();
    });
});
```

## Integration Test Quality Standards

### Database Assertions
```php
// ✅ CORRECT - Assert both model state and database persistence
expect($entity->fresh()->isEmployed())->toBeTrue();
$this->assertDatabaseHas('entity_employments', [
    'entity_id' => $entity->id,
    'started_at' => $employmentDate,
]);

// ❌ AVOID - Only testing model state
expect($entity->fresh()->isEmployed())->toBeTrue();
```

### Factory Usage
```php
// ✅ CORRECT - Use factories for realistic test data
$wrestler = Wrestler::factory()->employed()->create();
$tagTeam = TagTeam::factory()->withWrestlers(2)->create();

// ❌ AVOID - Manual model creation
$wrestler = new Wrestler(['name' => 'Test']);
$wrestler->save();
```

### Relationship Testing
```php
// ✅ CORRECT - Test actual relationship loading
expect($wrestler->currentEmployment)->not->toBeNull();
expect($wrestler->relationLoaded('currentEmployment'))->toBeTrue();

// ❌ AVOID - Testing relationship existence only
expect($wrestler->currentEmployment())->toBeInstanceOf(HasOne::class);
```

## Performance Considerations

### Integration Test Performance
- **Selective Database Usage**: Only use database when testing interactions
- **Transaction Rollbacks**: Use database transactions for isolation
- **Minimal Data**: Create only necessary test data
- **Eager Loading**: Test eager loading to prevent N+1 queries

### Test Optimization
```php
// ✅ CORRECT - Use database transactions
use Illuminate\Foundation\Testing\DatabaseTransactions;

class IntegrationTest extends TestCase
{
    use DatabaseTransactions;
    
    // Test methods
}

// ✅ CORRECT - Test eager loading
test('eager loads relationships correctly', function () {
    $wrestlers = Wrestler::factory()->count(3)->create();
    
    $results = Wrestler::with('currentEmployment')->get();
    
    foreach ($results as $wrestler) {
        expect($wrestler->relationLoaded('currentEmployment'))->toBeTrue();
    }
});
```

## Common Integration Test Patterns

### Testing Component Interactions
```php
test('action and repository work together correctly', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $action = app(EmployWrestlerAction::class);
    
    // Act
    $result = $action->handle($wrestler, now());
    
    // Assert
    expect($result->isEmployed())->toBeTrue();
    expect($result->currentEmployment)->not->toBeNull();
});
```

### Testing Complex Workflows
```php
test('complete wrestler employment workflow', function () {
    // Arrange
    $wrestler = Wrestler::factory()->unemployed()->create();
    
    // Act - Multi-step workflow
    $employment = EmployWrestlerAction::run($wrestler, now());
    $assignment = AssignToStableAction::run($wrestler, $stable);
    $booking = BookForMatchAction::run($wrestler, $match);
    
    // Assert - Complete workflow state
    expect($wrestler->fresh()->isEmployed())->toBeTrue();
    expect($wrestler->fresh()->currentStable)->not->toBeNull();
    expect($wrestler->fresh()->upcomingMatches)->toHaveCount(1);
});
```

### Testing Error Propagation
```php
test('errors propagate correctly through component stack', function () {
    // Arrange
    $wrestler = Wrestler::factory()->retired()->create();
    
    // Act & Assert
    expect(fn() => EmployWrestlerAction::run($wrestler, now()))
        ->toThrow(CannotBeEmployedException::class);
});
```

## Troubleshooting Integration Tests

### Common Issues
- **Database State**: Ensure proper database cleanup between tests
- **Factory Dependencies**: Verify factory relationships work correctly
- **Event Conflicts**: Use Event::fake() to avoid event side effects
- **Cache Issues**: Clear cache between tests when needed

### Debug Techniques
```php
// Debug database queries
DB::enableQueryLog();
// Run test code
dump(DB::getQueryLog());

// Debug model state
dump($model->fresh()->toArray());

// Debug relationships
dump($model->relationLoaded('relationship'));
```

This comprehensive integration testing guide ensures proper component interaction testing while maintaining performance and reliability standards.