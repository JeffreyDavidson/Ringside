# Assertions & Patterns

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
