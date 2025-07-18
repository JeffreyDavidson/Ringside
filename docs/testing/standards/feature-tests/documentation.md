# Documentation Standards

## Test Documentation
- **PHPDoc Headers**: Comprehensive scope documentation
- **Bidirectional @see**: Links between controllers and tests
- **Test Organization**: Clear describe block structure
- **Method Documentation**: Document complex test scenarios

## Cross-Reference Documentation
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
