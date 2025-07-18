# Database & Seeder Integration

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

### Database Seeder Integration Testing

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
