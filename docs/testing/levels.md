# Testing Levels & Distribution (Testing Pyramid)

### Unit Tests (70% of test suite)
- **Location**: `tests/Unit/`
- **Purpose**: Test individual classes, methods, and business logic in isolation
- **Scope**: Single class or method testing with mocked dependencies
- **Examples**: Model methods, Rule logic, Repository methods, Builder scopes

### Integration Tests (20% of test suite)
- **Location**: `tests/Integration/`
- **Purpose**: Test interaction between multiple components within a domain
- **Scope**: Multiple classes working together, database interactions, workflows
- **Examples**: Repository + Model interactions, Action + Repository workflows

### Feature Tests (8% of test suite)
- **Location**: `tests/Feature/Http/Controllers/`
- **Purpose**: Test complete user workflows and HTTP endpoints
- **Scope**: Full request-response cycle, authentication, authorization
- **Examples**: Controller endpoints, form submissions, user flows

### Browser Tests (2% of test suite)
- **Location**: `tests/Browser/` (when needed)
- **Purpose**: End-to-end testing of JavaScript interactions
- **Scope**: Full browser automation testing critical user paths
- **Framework**: Laravel Dusk for browser automation
