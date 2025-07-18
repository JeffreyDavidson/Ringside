# Livewire Testing Guide

## Overview

This guide provides comprehensive testing strategies for Livewire components in Ringside. Our testing approach ensures reliability, maintainability, and comprehensive coverage across all component types.

## Testing Philosophy

### Testing Pyramid
1. **Unit Tests** - Individual component methods and business logic
2. **Integration Tests** - Component rendering and interaction
3. **Feature Tests** - Complete user workflows and scenarios

### Testing Principles
- **Test Behavior, Not Implementation** - Focus on what components do, not how
- **Arrange, Act, Assert** - Clear test structure for readability
- **Test Edge Cases** - Include boundary conditions and error scenarios
- **Mock External Dependencies** - Isolate component logic from external systems

## Test Structure Standards

### File Organization
```
tests/
├── Unit/
│   └── Livewire/
│       ├── Base/
│       │   ├── BaseFormTest.php
│       │   ├── BaseModalTest.php
│       │   └── BaseFormModalTest.php
│       └── {Domain}/
│           ├── Forms/
│           │   └── CreateEditFormTest.php
│           └── Modals/
│               └── FormModalTest.php
└── Integration/
    └── Livewire/
        └── {Domain}/
            ├── Forms/
            │   └── CreateEditFormTest.php
            ├── Modals/
            │   └── FormModalTest.php
            └── Components/
                └── ActionsComponentTest.php
```

### Test Naming Convention
```php
// ✅ CORRECT - Descriptive test names
test('validates required name field when creating event', function () {
    // Test implementation
});

test('loads existing event data when editing', function () {
    // Test implementation
});

test('dispatches event created event after successful submission', function () {
    // Test implementation
});

// ❌ WRONG - Vague test names
test('validation works', function () {
    // Test implementation
});

test('form submits', function () {
    // Test implementation
});
```

## Component Testing Guides

### Core Component Testing
Each component type has its own focused testing guide with patterns and examples:

- **[Form Testing](testing-forms.md)** - Comprehensive form component testing patterns
  - Validation testing strategies
  - Model binding and data transformation
  - Form submission workflows
  - Real examples from CreateEditForm implementations

- **[Modal Testing](testing-modals.md)** - Modal component testing patterns
  - Modal state management testing
  - Form integration testing
  - Event handling and dispatching
  - Real examples from FormModal implementations

- **[Table Testing](testing-tables.md)** - Table component testing patterns
  - Data display and formatting
  - Filtering and sorting functionality
  - Pagination and search features
  - Real examples from domain table implementations

- **[Actions Testing](testing-actions.md)** - Actions component testing patterns
  - Business logic testing
  - Action confirmation workflows
  - Event dispatching and handling
  - Real examples from ActionsComponent implementations

### Advanced Testing
Specialized testing guides for complex scenarios:

- **[Advanced Testing](testing-advanced.md)** - Complex testing patterns
  - Mocking external dependencies
  - Event testing and listeners
  - Database transaction testing
  - Service integration testing

- **[Performance Testing](testing-performance.md)** - Performance testing strategies
  - Memory usage testing
  - Query performance optimization
  - Load testing approaches
  - Performance benchmarking

- **[Browser Testing](testing-browser.md)** - Browser/Dusk testing integration
  - End-to-end workflow testing
  - JavaScript interaction testing
  - Real browser environment testing
  - Cross-browser compatibility

### Testing Support
Tools and utilities for effective testing:

- **[Testing Utilities](testing-utilities.md)** - Test helpers and utilities
  - Custom test helpers
  - Factory usage patterns
  - Test data management
  - Shared setup functions

- **[Best Practices](testing-best-practices.md)** - Testing best practices
  - Code organization strategies
  - Common pitfalls to avoid
  - Maintenance guidelines
  - Testing philosophy deep dive

## Quick Start Example

Here's a basic example of testing a Livewire form component:

```php
use App\Livewire\Events\Forms\CreateEditForm;
use App\Models\Events\Event;
use App\Models\Events\Venue;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
});

test('creates event with valid data', function () {
    $venue = Venue::factory()->create();
    
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->date = '2024-01-01 14:00:00';
    $form->venue_id = $venue->id;
    
    $result = $form->store();
    
    expect($result)->toBeTrue();
    expect(Event::where('name', 'Test Event')->exists())->toBeTrue();
});

test('validates required fields', function () {
    $form = new CreateEditForm();
    $form->name = '';
    
    $form->validate();
    
    expect($form->getErrorBag()->has('name'))->toBeTrue();
});
```

## Test Setup and Helpers

### Common Setup
```php
beforeEach(function () {
    // Authentication setup
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
    
    // Database setup
    $this->artisan('migrate:fresh');
    $this->seed(VenueSeeder::class);
});
```

### Custom Helpers
```php
function createEventWithVenue(array $eventData = [], array $venueData = []): Event
{
    $venue = Venue::factory()->create($venueData);
    
    return Event::factory()->create(array_merge([
        'venue_id' => $venue->id,
    ], $eventData));
}
```

## Running Tests

### Basic Commands
```bash
# Run all Livewire tests
vendor/bin/pest tests/Unit/Livewire tests/Integration/Livewire

# Run specific component tests
vendor/bin/pest tests/Integration/Livewire/Events/Forms/CreateEditFormTest.php

# Run tests with coverage
vendor/bin/pest --coverage
```

### Test Filtering
```bash
# Run only form tests
vendor/bin/pest --filter="Form"

# Run only validation tests
vendor/bin/pest --filter="validation"

# Run tests for specific domain
vendor/bin/pest tests/Integration/Livewire/Events/
```

## Getting Started

1. **Choose Your Component Type** - Select the appropriate testing guide based on your component type
2. **Review Examples** - Study the real-world examples in each guide
3. **Start Simple** - Begin with basic tests and gradually add complexity
4. **Use Utilities** - Leverage shared helpers and factories for consistent testing
5. **Follow Standards** - Maintain consistent naming and structure across tests

## Related Documentation

- [Component Architecture](../../architecture/livewire/component-architecture.md) - Understanding component structure
- [Form Patterns](../../architecture/livewire/form-patterns.md) - Form implementation patterns
- [Modal Patterns](../../architecture/livewire/modal-patterns.md) - Modal component patterns
- [Testing Standards](../../testing/standards.md) - General testing standards

## Contributing

When adding new tests:
1. Follow the established patterns in the relevant testing guide
2. Use descriptive test names that explain the behavior being tested
3. Include both positive and negative test cases
4. Add examples to the appropriate testing guide
5. Update documentation when introducing new testing patterns