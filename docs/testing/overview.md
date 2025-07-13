# Testing Architecture & Overview

Comprehensive testing guidelines for the Ringside application using Pest PHP framework.

## Testing Framework & Structure
- **Framework**: Pest PHP with Laravel plugin for all testing
- **Coverage Requirement**: 100% test coverage maintained
- **Parallel Execution**: Tests run in parallel for performance
- **Database**: SQLite in-memory database for fast test execution
- **Recent Update**: Database seeder tests moved from Unit to Integration category (2024) for proper test classification

## Testing Levels & Distribution (Testing Pyramid)

### Unit Tests (70% of test suite)
- **Location**: `tests/Unit/`
- **Purpose**: Test individual classes, methods, and business logic in isolation
- **Scope**: Single class or method testing with mocked dependencies
- **Examples**: Model methods, Action classes, Repository methods, Builder scopes
- **Naming**: Match class structure (e.g., `WrestlerTest.php` for `Wrestler` model)

### Integration Tests (20% of test suite)
- **Location**: `tests/Integration/`
- **Purpose**: Test interaction between multiple components within a domain
- **Scope**: Multiple classes working together, database interactions, complex workflows
- **Examples**: Repository + Model interactions, Action + Repository workflows, Database seeder testing
- **Database Seeders**: All seeder tests are classified as integration tests due to their multi-system nature

### Feature Tests (8% of test suite)
- **Location**: `tests/Feature/Http/Controllers/`
- **Purpose**: Test complete user workflows and HTTP endpoints
- **Scope**: Full request-response cycle, authentication, authorization, view rendering
- **Examples**: Controller endpoints, form submissions, user authentication flows

### Browser Tests (2% of test suite)
- **Location**: `tests/Browser/` (when needed)
- **Purpose**: End-to-end testing of JavaScript interactions and complex user workflows
- **Scope**: Full browser automation testing critical user paths
- **Framework**: Laravel Dusk for browser automation

## Test Categories & Guidelines

### Model Unit Tests - Data Layer Only

**✅ What TO Test in Model Tests:**
- **Fillable Properties**: `getFillable()` returns correct array of fillable attributes
- **Casts**: `getCasts()` returns correct casting configuration (enums, value objects, etc.)
- **Custom Builder Class**: `query()` returns correct custom builder instance (e.g., `WrestlerBuilder`)
- **Trait Usage**: Model uses expected traits via `usesTrait()` assertions
- **Default Values**: Default attribute values are set correctly
- **Basic Attribute Handling**: Value objects and casting work correctly

**❌ What NOT to Test in Model Tests:**
- Business logic methods (`ensureCanBeXXX()`, `canBeXXX()`) - test these in Actions/Repositories
- Validation rules enforcement - test these in Form/Action tests  
- Complex business workflows - test these in Integration tests
- Trait functionality itself - test traits independently in `tests/Unit/Models/Concerns/`
- Relationships behavior - test relationship logic in Integration tests
- Database operations - test these in Repository/Integration tests
- **Factory-related code** - test factories in `tests/Unit/Database/Factories/XFactoryTest.php`

### Database Factory Tests - Essential Unit Tests

**CRITICAL PRINCIPLE**: Database Factory tests provide genuine business value and should REMAIN as Unit tests.

**Why Factory Tests Are Valuable:**
- Test actual business logic in data generation
- Verify business rule compliance
- Support comprehensive testing ecosystem
- Low maintenance with high ROI
- Prevent regression in test data quality

See [Factory Testing Guidelines](factory-testing.md) for complete documentation.

### Livewire Component Testing

**CRITICAL RULE**: Separate Unit and Integration concerns for Livewire components

**Unit Tests** (`tests/Unit/Livewire/`):
- Class structure, inheritance, trait integration
- Property existence and method signatures
- Basic type checking and configuration

**Integration Tests** (`tests/Integration/Livewire/`):
- Validation rules behavior and business logic
- Data transformation and protected method testing
- Component interactions and lifecycle management
- Authorization and business action integration

See [Livewire Testing Guidelines](livewire-testing.md) for detailed standards.

## Test Organization Standards

### Directory Structure Standard
**CRITICAL PRINCIPLE**: Test directory structure must EXACTLY mirror the app directory structure at ALL testing levels.

```
app/{Directory}/{ClassName}.php
↓
tests/Unit/{Directory}/{ClassName}Test.php
tests/Integration/{Directory}/{ClassName}IntegrationTest.php
```

### Current Test Directory Structure
```
tests/
├── Unit/                           # Unit tests (70% of suite)
│   ├── Models/                     # Model unit tests
│   ├── Repositories/               # Repository unit tests  
│   ├── Policies/                   # Policy unit tests
│   ├── Rules/                      # Validation rule unit tests
│   ├── Livewire/                   # Livewire component unit tests
│   └── Database/Factories/         # Factory unit tests
├── Integration/                    # Integration tests (20% of suite)
│   ├── Database/Seeders/           # Database seeder integration tests
│   ├── Livewire/                   # Livewire component integration tests
│   └── [Domain]/                   # Domain-specific integration tests
├── Feature/                        # Feature tests (8% of suite)
│   └── Http/Controllers/           # Controller feature tests
└── Browser/                        # Browser tests (2% of suite)
    └── [UserFlows]/                # End-to-end browser tests
```

### Test File Naming Conventions
- **Unit Tests**: `{ClassName}Test.php`
- **Integration Tests**: `{ClassName}IntegrationTest.php` or `{ClassName}Test.php` (for seeder tests)
- **Feature Tests**: `{ControllerName}ControllerTest.php`
- **Factory Tests**: `{FactoryName}FactoryTest.php`
- **Seeder Tests**: `{SeederName}Test.php` (located in `tests/Integration/Database/Seeders/`)

## Test Structure & Organization

### Arrange-Act-Assert (AAA) Pattern
All tests must follow the clear AAA pattern with visual separation:

```php
test('wrestler can be employed successfully', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create(['name' => 'John Doe']);
    $employmentDate = now()->subDays(30);
    
    // Act
    $action = EmployAction::make()
        ->handle($wrestler, $employmentDate);
    
    // Assert
    expect($wrestler->fresh()->isEmployed())->toBeTrue();
    expect($wrestler->currentEmployment->started_at)->toEqual($employmentDate);
});
```

### Code Standards for Tests
- **Import Classes**: Always import classes instead of using FQCN
- **Named Routes**: Use Laravel's named routes instead of hardcoded URLs
- **Realistic Data**: Use business-appropriate factory data
- **Method Chaining**: Each method call on separate lines for clarity

## Critical Test Failure Protocol

**🚨 MANDATORY PROCEDURE FOR TEST FAILURES:**

1. **Immediate Notification**: If any test fails during development work, Claude must immediately stop all code changes and notify the user
2. **No Autonomous Fixes**: Claude is NOT allowed to modify code to fix failing tests without explicit user approval
3. **Failure Analysis**: Provide detailed analysis of which tests failed, error messages, and potential causes
4. **User Collaboration**: Wait for user approval before making any code changes to address test failures

## Test Quality Standards

### Required Elements
- AAA pattern with clear comment blocks
- Bidirectional `@see` documentation between classes and tests
- Consistent describe block organization
- Complete method coverage for public APIs
- Proper factory usage for realistic test data
- Edge case coverage and error handling

### Coverage Requirements
- **Unit Tests**: 100% coverage of public methods and business logic
- **Integration Tests**: 100% coverage of component interactions
- **Feature Tests**: 100% coverage of HTTP endpoints and workflows
- **Factory Tests**: 100% coverage of all application factories

## Performance Considerations

### Test Execution Optimization
- Use SQLite in-memory database for fast execution
- Parallel test execution where possible
- Mock external dependencies in Unit tests
- Use real data in Integration tests sparingly

### Maintenance Strategies
- Regular test value assessment to prevent low-ROI test accumulation
- Consistent refactoring of test structure as application evolves
- Documentation updates when testing patterns change

## Related Documentation

### Detailed Testing Guides
- [Unit Testing Guidelines](unit-testing.md)
- [Integration Testing Standards](integration-testing.md)
- [Feature Testing Patterns](feature-testing.md)
- [Factory Testing Guidelines](factory-testing.md)
- [Livewire Testing Standards](livewire-testing.md)
- [Browser Testing Setup](browser-testing.md)

### Project Documentation
- [Livewire Test Reorganization Project](../projects/livewire-reorganization.md)
- [Development Commands](../development/commands.md)

### Architecture Documentation
- [Repository Pattern](../architecture/repository-pattern.md)
- [Actions Pattern](../architecture/actions-pattern.md)
- [Domain Structure](../architecture/domain-structure.md)