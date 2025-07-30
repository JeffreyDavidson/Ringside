# Livewire Modal Patterns

## Overview

This document covers the comprehensive modal patterns used in Ringside's Livewire architecture. Our modal system provides a consistent, reusable approach to displaying forms and content in overlay interfaces across all domain entities.

## Modal Architecture

### Core Components

#### BaseModal
The foundation class providing core modal functionality:
- **State Management**: Modal open/close state handling
- **Model Binding**: Automatic model resolution and context
- **Lifecycle Management**: Mount/unmount operations
- **Event Handling**: Modal-specific event dispatching

```php
/**
 * @template TForm of BaseForm
 * @template TModel of Model
 */
abstract class BaseModal extends Component
{
    protected ?Model $model = null;
    protected ?Model $modelType = null;
    protected ?string $modalFormPath = null;
    
    abstract protected function getFormClass(): string;
    abstract protected function getModelClass(): string;
    abstract protected function getModalPath(): string;
    
    public function openModal(mixed $modelId = null): void {}
    public function closeModal(): void {}
    public function mount(mixed $modelId = null): void {}
}
```

#### BaseFormModal
Extends BaseModal with form-specific functionality:
- **Form Integration**: Automatic form component instantiation
- **Dummy Data**: Test data generation for development
- **Unified API**: Single interface for form-based modals

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
    public bool $isModalOpen = false;
    
    public function save(): void {}
    public function submitForm(): bool {}
    protected function getDummyDataFields(): array {}
}
```

### Modal Types

#### Form Modals
Most common modal type - displays forms for create/edit operations:
```php
class FormModal extends BaseFormModal
{
    protected function getFormClass(): string
    {
        return CreateEditForm::class;
    }
    
    protected function getModelClass(): string
    {
        return Event::class;
    }
    
    protected function getModalPath(): string
    {
        return 'livewire.events.modals.form-modal';
    }
}
```

#### Confirmation Modals
For dangerous operations requiring user confirmation:
```php
class ConfirmationModal extends BaseModal
{
    public string $title = '';
    public string $message = '';
    public string $confirmText = 'Confirm';
    public string $cancelText = 'Cancel';
    
    public function confirm(): void
    {
        $this->dispatch('confirmed');
        $this->closeModal();
    }
}
```

#### Information Modals
For displaying read-only information:
```php
class InfoModal extends BaseModal
{
    public array $data = [];
    
    public function openModal(mixed $modelId = null): void
    {
        $this->loadData($modelId);
        parent::openModal($modelId);
    }
    
    protected function loadData(mixed $modelId): void
    {
        $modelClass = $this->getModelClass();
        $this->data = $modelClass::find($modelId)->toArray();
    }
}
```

## Modal Lifecycle

### 1. Initialization
Modal components are typically embedded in parent components:
```php
// In parent component
class ManageEvents extends Component
{
    public FormModal $modal;
    
    public function createEvent(): void
    {
        $this->modal->openModal();
    }
    
    public function editEvent(int $eventId): void
    {
        $this->modal->openModal($eventId);
    }
}
```

### 2. Opening Modal
The `openModal()` method handles modal state and context:
```php
public function openModal(mixed $modelId = null): void
{
    // Ensure proper initialization
    if (!isset($this->modalFormPath)) {
        $this->mount($modelId);
    } elseif ($modelId !== null) {
        $this->mount($modelId);
    }
    
    $this->isModalOpen = true;
}
```

### 3. Model Resolution
When editing existing models:
```php
public function mount(mixed $modelId = null): void
{
    $this->modalFormPath = $this->getModalPath();
    
    // Initialize model type
    $modelClass = $this->getModelClass();
    $this->modelType = new $modelClass();
    
    // Load specific model if provided
    if ($modelId) {
        $this->model = $modelClass::find($modelId);
    }
    
    // Initialize form with model
    if (isset($this->form) && $this->model) {
        $this->form->setModel($this->model);
    }
}
```

### 4. Form Integration
BaseFormModal automatically integrates with form components:
```php
public function mount(mixed $modelId = null): void
{
    // Initialize form if not exists
    if (!isset($this->form)) {
        $formClass = $this->getFormClass();
        $this->form = new $formClass($this, 'form');
    }
    
    // Link form and modal
    $this->modelForm = $this->form;
    
    parent::mount($modelId);
    
    // Ensure form has model reference
    if (isset($modelId) && isset($this->model)) {
        $this->form->setModel($this->model);
    }
}
```

### 5. Submission Handling
Form submission through modal interface:
```php
public function submitForm(): bool
{
    // Ensure form has correct model
    if (isset($this->model) && $this->form) {
        $this->form->setModel($this->model);
    }
    
    $result = $this->form->store();
    
    if ($result) {
        $this->closeModal();
        $this->dispatch('form-submitted');
    }
    
    return $result;
}
```

### 6. Closing Modal
Clean up modal state:
```php
public function closeModal(): void
{
    $this->isModalOpen = false;
    $this->dispatch('closeModal');
}
```

## View Integration

### Modal Template Structure
```blade
<!-- livewire/events/modals/form-modal.blade.php -->
<div>
    @if($isModalOpen)
        <!-- Modal Overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity">
            <!-- Modal Dialog -->
            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center">
                    <!-- Modal Content -->
                    <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ $model ? 'Edit Event' : 'Create Event' }}
                            </h3>
                            <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Modal Body -->
                        <div class="space-y-4">
                            <!-- Form Fields -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                                <input type="text" 
                                       wire:model="form.name" 
                                       id="name" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('form.name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="date" class="block text-sm font-medium text-gray-700">Date</label>
                                <input type="datetime-local" 
                                       wire:model="form.date" 
                                       id="date" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('form.date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="venue_id" class="block text-sm font-medium text-gray-700">Venue</label>
                                <select wire:model="form.venue_id" 
                                        id="venue_id" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select a venue</option>
                                    @foreach($form->venues as $venue)
                                        <option value="{{ $venue->id }}">{{ $venue->name }}</option>
                                    @endforeach
                                </select>
                                @error('form.venue_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        <!-- Modal Footer -->
                        <div class="mt-6 flex justify-end space-x-3">
                            <button wire:click="closeModal" 
                                    class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Cancel
                            </button>
                            <button wire:click="save" 
                                    class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                {{ $model ? 'Update' : 'Create' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
```

### Parent Component Integration
```blade
<!-- manage-events.blade.php -->
<div>
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Events</h1>
        <button wire:click="$refs.modal.openModal()" 
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
            Create Event
        </button>
    </div>
    
    <!-- Events Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        @foreach($events as $event)
            <div class="px-4 py-4 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">{{ $event->name }}</h3>
                    <p class="text-sm text-gray-500">{{ $event->date->format('F j, Y') }}</p>
                </div>
                <button wire:click="$refs.modal.openModal({{ $event->id }})" 
                        class="text-indigo-600 hover:text-indigo-900">
                    Edit
                </button>
            </div>
        @endforeach
    </div>
    
    <!-- Modal Component -->
    <livewire:events.modals.form-modal x-ref="modal" />
</div>
```

## Event Handling

### Modal Events
```php
class FormModal extends BaseFormModal
{
    public function submitForm(): bool
    {
        $result = $this->form->store();
        
        if ($result) {
            $this->closeModal();
            
            // Dispatch events for parent components
            $this->dispatch('form-submitted');
            $this->dispatch('closeModal');
            
            // Dispatch model-specific events
            if ($this->model) {
                $this->dispatch('eventUpdated', $this->model->id);
            } else {
                $this->dispatch('eventCreated');
            }
        }
        
        return $result;
    }
}
```

### Parent Component Listeners
```php
class ManageEvents extends Component
{
    protected $listeners = [
        'form-submitted' => 'refreshEvents',
        'eventCreated' => 'refreshEvents',
        'eventUpdated' => 'refreshEvents',
    ];
    
    public function refreshEvents(): void
    {
        // Refresh events list
        $this->events = Event::latest()->get();
    }
}
```

## Dummy Data Integration

### Development Data Generation
```php
class FormModal extends BaseFormModal
{
    use GeneratesDummyData;
    
    protected function getDummyDataFields(): array
    {
        $venue = Venue::query()->inRandomOrder()->first();
        
        return [
            'name' => fn() => Str::of(fake()->sentence(2))->title()->value(),
            'date' => fn() => fake()->dateTimeBetween('now', '+3 month')->format('Y-m-d H:i:s'),
            'venue_id' => fn() => $venue?->id ?? Venue::factory()->create()->id,
            'preview' => fn() => fake()->text(200),
        ];
    }
}
```

### Usage in Views
```blade
<!-- Development only -->
@if(app()->environment('local'))
    <button wire:click="fillDummyFields" 
            class="text-sm text-gray-500 hover:text-gray-700">
        Fill Test Data
    </button>
@endif
```

## Advanced Patterns

### Nested Modals
```php
class FormModal extends BaseFormModal
{
    public bool $showConfirmation = false;
    
    public function save(): void
    {
        if ($this->model && $this->hasImportantChanges()) {
            $this->showConfirmation = true;
            return;
        }
        
        $this->submitForm();
    }
    
    public function confirmSave(): void
    {
        $this->showConfirmation = false;
        $this->submitForm();
    }
}
```

### Dynamic Modal Content
```php
class FormModal extends BaseFormModal
{
    public string $modalSize = 'md';
    public array $formSections = [];
    
    public function mount(mixed $modelId = null): void
    {
        parent::mount($modelId);
        
        // Configure modal based on model type
        if ($this->model && $this->model->type === 'complex') {
            $this->modalSize = 'lg';
            $this->formSections = ['basic', 'advanced', 'metadata'];
        }
    }
}
```

### Modal with Tabs
```php
class FormModal extends BaseFormModal
{
    public string $activeTab = 'basic';
    public array $tabs = ['basic', 'details', 'settings'];
    
    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }
    
    public function save(): void
    {
        // Validate all tabs
        foreach ($this->tabs as $tab) {
            $this->validateTab($tab);
        }
        
        $this->submitForm();
    }
}
```

## State Management

### Modal State Persistence
```php
class FormModal extends BaseFormModal
{
    public bool $isModalOpen = false;
    
    public function openModal(mixed $modelId = null): void
    {
        // Clear previous state
        $this->resetValidation();
        $this->resetErrorBag();
        
        parent::openModal($modelId);
        
        $this->isModalOpen = true;
    }
    
    public function closeModal(): void
    {
        $this->isModalOpen = false;
        
        // Reset form state
        if ($this->form) {
            $this->form->reset();
        }
    }
}
```

### Conditional Modal Behavior
```php
class FormModal extends BaseFormModal
{
    public function openModal(mixed $modelId = null): void
    {
        // Check permissions
        if (!$this->canOpenModal($modelId)) {
            $this->dispatch('unauthorized');
            return;
        }
        
        parent::openModal($modelId);
    }
    
    protected function canOpenModal(mixed $modelId): bool
    {
        if ($modelId && !$this->canEditModel($modelId)) {
            return false;
        }
        
        return $this->canCreateModel();
    }
}
```

## Testing Patterns

### Modal State Testing
```php
test('modal opens and closes correctly', function () {
    $component = Livewire::test(FormModal::class);
    
    // Initially closed
    expect($component->instance()->isModalOpen)->toBeFalse();
    
    // Opens modal
    $component->call('openModal');
    expect($component->instance()->isModalOpen)->toBeTrue();
    
    // Closes modal
    $component->call('closeModal');
    expect($component->instance()->isModalOpen)->toBeFalse();
});
```

### Form Integration Testing
```php
test('modal form submission works correctly', function () {
    $venue = Venue::factory()->create();
    
    $component = Livewire::test(FormModal::class)
        ->call('openModal')
        ->set('form.name', 'Test Event')
        ->set('form.date', '2024-01-01')
        ->set('form.venue_id', $venue->id)
        ->call('save');
    
    $component->assertDispatched('form-submitted');
    expect($component->instance()->isModalOpen)->toBeFalse();
    
    $this->assertDatabaseHas('events', [
        'name' => 'Test Event',
        'date' => '2024-01-01',
        'venue_id' => $venue->id,
    ]);
});
```

### Event Testing
```php
test('modal dispatches correct events', function () {
    $component = Livewire::test(FormModal::class)
        ->call('openModal')
        ->set('form.name', 'Test Event')
        ->call('save');
    
    $component->assertDispatched('form-submitted');
    $component->assertDispatched('closeModal');
    $component->assertDispatched('eventCreated');
});
```

## Performance Optimization

### Lazy Loading
```php
class FormModal extends BaseFormModal
{
    #[Lazy]
    public function getVenuesProperty(): Collection
    {
        return Venue::active()->get();
    }
    
    #[Computed]
    public function availableVenues(): Collection
    {
        return $this->getVenuesProperty();
    }
}
```

### Deferred Loading
```php
class FormModal extends BaseFormModal
{
    public bool $showModal = false;
    
    public function openModal(mixed $modelId = null): void
    {
        $this->showModal = true;
        
        // Defer expensive operations
        $this->skipRender();
        
        parent::openModal($modelId);
    }
}
```

## Error Handling

### Modal Error States
```php
class FormModal extends BaseFormModal
{
    public bool $hasError = false;
    public string $errorMessage = '';
    
    public function submitForm(): bool
    {
        try {
            $result = $this->form->store();
            
            if ($result) {
                $this->hasError = false;
                $this->closeModal();
            }
            
            return $result;
        } catch (Exception $e) {
            $this->hasError = true;
            $this->errorMessage = 'An error occurred while saving.';
            Log::error('Modal form submission error', ['error' => $e->getMessage()]);
            
            return false;
        }
    }
}
```

### Validation Error Display
```blade
@if($hasError)
    <div class="rounded-md bg-red-50 p-4 mb-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-red-800">{{ $errorMessage }}</p>
            </div>
        </div>
    </div>
@endif
```

## Best Practices

### Modal Design
- Keep modals focused on single tasks
- Use appropriate modal sizes for content
- Provide clear actions (Save/Cancel)
- Include proper loading states

### State Management
- Reset modal state on close
- Handle form validation errors gracefully
- Provide feedback for user actions
- Maintain proper modal lifecycle

### Performance
- Use lazy loading for expensive operations
- Implement proper caching strategies
- Minimize modal re-rendering
- Clean up resources on close

### Accessibility
- Include proper ARIA labels
- Handle keyboard navigation
- Provide focus management
- Support screen readers

## Common Pitfalls

### Form State Issues
```php
// ❌ WRONG - Form state may be lost
public function openModal(mixed $modelId = null): void
{
    $this->isModalOpen = true;
    // Form may not have model context
}

// ✅ CORRECT - Ensure proper initialization
public function openModal(mixed $modelId = null): void
{
    if (!isset($this->modalFormPath)) {
        $this->mount($modelId);
    }
    $this->isModalOpen = true;
}
```

### Event Handling
```php
// ❌ WRONG - Events may not be dispatched
public function save(): void
{
    $this->form->store();
    $this->closeModal();
}

// ✅ CORRECT - Check result and dispatch events
public function save(): void
{
    if ($this->form->store()) {
        $this->dispatch('form-submitted');
        $this->closeModal();
    }
}
```

## Related Documentation

- [Component Architecture](component-architecture.md) - Overall architecture patterns
- [Form Patterns](form-patterns.md) - Form component implementation
- [Testing Guide](../../guides/livewire/testing-guide.md) - Modal testing strategies
- [Migration Guide](../../guides/livewire/migration-guide.md) - Migration from legacy modals