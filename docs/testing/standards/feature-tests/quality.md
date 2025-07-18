# Quality & Organization

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
