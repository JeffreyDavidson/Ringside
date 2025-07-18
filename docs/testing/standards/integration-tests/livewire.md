# Livewire Integration

## Livewire Component Integration

### Component Test Structure
```php
describe('ComponentName Integration Tests', function () {
    beforeEach(function () {
        $this->admin = administrator();
        $this->basicUser = basicUser();
    });

    describe('component configuration', function () {
        test('returns correct form class', function () {
            // Test abstract method implementations
        });
    });

    describe('component state management', function () {
        test('manages component state correctly', function () {
            // Test component properties and interactions
        });
    });

    describe('form integration', function () {
        test('handles form submission correctly', function () {
            // Test form submission workflow
        });
    });
});
```

### Form Component Testing
```php
describe('ComponentForm Integration Tests', function () {
    describe('validation workflow', function () {
        test('validates required fields correctly', function () {
            // Arrange
            $component = Livewire::test(ComponentForm::class);

            // Act
            $component->set('field_name', '')
                     ->call('submit');

            // Assert
            $component->assertHasErrors(['field_name' => 'required']);
        });
    });

    describe('data processing', function () {
        test('creates model with valid data', function () {
            // Arrange
            $component = Livewire::test(ComponentForm::class);

            // Act
            $component->set('field', 'value')
                     ->call('submit');

            // Assert
            expect(Model::where('field', 'value')->exists())->toBeTrue();
        });
    });
});
```

### Modal Component Testing
```php
describe('ComponentModal Integration Tests', function () {
    describe('modal state management', function () {
        test('opens and closes modal correctly', function () {
            // Arrange
            $component = Livewire::test(ComponentModal::class);

            // Act
            $component->call('openModal');

            // Assert
            expect($component->instance()->isModalOpen)->toBeTrue();
        });
    });

    describe('form integration', function () {
        test('handles form submission and closes modal', function () {
            // Arrange
            $component = Livewire::test(ComponentModal::class);

            // Act
            $component->call('openModal')
                     ->set('form.field', 'value')
                     ->call('form.submit');

            // Assert
            expect(Model::where('field', 'value')->exists())->toBeTrue();
            expect($component->instance()->isModalOpen)->toBeFalse();
        });
    });
});
```

### Livewire Event Testing
```php
describe('Livewire Event Integration Tests', function () {
    test('component dispatches events correctly', function () {
        // Arrange
        $component = Livewire::test(WrestlerForm::class);

        // Act
        $component->set('name', 'Test Wrestler')
                 ->call('submit');

        // Assert
        $component->assertDispatched('wrestler-created');
    });
});
```
