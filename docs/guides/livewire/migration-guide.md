# Migration Guide

## Overview

This guide provides step-by-step instructions for migrating from old Livewire patterns to the new standardized architecture in Ringside. The migration involves updating component naming conventions, implementing base classes, and adopting new testing patterns.

## Migration Timeline

### Phase 1: Base Class Standardization (Completed)
- ✅ Implemented `BaseForm`, `BaseFormModal`, and `BaseTable` classes
- ✅ Added generic type support for better type safety
- ✅ Established template method patterns

### Phase 2: Component Naming Standardization (Completed)
- ✅ Renamed `Form` classes to `CreateEditForm`
- ✅ Renamed `Modal` classes to `FormModal`
- ✅ Updated all component references

### Phase 3: Legacy Cleanup (Completed)
- ✅ Removed deprecated patterns and unused code
- ✅ Updated imports and class references
- ✅ Cleaned up outdated documentation

### Phase 4: Documentation Overhaul (In Progress)
- ✅ Created comprehensive testing guides
- ✅ Documented architecture patterns
- 🔄 Created migration documentation
- 🔄 Added real-world examples

## Component Migration

### Form Component Migration

#### Old Pattern (Deprecated)
```php
// Old: app/Livewire/Events/Forms/Form.php
class Form extends Component
{
    public $name;
    public $date;
    public $venue_id;
    
    protected $rules = [
        'name' => 'required|string|max:255',
        'date' => 'required|date',
        'venue_id' => 'required|exists:venues,id',
    ];
    
    public function submit()
    {
        $this->validate();
        
        Event::create([
            'name' => $this->name,
            'date' => $this->date,
            'venue_id' => $this->venue_id,
        ]);
        
        $this->emit('eventCreated');
        $this->reset();
    }
}
```

#### New Pattern (Current)
```php
// New: app/Livewire/Events/Forms/CreateEditForm.php
class CreateEditForm extends BaseForm
{
    protected function getModelClass(): string
    {
        return Event::class;
    }
    
    protected function getRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'venue_id' => 'required|exists:venues,id',
        ];
    }
    
    protected function getModelData(): array
    {
        return [
            'name' => $this->name,
            'date' => $this->date,
            'venue_id' => $this->venue_id,
        ];
    }
    
    protected function afterSave(Model $model): void
    {
        $this->dispatch('eventCreated', $model->id);
    }
}
```

#### Migration Steps

1. **Rename the class file**:
   ```bash
   # From: app/Livewire/Events/Forms/Form.php
   # To: app/Livewire/Events/Forms/CreateEditForm.php
   mv app/Livewire/Events/Forms/Form.php app/Livewire/Events/Forms/CreateEditForm.php
   ```

2. **Update class declaration**:
   ```php
   // Old
   class Form extends Component
   
   // New
   class CreateEditForm extends BaseForm
   ```

3. **Implement required abstract methods**:
   ```php
   protected function getModelClass(): string
   {
       return Event::class;
   }
   
   protected function getRules(): array
   {
       return [
           'name' => 'required|string|max:255',
           'date' => 'required|date',
           'venue_id' => 'required|exists:venues,id',
       ];
   }
   
   protected function getModelData(): array
   {
       return [
           'name' => $this->name,
           'date' => $this->date,
           'venue_id' => $this->venue_id,
       ];
   }
   ```

4. **Remove deprecated methods**:
   ```php
   // Remove these methods (handled by BaseForm)
   public function submit() { /* ... */ }
   public function validate() { /* ... */ }
   public function reset() { /* ... */ }
   ```

5. **Update event dispatching**:
   ```php
   // Old
   $this->emit('eventCreated');
   
   // New
   protected function afterSave(Model $model): void
   {
       $this->dispatch('eventCreated', $model->id);
   }
   ```

6. **Update component imports**:
   ```php
   // In other files that reference the old class
   use App\Livewire\Events\Forms\CreateEditForm; // Changed from Form
   ```

### Modal Component Migration

#### Old Pattern (Deprecated)
```php
// Old: app/Livewire/Events/Modals/Modal.php
class Modal extends Component
{
    public $isOpen = false;
    public $event;
    public $form;
    
    public function mount()
    {
        $this->form = new Form();
    }
    
    public function openModal($eventId = null)
    {
        $this->isOpen = true;
        if ($eventId) {
            $this->event = Event::find($eventId);
            $this->form->fill($this->event->toArray());
        }
    }
    
    public function closeModal()
    {
        $this->isOpen = false;
        $this->form->reset();
    }
    
    public function save()
    {
        $this->form->submit();
        $this->closeModal();
    }
}
```

#### New Pattern (Current)
```php
// New: app/Livewire/Events/Modals/FormModal.php
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

#### Migration Steps

1. **Rename the class file**:
   ```bash
   mv app/Livewire/Events/Modals/Modal.php app/Livewire/Events/Modals/FormModal.php
   ```

2. **Update class declaration**:
   ```php
   // Old
   class Modal extends Component
   
   // New
   class FormModal extends BaseFormModal
   ```

3. **Implement required abstract methods**:
   ```php
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
   ```

4. **Remove deprecated properties and methods**:
   ```php
   // Remove these (handled by BaseFormModal)
   public $isOpen = false;
   public $event;
   public $form;
   
   public function mount() { /* ... */ }
   public function openModal($eventId = null) { /* ... */ }
   public function closeModal() { /* ... */ }
   public function save() { /* ... */ }
   ```

### Table Component Migration

#### Old Pattern (Deprecated)
```php
// Old: app/Livewire/Events/Tables/Table.php
class Table extends Component
{
    public $search = '';
    public $sortBy = 'id';
    public $sortDirection = 'asc';
    public $perPage = 15;
    
    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'id'],
        'sortDirection' => ['except' => 'asc'],
    ];
    
    public function render()
    {
        $events = Event::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
        
        return view('livewire.events.tables.table', [
            'events' => $events,
        ]);
    }
}
```

#### New Pattern (Current)
```php
// New: app/Livewire/Events/Tables/EventsTable.php
class EventsTable extends BaseTable
{
    protected function getModelClass(): string
    {
        return Event::class;
    }
    
    protected function getColumns(): array
    {
        return [
            'name' => 'Name',
            'date' => 'Date',
            'venue.name' => 'Venue',
            'status' => 'Status',
        ];
    }
    
    protected function getFilters(): array
    {
        return [
            'status' => 'Status',
            'venue_id' => 'Venue',
        ];
    }
    
    protected function applySearch(Builder $query, string $search): Builder
    {
        return $query->where('name', 'like', "%{$search}%");
    }
}
```

#### Migration Steps

1. **Rename the class file**:
   ```bash
   mv app/Livewire/Events/Tables/Table.php app/Livewire/Events/Tables/EventsTable.php
   ```

2. **Update class declaration**:
   ```php
   // Old
   class Table extends Component
   
   // New
   class EventsTable extends BaseTable
   ```

3. **Implement required abstract methods**:
   ```php
   protected function getModelClass(): string
   {
       return Event::class;
   }
   
   protected function getColumns(): array
   {
       return [
           'name' => 'Name',
           'date' => 'Date',
           'venue.name' => 'Venue',
           'status' => 'Status',
       ];
   }
   ```

4. **Remove deprecated properties and methods**:
   ```php
   // Remove these (handled by BaseTable)
   public $search = '';
   public $sortBy = 'id';
   public $sortDirection = 'asc';
   public $perPage = 15;
   
   protected $queryString = [/* ... */];
   
   public function render() { /* ... */ }
   ```

5. **Implement custom search logic**:
   ```php
   protected function applySearch(Builder $query, string $search): Builder
   {
       return $query->where('name', 'like', "%{$search}%");
   }
   ```

## View Migration

### Blade Template Updates

#### Old Blade Templates
```blade
{{-- Old: resources/views/livewire/events/forms/form.blade.php --}}
<form wire:submit.prevent="submit">
    <input type="text" wire:model="name" placeholder="Event Name">
    @error('name') <span class="error">{{ $message }}</span> @enderror
    
    <input type="date" wire:model="date">
    @error('date') <span class="error">{{ $message }}</span> @enderror
    
    <select wire:model="venue_id">
        <option value="">Select Venue</option>
        @foreach($venues as $venue)
            <option value="{{ $venue->id }}">{{ $venue->name }}</option>
        @endforeach
    </select>
    @error('venue_id') <span class="error">{{ $message }}</span> @enderror
    
    <button type="submit">Save</button>
</form>
```

#### New Blade Templates
```blade
{{-- New: resources/views/livewire/events/forms/create-edit-form.blade.php --}}
<form wire:submit.prevent="save">
    <div class="form-group">
        <label for="name">Event Name</label>
        <input type="text" wire:model="name" id="name" class="form-control">
        @error('name') <span class="error">{{ $message }}</span> @enderror
    </div>
    
    <div class="form-group">
        <label for="date">Date</label>
        <input type="date" wire:model="date" id="date" class="form-control">
        @error('date') <span class="error">{{ $message }}</span> @enderror
    </div>
    
    <div class="form-group">
        <label for="venue_id">Venue</label>
        <select wire:model="venue_id" id="venue_id" class="form-control">
            <option value="">Select Venue</option>
            @foreach($venues as $venue)
                <option value="{{ $venue->id }}">{{ $venue->name }}</option>
            @endforeach
        </select>
        @error('venue_id') <span class="error">{{ $message }}</span> @enderror
    </div>
    
    <button type="submit" class="btn btn-primary">
        {{ $model ? 'Update' : 'Create' }} Event
    </button>
</form>
```

#### Migration Steps

1. **Rename blade files**:
   ```bash
   # Forms
   mv resources/views/livewire/events/forms/form.blade.php \
      resources/views/livewire/events/forms/create-edit-form.blade.php
   
   # Modals
   mv resources/views/livewire/events/modals/modal.blade.php \
      resources/views/livewire/events/modals/form-modal.blade.php
   
   # Tables
   mv resources/views/livewire/events/tables/table.blade.php \
      resources/views/livewire/events/tables/events-table.blade.php
   ```

2. **Update method references**:
   ```blade
   {{-- Old --}}
   <form wire:submit.prevent="submit">
   
   {{-- New --}}
   <form wire:submit.prevent="save">
   ```

3. **Update modal references**:
   ```blade
   {{-- Old --}}
   @if($isOpen)
       <div class="modal">
           {{-- modal content --}}
       </div>
   @endif
   
   {{-- New --}}
   @if($isModalOpen)
       <div class="modal">
           {{-- modal content --}}
       </div>
   @endif
   ```

4. **Update table references**:
   ```blade
   {{-- Old --}}
   @foreach($events as $event)
       <tr>
           <td>{{ $event->name }}</td>
           <td>{{ $event->date }}</td>
       </tr>
   @endforeach
   
   {{-- New --}}
   @foreach($this->getRecords() as $record)
       <tr>
           <td>{{ $record->name }}</td>
           <td>{{ $record->date }}</td>
       </tr>
   @endforeach
   ```

## Testing Migration

### Old Testing Patterns
```php
// Old test approach
test('creates event', function () {
    $component = Livewire::test(Form::class);
    
    $component->set('name', 'Test Event')
        ->set('date', '2024-01-01')
        ->set('venue_id', 1)
        ->call('submit');
    
    expect(Event::where('name', 'Test Event')->exists())->toBeTrue();
});
```

### New Testing Patterns
```php
// New test approach
test('creates event', function () {
    $venue = Venue::factory()->create();
    
    $component = Livewire::test(CreateEditForm::class)
        ->set('name', 'Test Event')
        ->set('date', '2024-01-01')
        ->set('venue_id', $venue->id)
        ->call('save');
    
    expect(Event::where('name', 'Test Event')->exists())->toBeTrue();
    $component->assertDispatched('eventCreated');
});
```

### Migration Steps for Tests

1. **Update test file names**:
   ```bash
   # Update test files to match new component names
   mv tests/Integration/Livewire/Events/Forms/FormTest.php \
      tests/Integration/Livewire/Events/Forms/CreateEditFormTest.php
   ```

2. **Update class references**:
   ```php
   // Old
   use App\Livewire\Events\Forms\Form;
   
   // New
   use App\Livewire\Events\Forms\CreateEditForm;
   ```

3. **Update test method calls**:
   ```php
   // Old
   $component->call('submit');
   
   // New
   $component->call('save');
   ```

4. **Update assertion patterns**:
   ```php
   // Old
   $component->assertEmitted('eventCreated');
   
   // New
   $component->assertDispatched('eventCreated');
   ```

5. **Use new testing utilities**:
   ```php
   // Use new testing helpers
   $component = createComponentTest(CreateEditForm::class);
   assertNoValidationErrors($component);
   assertModelExists(Event::class, ['name' => 'Test Event']);
   ```

## Route Migration

### Old Routes
```php
// Old routes (web.php)
Route::get('/events', \App\Livewire\Events\Tables\Table::class);
Route::get('/events/create', \App\Livewire\Events\Forms\Form::class);
Route::get('/events/{event}/edit', \App\Livewire\Events\Forms\Form::class);
```

### New Routes
```php
// New routes (web.php)
Route::get('/events', \App\Livewire\Events\Tables\EventsTable::class);
Route::get('/events/create', \App\Livewire\Events\Forms\CreateEditForm::class);
Route::get('/events/{event}/edit', \App\Livewire\Events\Forms\CreateEditForm::class);
```

### Migration Steps

1. **Update route definitions**:
   ```php
   // Update all route references to use new class names
   Route::get('/events', \App\Livewire\Events\Tables\EventsTable::class);
   ```

2. **Update navigation references**:
   ```php
   // In navigation views
   <a href="{{ route('events.index') }}">Events</a>
   ```

3. **Update middleware assignments**:
   ```php
   // Ensure middleware is properly assigned to new routes
   Route::middleware(['auth', 'verified'])->group(function () {
       Route::get('/events', \App\Livewire\Events\Tables\EventsTable::class);
   });
   ```

## Configuration Migration

### Service Provider Updates

Update service provider configurations:

```php
// In AppServiceProvider.php
public function boot()
{
    // Update Livewire component aliases if used
    Livewire::component('events.table', \App\Livewire\Events\Tables\EventsTable::class);
    Livewire::component('events.form', \App\Livewire\Events\Forms\CreateEditForm::class);
    Livewire::component('events.modal', \App\Livewire\Events\Modals\FormModal::class);
}
```

### Configuration Files

Update any configuration files that reference the old component names:

```php
// config/livewire.php
return [
    'class_namespace' => 'App\\Livewire',
    'view_path' => resource_path('views/livewire'),
    
    // Update any specific component mappings
    'components' => [
        'events.table' => \App\Livewire\Events\Tables\EventsTable::class,
        'events.form' => \App\Livewire\Events\Forms\CreateEditForm::class,
        'events.modal' => \App\Livewire\Events\Modals\FormModal::class,
    ],
];
```

## Database Migration

### Migration Files

If you have any database migrations that reference old component names or patterns, update them:

```php
// database/migrations/xxxx_create_livewire_temp_uploaded_files_table.php
Schema::create('livewire_temp_uploaded_files', function (Blueprint $table) {
    $table->string('id')->primary();
    $table->string('filename');
    $table->string('mime_type');
    $table->string('path');
    $table->timestamp('created_at');
});
```

## Troubleshooting

### Common Issues

1. **Class not found errors**:
   ```bash
   # Clear application cache
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   
   # Regenerate autoload files
   composer dump-autoload
   ```

2. **View not found errors**:
   - Ensure blade files are renamed correctly
   - Check that view paths match component names
   - Verify blade file syntax is correct

3. **Method not found errors**:
   - Update method calls in blade templates
   - Ensure proper method names in JavaScript
   - Check event dispatching syntax

4. **Testing failures**:
   - Update test class references
   - Check assertion methods
   - Verify component instantiation

### Migration Validation

After migration, validate that everything works:

```php
// Create a simple test to verify migration
test('migration validation', function () {
    $venue = Venue::factory()->create();
    
    // Test form component
    $form = Livewire::test(CreateEditForm::class)
        ->set('name', 'Migration Test')
        ->set('venue_id', $venue->id)
        ->call('save');
    
    expect(Event::where('name', 'Migration Test')->exists())->toBeTrue();
    
    // Test table component
    $table = Livewire::test(EventsTable::class);
    $table->assertSee('Migration Test');
    
    // Test modal component
    $modal = Livewire::test(FormModal::class)
        ->call('openModal')
        ->set('form.name', 'Modal Test')
        ->set('form.venue_id', $venue->id)
        ->call('save');
    
    expect(Event::where('name', 'Modal Test')->exists())->toBeTrue();
});
```

## Post-Migration Steps

### 1. Update Documentation

- Update internal documentation references
- Update README files
- Update API documentation
- Update deployment guides

### 2. Team Communication

- Notify team members of the changes
- Update development guidelines
- Schedule training sessions if needed
- Update code review checklists

### 3. Deployment

- Test migrations in staging environment
- Plan deployment strategy
- Update deployment scripts
- Monitor for issues after deployment

### 4. Monitoring

- Monitor application performance
- Check error logs for migration issues
- Verify all functionality works as expected
- Collect user feedback

## Benefits of Migration

### Code Quality
- ✅ Consistent naming conventions
- ✅ Improved type safety with generics
- ✅ Better code organization
- ✅ Reduced code duplication

### Maintainability
- ✅ Easier to understand component structure
- ✅ Standardized patterns across the application
- ✅ Better separation of concerns
- ✅ Improved testing capabilities

### Developer Experience
- ✅ Better IDE support with type hints
- ✅ Easier onboarding for new developers
- ✅ Consistent development patterns
- ✅ Improved debugging capabilities

### Performance
- ✅ Optimized query patterns
- ✅ Better caching strategies
- ✅ Reduced memory usage
- ✅ Faster component rendering

## Related Documentation

- [Component Architecture](../../architecture/livewire/component-architecture.md) - New architecture patterns
- [Testing Guide](testing-guide.md) - Updated testing approaches
- [Best Practices](testing-best-practices.md) - Development best practices
- [Form Patterns](../../architecture/livewire/form-patterns.md) - Form implementation patterns
- [Modal Patterns](../../architecture/livewire/modal-patterns.md) - Modal implementation patterns