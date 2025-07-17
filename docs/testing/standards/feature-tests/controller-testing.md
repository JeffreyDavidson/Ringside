# Controller Testing Standards

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
