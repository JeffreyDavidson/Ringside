# Policy Testing Standards

## Policy Test Structure
```php
describe('EntityPolicy Unit Tests', function () {
    beforeEach(function () {
        $this->policy = new EntityPolicy();
        $this->admin = administrator();
        $this->basicUser = basicUser();
    });

    describe('before hook behavior', function () {
        test('administrators bypass all authorization checks', function () {
            // Test admin bypass
        });

        test('basic users continue to individual method checks', function () {
            // Test basic user flow
        });
    });
});
```

## Policy Testing Requirements
- **Before Hook**: Test administrator bypass functionality
- **Individual Methods**: Test all policy methods return false for basic users
- **Laravel Gate**: Test Gate facade integration
- **Consistency**: Test all methods follow same pattern
