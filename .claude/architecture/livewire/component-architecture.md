# Livewire Component Architecture

## Overview

This document provides a comprehensive guide to the Livewire component architecture in Ringside. Our architecture follows a standardized pattern that ensures consistency, maintainability, and scalability across all domain entities.

## Architecture Principles

### 1. Single Responsibility Principle
Each component has a focused purpose:
- **Forms** handle data input, validation, and persistence
- **Modals** manage modal display and form integration
- **Tables** display and manage data collections
- **Actions** handle specific business operations

### 2. Template Method Pattern
Base classes provide common functionality while allowing customization through abstract methods:
- `BaseForm` - Form lifecycle management
- `BaseModal` - Modal state management
- `BaseFormModal` - Combined form and modal functionality
- `BaseTable` - Table display and filtering

### 3. Composition over Inheritance
Components use traits for shared functionality:
- `GeneratesDummyData` - Test data generation
- `HasStandardValidationAttributes` - Validation messaging
- `ManagesEmployment` - Employment status handling

## Component Hierarchy

```
app/Livewire/
├── Base/
│   ├── BaseForm.php           # Form lifecycle management
│   ├── BaseModal.php          # Modal state management
│   └── BaseFormModal.php      # Combined form+modal functionality
├── Concerns/
│   ├── GeneratesDummyData.php # Test data generation
│   └── Data/
│       └── PresentsVenuesList.php # Domain-specific data presentation
└── {Domain}/
    ├── Forms/
    │   └── CreateEditForm.php  # Domain-specific form implementation
    ├── Modals/
    │   └── FormModal.php       # Domain-specific modal implementation
    ├── Tables/
    │   └── {Domain}Table.php   # Domain-specific table implementation
    └── Components/
        └── Actions.php # Domain-specific actions
```

## Base Classes

### BaseForm

The foundational form class that provides:
- **Model Management**: Automatic model binding and persistence
- **Validation**: Standardized validation patterns
- **Lifecycle Hooks**: `loadExtraData()`, `getModelData()`, `rules()`
- **Type Safety**: Generic type parameters for model binding

```php
/**
 * @template TModel of Model
 * @extends BaseForm<TModel>
 */
abstract class BaseForm extends Form
{
    abstract protected function getModelClass(): string;
    abstract protected function getModelData(): array;
    abstract protected function rules(): array;
    
    public function loadExtraData(): void {}
    public function setModel(?Model $model): void {}
    public function store(): bool {}
}
```

### BaseModal

Core modal functionality providing:
- **State Management**: Modal open/close state
- **Model Binding**: Automatic model resolution
- **Event Handling**: Modal lifecycle events
- **View Resolution**: Dynamic view path handling

```php
/**
 * @template TForm of BaseForm
 * @template TModel of Model
 */
abstract class BaseModal extends Component
{
    abstract protected function getFormClass(): string;
    abstract protected function getModelClass(): string;
    abstract protected function getModalPath(): string;
    
    public function openModal(mixed $modelId = null): void {}
    public function closeModal(): void {}
    public function mount(mixed $modelId = null): void {}
}
```

### BaseFormModal

Combines BaseModal with form-specific functionality:
- **Form Integration**: Automatic form instantiation
- **Dummy Data**: Test data generation capabilities
- **Unified API**: Single interface for form modals

```php
/**
 * @template TForm of BaseForm
 * @template TModel of Model
 * @extends BaseModal<TForm, TModel>
 */
abstract class BaseFormModal extends BaseModal
{
    use GeneratesDummyData;
    
    public BaseForm $form;
    
    public function save(): void {}
    public function submitForm(): bool {}
    protected function getDummyDataFields(): array {}
}
```

## Component Lifecycle

### Form Lifecycle

1. **Instantiation**: Component created with optional model ID
2. **Mount**: `mount($modelId)` called to initialize state
3. **Model Binding**: If model ID provided, model loaded and bound
4. **Extra Data Loading**: `loadExtraData()` called for additional setup
5. **Validation**: Real-time validation on property updates
6. **Submission**: `store()` method handles persistence
7. **Success**: Events dispatched, component state updated

### Modal Lifecycle

1. **Component Creation**: Modal component instantiated
2. **Open Modal**: `openModal($modelId)` called
3. **Mount/Remount**: Component mounted with model context
4. **Form Initialization**: Form component created and configured
5. **Display**: Modal rendered with form content
6. **Form Submission**: Form validation and persistence
7. **Close**: Modal closed on success or user action

## Domain Implementation Pattern

### Directory Structure
Each domain follows this standardized structure:

```
app/Livewire/{Domain}/
├── Forms/
│   └── CreateEditForm.php      # Handles create/edit operations
├── Modals/
│   └── FormModal.php           # Modal wrapper for forms
├── Tables/
│   ├── Main.php                # Main entity table
│   └── Previous{Related}.php   # Relationship tables
└── Components/
    └── Actions.php             # Business action handlers
```

### Naming Conventions

- **Forms**: `CreateEditForm` - Handles both create and edit operations
- **Modals**: `FormModal` - Wraps CreateEditForm in modal interface
- **Tables**: `Main` - Main entity display table, `Previous{Related}` - Relationship tables
- **Components**: `Actions` - Business action handlers

### Implementation Requirements

Each domain component must:
1. **Extend appropriate base class**
2. **Implement required abstract methods**
3. **Follow naming conventions**
4. **Include comprehensive documentation**
5. **Provide test coverage**

## Generic Type Safety

### Type Parameters

Components use generic type parameters for type safety:

```php
/**
 * @template TModel of Model
 * @extends BaseForm<TModel>
 */
class CreateEditForm extends BaseForm
{
    protected function getModelClass(): string
    {
        return Event::class; // @phpstan-ignore-line
    }
}
```

### Benefits

- **IDE Support**: Better autocomplete and error detection
- **Static Analysis**: PHPStan can verify type correctness
- **Runtime Safety**: Type hints prevent incorrect usage
- **Documentation**: Clear contracts for component usage

## Integration Patterns

### Form-Modal Integration

```php
// FormModal provides the modal wrapper
class FormModal extends BaseFormModal
{
    // Form class configuration
    protected function getFormClass(): string
    {
        return CreateEditForm::class;
    }
    
    // Model class configuration
    protected function getModelClass(): string
    {
        return Event::class;
    }
    
    // View path configuration
    protected function getModalPath(): string
    {
        return 'livewire.events.modals.form-modal';
    }
}
```

### Component Communication

Components communicate through:
- **Events**: `$this->dispatch('event-name', $data)`
- **Properties**: Reactive public properties
- **Method Calls**: Direct method invocation
- **URL Parameters**: Route model binding

## Performance Considerations

### Lazy Loading
- Use `#[Lazy]` attribute for expensive computations
- Implement `#[Computed]` for cached calculations
- Defer non-critical operations

### Memory Management
- Avoid storing large objects in component state
- Use database queries instead of in-memory collections
- Implement proper cleanup in component destruction

### Caching
- Cache expensive calculations using `#[Computed]`
- Implement query result caching where appropriate
- Use Livewire's built-in caching mechanisms

## Error Handling

### Validation Errors
- Use Laravel's validation system
- Provide clear error messages
- Implement field-specific validation rules

### Exception Handling
- Catch and handle exceptions gracefully
- Provide user-friendly error messages
- Log errors for debugging

### Fallback Behavior
- Implement fallback UI for error states
- Provide retry mechanisms where appropriate
- Maintain component state during errors

## Testing Strategy

### Unit Tests
- Test individual component methods
- Mock dependencies and external services
- Verify business logic correctness

### Integration Tests
- Test component rendering
- Verify form submission workflows
- Test modal state management

### Feature Tests
- Test complete user workflows
- Verify event dispatching
- Test component interaction

## Migration Path

### From Legacy Components
1. **Assess Current Component**: Understand existing functionality
2. **Plan Migration**: Identify required changes
3. **Implement Base Classes**: Extend appropriate base classes
4. **Update Tests**: Ensure comprehensive test coverage
5. **Deploy Gradually**: Use feature flags for safe deployment

### Breaking Changes
- Document all breaking changes
- Provide migration guides
- Offer backward compatibility where possible
- Communicate changes clearly

## Best Practices

### Code Organization
- Group related functionality together
- Use descriptive method and property names
- Follow PSR-12 coding standards
- Include comprehensive PHPDoc comments

### Component Design
- Keep components focused and small
- Avoid tight coupling between components
- Use dependency injection for external services
- Implement proper error boundaries

### State Management
- Minimize component state
- Use reactive properties appropriately
- Implement proper state validation
- Handle state hydration correctly

## Related Documentation

- [Form Patterns](form-patterns.md) - Detailed form implementation patterns
- [Modal Patterns](modal-patterns.md) - Modal component best practices
- [Testing Guide](../../guides/livewire/testing-guide.md) - Comprehensive testing strategies
- [Migration Guide](../../guides/livewire/migration-guide.md) - Migration from legacy patterns

## References

- [Laravel Livewire Documentation](https://laravel-livewire.com/docs)
- [Laravel Validation](https://laravel.com/docs/validation)
- [PHPStan Generic Types](https://phpstan.org/blog/generics-in-php-using-phpdocs)
- [Template Method Pattern](https://refactoring.guru/design-patterns/template-method)