# Feature Testing Standards

Guidelines for testing complete application features and business workflows.

## Overview

Feature tests verify complete application features and business workflows (8% of test suite). They test HTTP endpoints, authentication, authorization, and complete user journeys.

## Feature Test Scope

### Purpose
- **HTTP Layer**: Test complete request-response cycle
- **Authentication**: Test user authentication workflows
- **Authorization**: Test access control and permissions
- **User Journeys**: Test complete user workflows
- **View Rendering**: Test view rendering and data passing

### Location
- **Directory**: `tests/Feature/Http/Controllers/`
- **Structure**: Must exactly mirror app directory structure
- **Naming**: `{ControllerName}ControllerTest.php`

## Controller Testing Standards

### Required Test Structure
```php
describe('method_name', function () {
    beforeEach(function () {
        // Setup resources if needed for show methods
        $this->resource = Resource::factory()->create();
    });

    test('happy path test', function () {
        // Test successful access with proper authorization
    });

    test('authorization tests', function () {
        // Test forbidden/redirect scenarios
    });

    test('edge case tests', function () {
        // Test 404, malformed input, etc.
    });
});
```

### Authorization Test Patterns

#### Pattern A: Public Access
```php
test('administrators can access dashboard', function () {
    actingAs(administrator())->get(route('dashboard'))->assertOk();
});

test('basic users can access dashboard', function () {
    actingAs(basicUser())->get(route('dashboard'))->assertOk();
});

test('guests cannot access dashboard', function () {
    get(route('dashboard'))->assertRedirect(route('login'));
});
```

#### Pattern B: Admin + Ownership
```php
test('administrators can view any resource', function () {
    actingAs(administrator())->get(action([Controller::class, 'show'], $resource))->assertOk();
});

test('basic users can view their own resource', function () {
    $resource = Resource::factory()->for($user = basicUser())->create();
    actingAs($user)->get(action([Controller::class, 'show'], $resource))->assertOk();
});

test('basic users cannot view others resources', function () {
    $resource = Resource::factory()->for(User::factory())->create();
    actingAs(basicUser())->get(action([Controller::class, 'show'], $resource))->assertForbidden();
});
```

#### Pattern C: Admin Only
```php
test('administrators can access resource', function () {
    actingAs(administrator())->get(action([Controller::class, 'index']))->assertOk();
});

test('basic users cannot access resource', function () {
    actingAs(basicUser())->get(action([Controller::class, 'index']))->assertForbidden();
});
```

## Standard Feature Test Assertions

### HTTP Response Testing
```php
// Status assertions
$response->assertOk();
$response->assertForbidden();
$response->assertNotFound();
$response->assertRedirect(route('login'));

// View assertions
$response->assertViewIs('expected.view');
$response->assertViewHas('variable', $expectedValue);

// Content assertions
$response->assertSee('Expected Text');
$response->assertDontSee('Unexpected Text');
```

### Livewire Component Testing
```php
// Component presence
$response->assertSeeLivewire(ExpectedComponent::class);

// Component state
Livewire::test(Component::class)
    ->assertSet('property', 'value')
    ->assertDispatched('event-name');
```

## Required Controller Test Coverage

### All Controller Methods Must Test:
1. **Happy Path**: Successful response for authorized users
2. **Authorization**: Forbidden response for unauthorized users
3. **Authentication**: Redirect to login for guests
4. **View Data**: Correct view and data passed to templates
5. **404 Testing**: Not found responses for invalid resources

### Example Controller Test
```php
<?php

declare(strict_types=1);

use App\Http\Controllers\EventsController;
use App\Models\Events\Event;

/**
 * Feature tests for EventsController.
 * 
 * @see \App\Http\Controllers\EventsController
 */
describe('EventsController Feature Tests', function () {
    describe('index', function () {
        test('administrators can view events list', function () {
            // Arrange
            $admin = administrator();
            
            // Act
            $response = actingAs($admin)
                ->get(route('events.index'));
            
            // Assert
            $response->assertOk();
            $response->assertViewIs('events.index');
            $response->assertSeeLivewire(EventsTable::class);
        });

        test('basic users cannot view events list', function () {
            // Arrange
            $user = basicUser();
            
            // Act
            $response = actingAs($user)
                ->get(route('events.index'));
            
            // Assert
            $response->assertForbidden();
        });

        test('guests are redirected to login', function () {
            // Act
            $response = get(route('events.index'));
            
            // Assert
            $response->assertRedirect(route('login'));
        });
    });

    describe('show', function () {
        beforeEach(function () {
            $this->event = Event::factory()->create();
        });

        test('administrators can view event details', function () {
            // Arrange
            $admin = administrator();
            
            // Act
            $response = actingAs($admin)
                ->get(route('events.show', $this->event));
            
            // Assert
            $response->assertOk();
            $response->assertViewIs('events.show');
            $response->assertViewHas('event', $this->event);
        });

        test('returns 404 when event does not exist', function () {
            // Arrange
            $admin = administrator();
            
            // Act
            $response = actingAs($admin)
                ->get(route('events.show', 999999));
            
            // Assert
            $response->assertNotFound();
        });
    });
});
```

## Named Routes Usage

### Always Use Named Routes
```php
// ✅ CORRECT - Use named routes
actingAs(administrator())
    ->get(route('wrestlers.show', $wrestler))
    ->assertOk();

// ❌ INCORRECT - Hardcoded URLs
actingAs(administrator())
    ->get("/wrestlers/{$wrestler->id}")
    ->assertOk();
```

### Route Parameter Testing
```php
// Test with model binding
$response = actingAs(administrator())
    ->get(route('wrestlers.show', $wrestler));

// Test with array parameters
$response = actingAs(administrator())
    ->get(route('wrestlers.edit', ['wrestler' => $wrestler->id]));
```

## Form Testing Standards

### Form Submission Testing
```php
describe('form submissions', function () {
    test('valid form submission succeeds', function () {
        // Arrange
        $admin = administrator();
        $formData = [
            'name' => 'Test Wrestler',
            'hometown' => 'Test City',
            'weight' => 200,
        ];
        
        // Act
        $response = actingAs($admin)
            ->post(route('wrestlers.store'), $formData);
        
        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        expect(Wrestler::where('name', 'Test Wrestler')->exists())->toBeTrue();
    });

    test('invalid form submission returns errors', function () {
        // Arrange
        $admin = administrator();
        $formData = ['name' => '']; // Missing required field
        
        // Act
        $response = actingAs($admin)
            ->post(route('wrestlers.store'), $formData);
        
        // Assert
        $response->assertSessionHasErrors(['name']);
    });
});
```

### File Upload Testing
```php
test('file upload works correctly', function () {
    // Arrange
    Storage::fake('public');
    $admin = administrator();
    $file = UploadedFile::fake()->image('avatar.jpg');
    
    // Act
    $response = actingAs($admin)
        ->post(route('wrestlers.store'), [
            'name' => 'Test Wrestler',
            'avatar' => $file,
        ]);
    
    // Assert
    $response->assertRedirect();
    Storage::disk('public')->assertExists('avatars/' . $file->hashName());
});
```

## Database Testing

### Database Assertions
```php
// Test database operations
$this->assertDatabaseHas('wrestlers', [
    'name' => 'Test Wrestler',
    'hometown' => 'Test City',
]);

$this->assertDatabaseMissing('wrestlers', [
    'name' => 'Deleted Wrestler',
]);

// Test soft deletes
$this->assertSoftDeleted('wrestlers', [
    'id' => $wrestler->id,
]);
```

### Model State Testing
```php
// Test model state after operations
$wrestler = Wrestler::factory()->create();

actingAs(administrator())
    ->patch(route('wrestlers.update', $wrestler), [
        'name' => 'Updated Name',
    ]);

expect($wrestler->fresh()->name)->toBe('Updated Name');
```

## Session and Flash Testing

### Session Testing
```php
// Test session data
$response->assertSessionHas('key', 'value');
$response->assertSessionHasErrors(['field']);
$response->assertSessionHasNoErrors();

// Test flash messages
$response->assertSessionHas('success', 'Operation completed successfully');
$response->assertSessionHas('error', 'Operation failed');
```

### Authentication Session Testing
```php
test('login creates authenticated session', function () {
    // Arrange
    $user = User::factory()->create();
    
    // Act
    $response = post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);
    
    // Assert
    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});
```

## API Testing Standards

### JSON API Testing
```php
describe('API endpoints', function () {
    test('API returns correct JSON structure', function () {
        // Arrange
        $admin = administrator();
        $wrestlers = Wrestler::factory()->count(3)->create();
        
        // Act
        $response = actingAs($admin)
            ->getJson(route('api.wrestlers.index'));
        
        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'hometown', 'created_at'],
            ],
            'links',
            'meta',
        ]);
    });

    test('API validates required fields', function () {
        // Arrange
        $admin = administrator();
        
        // Act
        $response = actingAs($admin)
            ->postJson(route('api.wrestlers.store'), []);
        
        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    });
});
```

## Middleware Testing

### Authentication Middleware
```php
test('protected route requires authentication', function () {
    // Act
    $response = get(route('protected.route'));
    
    // Assert
    $response->assertRedirect(route('login'));
});

test('authenticated users can access protected route', function () {
    // Arrange
    $user = User::factory()->create();
    
    // Act
    $response = actingAs($user)
        ->get(route('protected.route'));
    
    // Assert
    $response->assertOk();
});
```

### Custom Middleware Testing
```php
test('custom middleware blocks unauthorized access', function () {
    // Arrange
    $user = basicUser();
    
    // Act
    $response = actingAs($user)
        ->get(route('admin.route'));
    
    // Assert
    $response->assertForbidden();
});
```

## Error Handling Testing

### Exception Testing
```php
test('handles exceptions gracefully', function () {
    // Arrange
    $admin = administrator();
    
    // Mock to throw exception
    $this->mock(WrestlerRepository::class)
        ->shouldReceive('create')
        ->andThrow(new Exception('Database error'));
    
    // Act
    $response = actingAs($admin)
        ->post(route('wrestlers.store'), [
            'name' => 'Test Wrestler',
        ]);
    
    // Assert
    $response->assertStatus(500);
});
```

### Validation Error Testing
```php
test('validation errors are displayed correctly', function () {
    // Arrange
    $admin = administrator();
    
    // Act
    $response = actingAs($admin)
        ->post(route('wrestlers.store'), [
            'name' => '', // Invalid
            'weight' => 'invalid', // Invalid type
        ]);
    
    // Assert
    $response->assertSessionHasErrors(['name', 'weight']);
    $response->assertRedirect();
});
```

## Performance Testing

### Response Time Testing
```php
test('page loads within acceptable time', function () {
    // Arrange
    $admin = administrator();
    Wrestler::factory()->count(100)->create();
    
    // Act
    $startTime = microtime(true);
    $response = actingAs($admin)
        ->get(route('wrestlers.index'));
    $endTime = microtime(true);
    
    // Assert
    $response->assertOk();
    expect($endTime - $startTime)->toBeLessThan(2.0); // 2 seconds max
});
```

### Database Query Testing
```php
test('avoids N+1 queries', function () {
    // Arrange
    $admin = administrator();
    Wrestler::factory()->count(10)->create();
    
    // Act & Assert
    $this->assertDatabaseQueryCount(3, function () use ($admin) {
        actingAs($admin)->get(route('wrestlers.index'));
    });
});
```

## Feature Test Quality Standards

### Code Standards
- **Import Classes**: Always import instead of using FQCN
- **Named Routes**: Use Laravel's named routes consistently
- **AAA Pattern**: Clear Arrange-Act-Assert separation
- **Descriptive Names**: Test names explain expected behavior

### Test Organization
- **Describe Blocks**: Group tests by controller method
- **BeforeEach Setup**: Use beforeEach for shared setup
- **Consistent Structure**: Follow established patterns
- **Proper Cleanup**: Clean up test data appropriately

### Assertion Standards
- **HTTP Status**: Always assert expected status codes
- **View Rendering**: Verify correct views are rendered
- **Data Passing**: Verify expected data is passed to views
- **Component Presence**: Assert expected Livewire components are loaded

## Common Feature Test Patterns

### CRUD Operation Testing
```php
// Create
test('can create resource', function () {
    $response = actingAs(administrator())
        ->post(route('resources.store'), $validData);
    
    $response->assertRedirect();
    $this->assertDatabaseHas('resources', $validData);
});

// Read
test('can view resource', function () {
    $resource = Resource::factory()->create();
    
    $response = actingAs(administrator())
        ->get(route('resources.show', $resource));
    
    $response->assertOk();
    $response->assertViewHas('resource', $resource);
});

// Update
test('can update resource', function () {
    $resource = Resource::factory()->create();
    
    $response = actingAs(administrator())
        ->patch(route('resources.update', $resource), $updateData);
    
    $response->assertRedirect();
    expect($resource->fresh()->name)->toBe($updateData['name']);
});

// Delete
test('can delete resource', function () {
    $resource = Resource::factory()->create();
    
    $response = actingAs(administrator())
        ->delete(route('resources.destroy', $resource));
    
    $response->assertRedirect();
    $this->assertSoftDeleted('resources', ['id' => $resource->id]);
});
```

## Documentation Standards

### Test Documentation
- **PHPDoc Headers**: Comprehensive scope documentation
- **Bidirectional @see**: Links between controllers and tests
- **Test Organization**: Clear describe block structure
- **Method Documentation**: Document complex test scenarios

### Cross-Reference Documentation
```php
/**
 * Feature tests for EventsController.
 * 
 * @see \App\Http\Controllers\EventsController
 */

/**
 * @see EventsController::index()
 */
test('administrators can view events list', function () {
    // Test implementation
});
```

This comprehensive feature testing guide ensures proper HTTP layer testing while maintaining clear separation of concerns between feature tests and other test levels.