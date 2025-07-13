# Browser Testing

Guide for browser-based testing using Laravel Dusk (future implementation).

## Overview

Browser testing provides end-to-end testing of user interactions and JavaScript functionality. This document outlines the planned browser testing approach for Ringside.

## Current Status

**Browser testing is currently in preparation phase**. The foundation has been established but Laravel Dusk is not yet implemented.

### Preparation Complete
- ✅ Test structure prepared in `tests/Browser/Workflows/`
- ✅ Page Object Model architecture planned
- ✅ Component testing patterns established
- ✅ Preparatory test files with TODO comments

### Implementation Pending
- ❌ Laravel Dusk installation and configuration
- ❌ Page Object Model classes implementation
- ❌ Browser test data attributes in Blade templates
- ❌ Actual browser test execution

## Browser Test Structure

### Test Categories
Browser tests are organized by workflow complexity:

#### Core Workflow Tests
- **`WrestlerManagementBrowserTest.php`** - Wrestler CRUD and relationship management
- **`AuthenticationBrowserTest.php`** - Login, logout, and session management
- **`NavigationBrowserTest.php`** - Cross-entity navigation and breadcrumbs
- **`EventTitleManagementBrowserTest.php`** - Event and title management workflows
- **`RosterManagementBrowserTest.php`** - Complex relationship management

#### Test Scope
Each browser test focuses on visual elements and user interactions:
- **Visual Elements**: Modal visibility, form field interactions, button states
- **User Interactions**: Click, type, drag-and-drop, form submissions
- **JavaScript Functionality**: Real-time search, dynamic content updates
- **Cross-browser Compatibility**: Consistent behavior across browsers

## Page Object Model Architecture

### Page Class Structure
**CRITICAL**: Browser tests must ALWAYS use Page Object classes for maintainability.

```php
<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class WrestlerIndexPage extends Page
{
    public function url(): string
    {
        return '/wrestlers';
    }

    public function elements(): array
    {
        return [
            '@create-button' => '[data-test="create-wrestler-btn"], .create-wrestler, button:contains("Create")',
            '@wrestlers-table' => 'table, .table, [role="table"]',
            '@search-input' => 'input[placeholder*="Search"], input[type="search"]',
            '@action-dropdown' => '.dropdown, [data-test="actions"]',
        ];
    }

    public function createWrestler(Browser $browser, array $data): void
    {
        $browser->click('@create-button')
                ->waitFor('.modal, [role="dialog"]')
                ->type('name', $data['name'])
                ->type('hometown', $data['hometown'])
                ->click('button[type="submit"]');
    }

    public function searchFor(Browser $browser, string $term): void
    {
        $browser->type('@search-input', $term)
                ->pause(500); // Allow search to process
    }
}
```

### Page Class Requirements
- **Location**: `tests/Browser/Pages/` directory
- **Naming**: `{Entity}{PageType}Page.php` pattern
- **Flexible Selectors**: Multiple fallback options for elements
- **Business Methods**: Domain-specific actions like `createWrestler()`
- **Wait Mechanisms**: Proper waiting for dynamic content

## Browser Test Implementation

### Test Structure Pattern
```php
describe('Workflow Name', function () {
    test('specific browser behavior test', function () {
        // Given: Test data setup
        $entity = Entity::factory()->create(['name' => 'Test Entity']);
        
        // Browser Test Implementation (Future Dusk):
        /*
        $this->browse(function (Browser $browser) use ($entity) {
            $browser->loginAs(administrator())
                    ->visit(new EntityIndexPage())
                    ->createEntity(['name' => 'New Entity'])
                    ->assertSee('New Entity')
                    ->assertPathIs('/entities');
        });
        */
        
        // Current: Basic verification for structure
        expect($entity->name)->toBe('Test Entity');
    });
});
```

### Conversion to Dusk Process
1. **Install Laravel Dusk**: `composer require --dev laravel/dusk`
2. **Configure Environment**: Set up browser automation
3. **Implement Page Objects**: Create actual Page Object classes
4. **Add Data Attributes**: Update Blade templates with test selectors
5. **Activate Tests**: Remove TODO comments and implement actual browser tests

## Data Attributes for Browser Testing

### Selector Strategy
Prepare HTML elements with proper data attributes for reliable testing:

```html
<!-- Primary actions -->
<button data-test="create-wrestler-btn" 
        class="create-wrestler" 
        id="create-btn">Create Wrestler</button>

<!-- Form elements -->
<input data-test="wrestler-name-field" 
       name="name" 
       placeholder="Enter wrestler name">

<!-- Dynamic elements -->
<div data-test="wrestler-{{ $wrestler->id }}-card">
    <button data-test="assign-manager-{{ $wrestler->id }}">
        Assign Manager
    </button>
</div>

<!-- State indicators -->
<div data-test="modal-open" class="modal show">
<div data-test="loading-spinner" style="display: none;">
```

### Selector Patterns
Page Object selectors should include multiple fallback options:

```php
'@create-button' => '[data-test="create-wrestler-btn"], .create-wrestler, button:contains("Create")',
'@wrestlers-table' => 'table, .table, [role="table"]',
'@search-input' => 'input[placeholder*="Search"], input[type="search"]',
```

## Browser Test Scope

### What TO Test in Browser Tests
- ✅ **Visual Elements**: Modal visibility, form field interactions, button states
- ✅ **User Interactions**: Click, type, drag-and-drop, form submissions
- ✅ **JavaScript Functionality**: Real-time search, dynamic content updates
- ✅ **Cross-browser Compatibility**: Consistent behavior across browsers
- ✅ **Responsive Design**: Mobile and desktop layouts
- ✅ **Accessibility**: Keyboard navigation, screen reader compatibility

### What NOT to Test in Browser Tests
- ❌ **Business Logic**: Test in Unit/Integration tests
- ❌ **Database Operations**: Test in Repository tests
- ❌ **Authorization Rules**: Test in Policy/Feature tests
- ❌ **API Endpoints**: Test in Feature tests
- ❌ **Simple CRUD Operations**: Test in Feature tests if they work reliably

## Planned Browser Test Workflows

### Wrestler Management Workflow
1. **Create Wrestler**: Navigate to form, fill data, submit, verify creation
2. **Edit Wrestler**: Open edit modal, modify data, save, verify changes
3. **Assign Manager**: Use dropdown, select manager, confirm assignment
4. **Employment Status**: Change status, verify visual updates
5. **Delete Wrestler**: Confirm deletion dialog, verify removal

### Event Management Workflow
1. **Create Event**: Date picker, venue selection, form validation
2. **Add Matches**: Competitor selection, title assignment, match rules
3. **Booking Conflicts**: Visual indicators for scheduling conflicts
4. **Event Publication**: Status changes, visual feedback

### Complex Relationship Management
1. **Tag Team Formation**: Drag-and-drop wrestler assignment
2. **Stable Management**: Multi-member selection, hierarchy management
3. **Championship Tracking**: Title assignment, reign history display
4. **Status Transitions**: Visual feedback for employment/injury/suspension changes

## Performance Considerations

### Browser Test Performance
- **Execution Speed**: Browser tests are inherently slower than other test types
- **Parallel Execution**: Consider parallel browser instances for speed
- **Test Data**: Use factories for consistent test data
- **Cleanup**: Proper test cleanup to prevent state leakage

### Browser Test Reliability
- **Timing Issues**: Use proper wait mechanisms for dynamic content
- **Flaky Tests**: Browser tests can be more prone to intermittent failures
- **Environment Dependencies**: Require browser automation setup
- **Cross-browser Testing**: Different browsers may behave differently

## Future Implementation Steps

### Phase 1: Dusk Setup
1. Install Laravel Dusk package
2. Configure browser automation environment
3. Create basic Page Object classes
4. Implement first simple browser test

### Phase 2: Core Workflows
1. Implement wrestler management tests
2. Add event management tests
3. Create authentication workflow tests
4. Add navigation and breadcrumb tests

### Phase 3: Advanced Features
1. Complex relationship management tests
2. Real-time search and filtering tests
3. Mobile responsiveness tests
4. Accessibility compliance tests

### Phase 4: CI/CD Integration
1. Configure browser tests in CI pipeline
2. Add visual regression testing
3. Implement cross-browser testing
4. Performance monitoring and optimization

## Browser Test Quality Standards

### Test Structure Requirements
- **Page Object Usage**: All browser tests must use Page Object classes
- **Clear Test Names**: Descriptive test names explaining user scenarios
- **AAA Pattern**: Arrange-Act-Assert structure with proper separation
- **Error Handling**: Graceful handling of browser-specific issues

### Maintenance Standards
- **Regular Updates**: Keep Page Objects current with UI changes
- **Selector Maintenance**: Update selectors when HTML structure changes
- **Cross-browser Validation**: Test on multiple browser engines
- **Performance Monitoring**: Monitor test execution times

## Troubleshooting

### Common Browser Test Issues
- **Element Not Found**: Selector changes or timing issues
- **Timeout Errors**: Slow page loads or JavaScript execution
- **Flaky Behavior**: Inconsistent test results
- **Environment Issues**: Browser driver version mismatches

### Debug Techniques
```php
// Screenshots for debugging
$browser->screenshot('debug-screenshot');

// Pause for manual inspection
$browser->pause(5000);

// Wait for specific conditions
$browser->waitFor('@element');
$browser->waitUntil('condition');

// Console log inspection
$browser->dump();
```

## Integration with Other Test Types

### Test Level Coordination
- **Unit Tests**: Test business logic in isolation
- **Integration Tests**: Test component interactions
- **Feature Tests**: Test HTTP layer and authentication
- **Browser Tests**: Test visual elements and user interactions

### Avoid Duplicate Testing
- Don't test business logic in browser tests
- Don't test visual elements in Feature tests
- Use appropriate test level for each concern
- Focus on what only browser tests can validate

This browser testing approach ensures comprehensive coverage of user interactions while maintaining performance and reliability standards.