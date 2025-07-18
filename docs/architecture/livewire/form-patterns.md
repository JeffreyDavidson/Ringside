# Livewire Form Patterns

## Overview

This document details the comprehensive form patterns used in Ringside's Livewire architecture. Our form system provides a consistent, type-safe approach to handling data input, validation, and persistence across all domain entities.

## Form Architecture

### Core Components

#### BaseForm
The foundation class that provides:
- **Model Management**: Automatic model binding and lifecycle
- **Validation**: Integrated Laravel validation
- **Data Transformation**: Input/output data mapping
- **Type Safety**: Generic type parameters for model binding

```php
/**
 * @template TModel of Model
 */
abstract class BaseForm extends Form
{
    protected ?Model $formModel = null;
    
    abstract protected function getModelClass(): string;
    abstract protected function getModelData(): array;
    abstract protected function rules(): array;
    
    public function loadExtraData(): void {}
    public function setModel(?Model $model): void {}
    public function store(): bool {}
}
```

#### CreateEditForm Pattern
All domain forms follow the `CreateEditForm` pattern:
- **Unified Interface**: Single form handles both create and edit operations
- **Model Binding**: Automatic model detection and data population
- **Validation**: Context-aware validation rules
- **Data Mapping**: Consistent data transformation patterns

### Implementation Pattern

```php
/**
 * @extends BaseForm<Event>
 */
class CreateEditForm extends BaseForm
{
    // Form properties - match model attributes
    public string $name = '';
    public Carbon|string|null $date = '';
    public int $venue_id = 0;
    public ?string $preview = '';
    
    // Model configuration
    protected function getModelClass(): string
    {
        return Event::class;
    }
    
    // Data transformation
    protected function getModelData(): array
    {
        return [
            'name' => $this->name,
            'date' => $this->date,
            'venue_id' => $this->venue_id,
            'preview' => $this->preview,
        ];
    }
    
    // Validation rules
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 
                Rule::unique('events', 'name')->ignore($this->formModel)],
            'date' => ['nullable', 'date', 
                new DateCanBeChanged($this->formModel)],
            'venue_id' => ['required_with:date', 'integer', 
                Rule::exists('venues', 'id')],
            'preview' => ['nullable', 'string'],
        ];
    }
}
```

## Form Lifecycle

### 1. Instantiation
Form components are created by parent components:
```php
// In BaseFormModal
$formClass = $this->getFormClass();
$this->form = new $formClass($this, 'form');
```

### 2. Model Binding
When editing existing models:
```php
public function setModel(?Model $model): void
{
    $this->formModel = $model;
    
    if ($model) {
        $this->fill($model->toArray());
        $this->loadExtraData();
    }
}
```

### 3. Extra Data Loading
Hook for additional data setup:
```php
public function loadExtraData(): void
{
    // Load related data, set computed properties, etc.
    if ($this->formModel && isset($this->formModel->venue_id)) {
        $this->venue_id = $this->formModel->venue_id;
    }
}
```

### 4. Validation
Real-time validation on property updates:
```php
// Automatic validation on property changes
public function updated($propertyName): void
{
    $this->validateOnly($propertyName);
}
```

### 5. Submission
Form persistence and event handling:
```php
public function store(): bool
{
    $this->validate();
    
    $modelData = $this->getModelData();
    $modelClass = $this->getModelClass();
    
    if ($this->formModel) {
        $this->formModel->update($modelData);
        $this->dispatch('modelUpdated', $this->formModel->id);
    } else {
        $model = $modelClass::create($modelData);
        $this->dispatch('modelCreated', $model->id);
    }
    
    return true;
}
```

## Validation Patterns

### Context-Aware Validation
Rules adapt based on create vs edit context:
```php
protected function rules(): array
{
    return [
        'name' => [
            'required',
            'string',
            'max:255',
            Rule::unique('events', 'name')->ignore($this->formModel)
        ],
        'email' => [
            'required',
            'email',
            Rule::unique('users', 'email')->ignore($this->formModel)
        ],
    ];
}
```

### Custom Validation Rules
Domain-specific validation logic:
```php
protected function rules(): array
{
    return [
        'date' => [
            'nullable',
            'date',
            new DateCanBeChanged($this->formModel)
        ],
        'employment_date' => [
            'nullable',
            'date',
            new EmploymentDateRule($this->formModel)
        ],
    ];
}
```

### Validation Attributes
Custom field names for error messages:
```php
protected function getCustomValidationAttributes(): array
{
    return [
        'date' => 'event date',
        'venue_id' => 'venue',
        'preview' => 'event preview',
    ];
}
```

## Data Handling Patterns

### Input Transformation
Transform form inputs for model storage:
```php
protected function getModelData(): array
{
    return [
        'name' => $this->name,
        'date' => $this->date,
        'venue_id' => $this->venue_id,
        'preview' => $this->preview,
        // Transform complex data
        'metadata' => json_encode($this->metadata),
        'status' => $this->status ?? 'active',
    ];
}
```

### Output Transformation
Transform model data for form display:
```php
public function loadExtraData(): void
{
    if ($this->formModel) {
        // Transform complex data for display
        $this->metadata = json_decode($this->formModel->metadata, true);
        $this->venue_id = $this->formModel->venue_id;
    }
}
```

## Relationship Handling

### Belongs To Relationships
```php
class CreateEditForm extends BaseForm
{
    public int $venue_id = 0;
    
    // Access related model
    public function getVenueProperty(): ?Venue
    {
        return $this->venue_id ? Venue::find($this->venue_id) : null;
    }
    
    // Validation includes relationship
    protected function rules(): array
    {
        return [
            'venue_id' => ['required', 'integer', Rule::exists('venues', 'id')],
        ];
    }
}
```

### Has Many Relationships
```php
class CreateEditForm extends BaseForm
{
    public array $tags = [];
    
    public function loadExtraData(): void
    {
        if ($this->formModel) {
            $this->tags = $this->formModel->tags->pluck('id')->toArray();
        }
    }
    
    protected function getModelData(): array
    {
        return [
            'name' => $this->name,
            // Handle relationships separately
        ];
    }
    
    public function store(): bool
    {
        $this->validate();
        
        $modelData = $this->getModelData();
        $modelClass = $this->getModelClass();
        
        if ($this->formModel) {
            $this->formModel->update($modelData);
            $this->formModel->tags()->sync($this->tags);
        } else {
            $model = $modelClass::create($modelData);
            $model->tags()->sync($this->tags);
        }
        
        return true;
    }
}
```

## Trait Integration

### Employment Management
```php
class CreateEditForm extends BaseForm
{
    use ManagesEmployment;
    
    public ?Carbon $employment_date = null;
    
    protected function rules(): array
    {
        return [
            'employment_date' => $this->employmentDateRules(),
        ];
    }
    
    protected function getModelData(): array
    {
        return [
            'name' => $this->name,
            'employment_date' => $this->employment_date,
        ];
    }
}
```

### Data Presentation
```php
class CreateEditForm extends BaseForm
{
    use PresentsVenuesList;
    
    public int $venue_id = 0;
    
    // Trait provides getVenues() method
    public function getVenuesProperty(): Collection
    {
        return $this->getVenues();
    }
}
```

## Error Handling

### Validation Errors
```php
public function store(): bool
{
    try {
        $this->validate();
        
        $modelData = $this->getModelData();
        // ... persistence logic
        
        return true;
    } catch (ValidationException $e) {
        // Validation errors automatically handled by Livewire
        return false;
    }
}
```

### Business Logic Errors
```php
public function store(): bool
{
    try {
        $this->validate();
        
        $modelData = $this->getModelData();
        $modelClass = $this->getModelClass();
        
        // Business logic validation
        if (!$this->canCreateModel($modelData)) {
            $this->addError('general', 'Cannot create model due to business rules');
            return false;
        }
        
        $model = $modelClass::create($modelData);
        
        return true;
    } catch (Exception $e) {
        $this->addError('general', 'An error occurred while saving');
        Log::error('Form submission error', ['error' => $e->getMessage()]);
        return false;
    }
}
```

## Testing Patterns

### Form Property Testing
```php
test('form properties are initialized correctly', function () {
    $form = new CreateEditForm();
    
    expect($form->name)->toBe('');
    expect($form->date)->toBe('');
    expect($form->venue_id)->toBe(0);
    expect($form->preview)->toBe('');
});
```

### Validation Testing
```php
test('validates required fields', function () {
    $form = new CreateEditForm();
    
    $form->name = '';
    $form->validate();
    
    expect($form->getErrorBag()->has('name'))->toBeTrue();
});
```

### Model Binding Testing
```php
test('loads model data correctly', function () {
    $event = Event::factory()->create([
        'name' => 'Test Event',
        'date' => '2024-01-01',
    ]);
    
    $form = new CreateEditForm();
    $form->setModel($event);
    
    expect($form->name)->toBe('Test Event');
    expect($form->date)->toBe('2024-01-01');
});
```

## Performance Optimization

### Lazy Loading
```php
class CreateEditForm extends BaseForm
{
    #[Lazy]
    public function getVenuesProperty(): Collection
    {
        return Venue::active()->get();
    }
}
```

### Computed Properties
```php
class CreateEditForm extends BaseForm
{
    #[Computed]
    public function availableVenues(): Collection
    {
        return Venue::active()
            ->when($this->formModel, function ($query) {
                return $query->orWhere('id', $this->formModel->venue_id);
            })
            ->get();
    }
}
```

### Database Optimization
```php
public function loadExtraData(): void
{
    if ($this->formModel) {
        // Eager load relationships
        $this->formModel->load(['venue', 'tags']);
        
        // Use specific queries instead of loading all data
        $this->venue_id = $this->formModel->venue_id;
    }
}
```

## Advanced Patterns

### Dynamic Form Fields
```php
class CreateEditForm extends BaseForm
{
    public array $dynamicFields = [];
    
    public function mount(): void
    {
        $this->dynamicFields = $this->getDynamicFields();
    }
    
    protected function getDynamicFields(): array
    {
        // Return fields based on configuration
        return config('forms.dynamic_fields', []);
    }
}
```

### Conditional Validation
```php
protected function rules(): array
{
    $rules = [
        'name' => ['required', 'string', 'max:255'],
    ];
    
    if ($this->type === 'special') {
        $rules['special_field'] = ['required', 'string'];
    }
    
    return $rules;
}
```

### Multi-Step Forms
```php
class CreateEditForm extends BaseForm
{
    public int $currentStep = 1;
    public array $steps = ['basic', 'details', 'review'];
    
    public function nextStep(): void
    {
        $this->validateCurrentStep();
        $this->currentStep++;
    }
    
    protected function validateCurrentStep(): void
    {
        $stepRules = $this->getStepRules($this->currentStep);
        $this->validate($stepRules);
    }
}
```

## Common Pitfalls

### Model Hydration Issues
```php
// ❌ WRONG - Model state may be lost
public function store(): bool
{
    $this->validate();
    // Model might be null here due to hydration
    $this->formModel->update($this->getModelData());
}

// ✅ CORRECT - Always ensure model is set
public function store(): bool
{
    $this->validate();
    
    if ($this->formModel) {
        $this->formModel->update($this->getModelData());
    } else {
        $modelClass = $this->getModelClass();
        $modelClass::create($this->getModelData());
    }
}
```

### Validation Rule Context
```php
// ❌ WRONG - Ignores current model incorrectly
'email' => Rule::unique('users', 'email')->ignore($this->id),

// ✅ CORRECT - Uses proper model context
'email' => Rule::unique('users', 'email')->ignore($this->formModel),
```

## Best Practices

### Form Design
- Keep forms focused on single responsibility
- Use meaningful property names
- Implement proper type hints
- Document complex validation rules

### Data Handling
- Transform data appropriately for storage
- Handle null values gracefully
- Validate relationships exist
- Use transactions for complex operations

### Error Handling
- Provide clear error messages
- Log errors for debugging
- Handle edge cases gracefully
- Test error scenarios

### Performance
- Use lazy loading for expensive operations
- Implement computed properties for cached data
- Optimize database queries
- Avoid storing large objects in form state

## Related Documentation

- [Component Architecture](component-architecture.md) - Overall architecture patterns
- [Modal Patterns](modal-patterns.md) - Modal integration patterns
- [Testing Guide](../../guides/livewire/testing-guide.md) - Form testing strategies
- [Migration Guide](../../guides/livewire/migration-guide.md) - Migration from legacy forms