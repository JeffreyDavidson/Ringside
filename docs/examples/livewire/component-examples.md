# Livewire Component Examples

## Overview

This document provides real-world examples of Livewire components from the Ringside codebase, demonstrating best practices and implementation patterns for forms, modals, tables, and actions components.

## Form Component Examples

### Event Form Component

Complete implementation of an event form with validation and relationships:

```php
// app/Livewire/Events/Forms/CreateEditForm.php
<?php

namespace App\Livewire\Events\Forms;

use App\Livewire\Forms\BaseForm;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use Illuminate\Database\Eloquent\Model;

class CreateEditForm extends BaseForm
{
    public string $name = '';
    public string $date = '';
    public string $time = '';
    public int $venue_id = 0;
    public string $preview = '';
    public bool $published = false;
    
    protected function getModelClass(): string
    {
        return Event::class;
    }
    
    protected function getRules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'venue_id' => 'required|exists:venues,id',
            'preview' => 'nullable|string|max:1000',
            'published' => 'boolean',
        ];
        
        // Add unique validation for create, exclude current for edit
        if ($this->model) {
            $rules['name'] .= '|unique:events,name,' . $this->model->id;
        } else {
            $rules['name'] .= '|unique:events,name';
        }
        
        return $rules;
    }
    
    protected function getModelData(): array
    {
        $datetime = $this->date . ' ' . $this->time;
        
        return [
            'name' => $this->name,
            'date' => $datetime,
            'venue_id' => $this->venue_id,
            'preview' => $this->preview,
            'published' => $this->published,
        ];
    }
    
    protected function afterSave(Model $model): void
    {
        $eventName = $this->model ? 'eventUpdated' : 'eventCreated';
        $this->dispatch($eventName, $model->id);
        
        // Send notification if published
        if ($model->published) {
            $this->dispatch('eventPublished', $model->id);
        }
    }
    
    public function mount(?Event $event = null): void
    {
        if ($event) {
            $this->setModel($event);
            $this->name = $event->name;
            $this->date = $event->date->format('Y-m-d');
            $this->time = $event->date->format('H:i');
            $this->venue_id = $event->venue_id;
            $this->preview = $event->preview ?? '';
            $this->published = $event->published;
        }
    }
    
    public function getVenuesProperty()
    {
        return Venue::orderBy('name')->get();
    }
    
    public function updatedVenueId($value)
    {
        // Clear any validation errors when venue is selected
        $this->resetErrorBag('venue_id');
    }
    
    public function generateDummyData(): void
    {
        $this->name = 'Demo Event ' . now()->format('Y-m-d H:i:s');
        $this->date = now()->addDays(30)->format('Y-m-d');
        $this->time = '19:00';
        $this->venue_id = $this->venues->random()->id;
        $this->preview = 'This is a demo event created for testing purposes.';
        $this->published = false;
    }
}
```

### Wrestler Form Component

Example with image uploads and complex validation:

```php
// app/Livewire/Wrestlers/Forms/CreateEditForm.php
<?php

namespace App\Livewire\Wrestlers\Forms;

use App\Livewire\Forms\BaseForm;
use App\Models\Wrestlers\Wrestler;
use App\Traits\ManagesEmployment;
use Illuminate\Database\Eloquent\Model;
use Livewire\WithFileUploads;

class CreateEditForm extends BaseForm
{
    use WithFileUploads, ManagesEmployment;
    
    public string $name = '';
    public string $slug = '';
    public int $height = 0;
    public int $weight = 0;
    public string $hometown = '';
    public string $signature_move = '';
    public $photo;
    public bool $active = true;
    
    // Employment fields from trait
    public ?string $employed_from = null;
    public ?string $employed_until = null;
    
    protected function getModelClass(): string
    {
        return Wrestler::class;
    }
    
    protected function getRules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'height' => 'required|integer|min:1',
            'weight' => 'required|integer|min:1',
            'hometown' => 'required|string|max:255',
            'signature_move' => 'required|string|max:255',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'active' => 'boolean',
        ];
        
        // Add employment validation from trait
        $rules = array_merge($rules, $this->getEmploymentRules());
        
        // Add unique validation
        $uniqueRule = $this->model ? 
            'unique:wrestlers,slug,' . $this->model->id : 
            'unique:wrestlers,slug';
        $rules['slug'] .= '|' . $uniqueRule;
        
        return $rules;
    }
    
    protected function getModelData(): array
    {
        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'height' => $this->height,
            'weight' => $this->weight,
            'hometown' => $this->hometown,
            'signature_move' => $this->signature_move,
            'active' => $this->active,
        ];
        
        // Add employment data from trait
        $data = array_merge($data, $this->getEmploymentData());
        
        // Handle photo upload
        if ($this->photo) {
            $data['photo'] = $this->photo->store('wrestlers', 'public');
        }
        
        return $data;
    }
    
    protected function afterSave(Model $model): void
    {
        $eventName = $this->model ? 'wrestlerUpdated' : 'wrestlerCreated';
        $this->dispatch($eventName, $model->id);
        
        // Clear photo after successful save
        $this->photo = null;
    }
    
    public function mount(?Wrestler $wrestler = null): void
    {
        if ($wrestler) {
            $this->setModel($wrestler);
            $this->name = $wrestler->name;
            $this->slug = $wrestler->slug;
            $this->height = $wrestler->height;
            $this->weight = $wrestler->weight;
            $this->hometown = $wrestler->hometown;
            $this->signature_move = $wrestler->signature_move;
            $this->active = $wrestler->active;
            
            // Load employment data from trait
            $this->loadEmploymentData($wrestler);
        }
    }
    
    public function updatedName($value)
    {
        // Auto-generate slug from name
        $this->slug = str($value)->slug();
    }
    
    public function generateDummyData(): void
    {
        $names = ['John Doe', 'Jane Smith', 'Mike Johnson', 'Sarah Wilson'];
        $hometowns = ['New York, NY', 'Los Angeles, CA', 'Chicago, IL', 'Houston, TX'];
        $moves = ['Suplex', 'DDT', 'Powerbomb', 'Submission Hold'];
        
        $this->name = fake()->randomElement($names);
        $this->slug = str($this->name)->slug();
        $this->height = fake()->numberBetween(165, 210);
        $this->weight = fake()->numberBetween(70, 150);
        $this->hometown = fake()->randomElement($hometowns);
        $this->signature_move = fake()->randomElement($moves);
        $this->active = true;
        
        // Generate dummy employment data
        $this->generateDummyEmploymentData();
    }
}
```

## Modal Component Examples

### Event Form Modal

Simple modal integrating with form component:

```php
// app/Livewire/Events/Modals/FormModal.php
<?php

namespace App\Livewire\Events\Modals;

use App\Livewire\Events\Forms\CreateEditForm;
use App\Livewire\Modals\BaseFormModal;
use App\Models\Events\Event;

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
    
    protected function getModalTitle(): string
    {
        return $this->model ? 'Edit Event' : 'Create Event';
    }
    
    protected function afterSave(): void
    {
        $this->dispatch('refresh-table');
        $this->dispatch('show-notification', 'Event saved successfully!');
    }
}
```

### Wrestler Form Modal

Modal with additional validation and file upload handling:

```php
// app/Livewire/Wrestlers/Modals/FormModal.php
<?php

namespace App\Livewire\Wrestlers\Modals;

use App\Livewire\Modals\BaseFormModal;
use App\Livewire\Wrestlers\Forms\CreateEditForm;
use App\Models\Wrestlers\Wrestler;

class FormModal extends BaseFormModal
{
    protected function getFormClass(): string
    {
        return CreateEditForm::class;
    }
    
    protected function getModelClass(): string
    {
        return Wrestler::class;
    }
    
    protected function getModalPath(): string
    {
        return 'livewire.wrestlers.modals.form-modal';
    }
    
    protected function getModalTitle(): string
    {
        return $this->model ? 'Edit Wrestler' : 'Create Wrestler';
    }
    
    protected function beforeSave(): bool
    {
        // Additional validation before save
        if ($this->form->photo && $this->form->photo->getSize() > 2048000) {
            $this->form->addError('photo', 'Photo must be less than 2MB');
            return false;
        }
        
        return parent::beforeSave();
    }
    
    protected function afterSave(): void
    {
        $this->dispatch('refresh-table');
        
        $message = $this->model ? 
            'Wrestler updated successfully!' : 
            'Wrestler created successfully!';
            
        $this->dispatch('show-notification', $message);
        
        // Refresh wrestler roster if active
        if ($this->form->active) {
            $this->dispatch('refresh-roster');
        }
    }
}
```

## Table Component Examples

### Events Table

Comprehensive table with filtering, sorting, and actions:

```php
// app/Livewire/Events/Tables/Main.php
<?php

namespace App\Livewire\Events\Tables;

use App\Livewire\Tables\BaseTable;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use Illuminate\Database\Eloquent\Builder;

class Main extends BaseTable
{
    protected function getModelClass(): string
    {
        return Event::class;
    }
    
    protected function getColumns(): array
    {
        return [
            'name' => 'Event Name',
            'date' => 'Date',
            'venue.name' => 'Venue',
            'status' => 'Status',
            'published' => 'Published',
        ];
    }
    
    protected function getFilters(): array
    {
        return [
            'status' => [
                'label' => 'Status',
                'options' => [
                    'scheduled' => 'Scheduled',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ],
            ],
            'venue_id' => [
                'label' => 'Venue',
                'options' => Venue::orderBy('name')->pluck('name', 'id')->toArray(),
            ],
            'published' => [
                'label' => 'Published',
                'options' => [
                    '1' => 'Published',
                    '0' => 'Draft',
                ],
            ],
        ];
    }
    
    protected function getQuery(): Builder
    {
        return Event::with('venue')
            ->when($this->filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($this->filters['venue_id'] ?? null, function ($query, $venueId) {
                $query->where('venue_id', $venueId);
            })
            ->when(isset($this->filters['published']), function ($query) {
                $query->where('published', (bool) $this->filters['published']);
            });
    }
    
    protected function applySearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhereHas('venue', function ($venueQuery) use ($search) {
                  $venueQuery->where('name', 'like', "%{$search}%");
              });
        });
    }
    
    protected function getDefaultSort(): array
    {
        return ['date', 'desc'];
    }
    
    public function getDateColumnAttribute($record): string
    {
        return $record->date->format('M j, Y g:i A');
    }
    
    public function getStatusColumnAttribute($record): string
    {
        $statusClasses = [
            'scheduled' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
        ];
        
        $class = $statusClasses[$record->status] ?? 'bg-gray-100 text-gray-800';
        
        return "<span class=\"px-2 py-1 text-xs font-medium rounded-full {$class}\">" . 
               ucfirst($record->status) . 
               "</span>";
    }
    
    public function getPublishedColumnAttribute($record): string
    {
        return $record->published ? 
            '<span class="text-green-600">✓</span>' : 
            '<span class="text-gray-400">—</span>';
    }
    
    public function togglePublished($eventId): void
    {
        $event = Event::findOrFail($eventId);
        $event->update(['published' => !$event->published]);
        
        $status = $event->published ? 'published' : 'unpublished';
        $this->dispatch('show-notification', "Event {$status} successfully!");
    }
    
    public function bulkDelete(array $ids): void
    {
        Event::whereIn('id', $ids)->delete();
        $this->dispatch('show-notification', count($ids) . ' events deleted successfully!');
        $this->selectedRecords = [];
    }
    
    public function export(string $format = 'csv'): void
    {
        $events = $this->getQuery()->get();
        
        $this->dispatch('download-file', [
            'filename' => 'events.' . $format,
            'data' => $events->toArray(),
            'format' => $format,
        ]);
    }
}
```

### Wrestlers Table

Table with image display and complex status management:

```php
// app/Livewire/Wrestlers/Tables/Main.php
<?php

namespace App\Livewire\Wrestlers\Tables;

use App\Livewire\Tables\BaseTable;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;

class Main extends BaseTable
{
    protected function getModelClass(): string
    {
        return Wrestler::class;
    }
    
    protected function getColumns(): array
    {
        return [
            'photo' => 'Photo',
            'name' => 'Name',
            'height' => 'Height',
            'weight' => 'Weight',
            'hometown' => 'Hometown',
            'signature_move' => 'Signature Move',
            'employment_status' => 'Status',
            'active' => 'Active',
        ];
    }
    
    protected function getFilters(): array
    {
        return [
            'active' => [
                'label' => 'Active Status',
                'options' => [
                    '1' => 'Active',
                    '0' => 'Inactive',
                ],
            ],
            'employment_status' => [
                'label' => 'Employment Status',
                'options' => [
                    'employed' => 'Employed',
                    'unemployed' => 'Unemployed',
                    'suspended' => 'Suspended',
                    'injured' => 'Injured',
                ],
            ],
        ];
    }
    
    protected function getQuery(): Builder
    {
        return Wrestler::query()
            ->when(isset($this->filters['active']), function ($query) {
                $query->where('active', (bool) $this->filters['active']);
            })
            ->when($this->filters['employment_status'] ?? null, function ($query, $status) {
                $query->whereEmploymentStatus($status);
            });
    }
    
    protected function applySearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('hometown', 'like', "%{$search}%")
              ->orWhere('signature_move', 'like', "%{$search}%");
        });
    }
    
    public function getPhotoColumnAttribute($record): string
    {
        if ($record->photo) {
            return "<img src=\"{$record->photo_url}\" alt=\"{$record->name}\" class=\"w-12 h-12 rounded-full object-cover\">";
        }
        
        return '<div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-xs">No Photo</div>';
    }
    
    public function getHeightColumnAttribute($record): string
    {
        return $record->height . ' cm';
    }
    
    public function getWeightColumnAttribute($record): string
    {
        return $record->weight . ' kg';
    }
    
    public function getEmploymentStatusColumnAttribute($record): string
    {
        $statusClasses = [
            'employed' => 'bg-green-100 text-green-800',
            'unemployed' => 'bg-gray-100 text-gray-800',
            'suspended' => 'bg-yellow-100 text-yellow-800',
            'injured' => 'bg-red-100 text-red-800',
        ];
        
        $status = $record->employment_status;
        $class = $statusClasses[$status] ?? 'bg-gray-100 text-gray-800';
        
        return "<span class=\"px-2 py-1 text-xs font-medium rounded-full {$class}\">" . 
               ucfirst($status) . 
               "</span>";
    }
    
    public function getActiveColumnAttribute($record): string
    {
        return $record->active ? 
            '<span class="text-green-600">✓</span>' : 
            '<span class="text-red-600">✗</span>';
    }
    
    public function toggleActive($wrestlerId): void
    {
        $wrestler = Wrestler::findOrFail($wrestlerId);
        $wrestler->update(['active' => !$wrestler->active]);
        
        $status = $wrestler->active ? 'activated' : 'deactivated';
        $this->dispatch('show-notification', "Wrestler {$status} successfully!");
    }
}
```

## Actions Component Examples

### Event Actions

Actions component with conditional visibility and confirmation:

```php
// app/Livewire/Events/Components/Actions.php
<?php

namespace App\Livewire\Events\Components;

use App\Livewire\Components\BaseActions;
use App\Models\Events\Event;
use Livewire\Component;

class Actions extends Component
{
    public Event $event;
    public bool $showConfirmation = false;
    
    public function mount(Event $event): void
    {
        $this->event = $event;
    }
    
    public function render()
    {
        return view('livewire.events.components.actions-component');
    }
    
    public function edit(): void
    {
        $this->dispatch('open-edit-modal', $this->event->id);
    }
    
    public function duplicate(): void
    {
        $newEvent = $this->event->replicate();
        $newEvent->name = 'Copy of ' . $this->event->name;
        $newEvent->published = false;
        $newEvent->save();
        
        $this->dispatch('event-duplicated', $newEvent->id);
        $this->dispatch('refresh-table');
        $this->dispatch('show-notification', 'Event duplicated successfully!');
    }
    
    public function confirmDelete(): void
    {
        if ($this->event->isPast()) {
            $this->dispatch('show-error', 'Cannot delete past events');
            return;
        }
        
        $this->showConfirmation = true;
    }
    
    public function cancelDelete(): void
    {
        $this->showConfirmation = false;
    }
    
    public function delete(): void
    {
        $this->event->delete();
        
        $this->dispatch('event-deleted', $this->event->id);
        $this->dispatch('refresh-table');
        $this->dispatch('show-notification', 'Event deleted successfully!');
        
        $this->showConfirmation = false;
    }
    
    public function togglePublished(): void
    {
        if (!$this->event->canBePublished()) {
            $this->dispatch('show-error', 'Event cannot be published without venue and matches');
            return;
        }
        
        $this->event->update(['published' => !$this->event->published]);
        
        $status = $this->event->published ? 'published' : 'unpublished';
        $this->dispatch('show-notification', "Event {$status} successfully!");
        $this->dispatch('refresh-table');
    }
    
    public function cancel(): void
    {
        if ($this->event->status === 'completed') {
            $this->dispatch('show-error', 'Cannot cancel completed events');
            return;
        }
        
        $this->event->update(['status' => 'cancelled']);
        
        $this->dispatch('event-cancelled', $this->event->id);
        $this->dispatch('refresh-table');
        $this->dispatch('show-notification', 'Event cancelled successfully!');
    }
    
    public function reschedule(): void
    {
        $this->dispatch('open-reschedule-modal', $this->event->id);
    }
    
    public function getCanEditProperty(): bool
    {
        return auth()->user()->can('update', $this->event);
    }
    
    public function getCanDeleteProperty(): bool
    {
        return auth()->user()->can('delete', $this->event) && !$this->event->isPast();
    }
    
    public function getCanPublishProperty(): bool
    {
        return auth()->user()->can('publish', $this->event);
    }
    
    public function getCanCancelProperty(): bool
    {
        return auth()->user()->can('cancel', $this->event) && 
               $this->event->status !== 'completed';
    }
}
```

### Wrestler Actions

Actions with employment status management:

```php
// app/Livewire/Wrestlers/Components/Actions.php
<?php

namespace App\Livewire\Wrestlers\Components;

use App\Models\Wrestlers\Wrestler;
use Livewire\Component;

class Actions extends Component
{
    public Wrestler $wrestler;
    public bool $showConfirmation = false;
    public string $confirmationAction = '';
    
    public function mount(Wrestler $wrestler): void
    {
        $this->wrestler = $wrestler;
    }
    
    public function render()
    {
        return view('livewire.wrestlers.components.actions-component');
    }
    
    public function edit(): void
    {
        $this->dispatch('open-edit-modal', $this->wrestler->id);
    }
    
    public function viewProfile(): void
    {
        $this->dispatch('open-profile-modal', $this->wrestler->id);
    }
    
    public function toggleActive(): void
    {
        $this->wrestler->update(['active' => !$this->wrestler->active]);
        
        $status = $this->wrestler->active ? 'activated' : 'deactivated';
        $this->dispatch('show-notification', "Wrestler {$status} successfully!");
        $this->dispatch('refresh-table');
    }
    
    public function employ(): void
    {
        $this->wrestler->employ();
        
        $this->dispatch('wrestler-employed', $this->wrestler->id);
        $this->dispatch('refresh-table');
        $this->dispatch('show-notification', 'Wrestler employed successfully!');
    }
    
    public function release(): void
    {
        $this->confirmationAction = 'release';
        $this->showConfirmation = true;
    }
    
    public function suspend(): void
    {
        $this->confirmationAction = 'suspend';
        $this->showConfirmation = true;
    }
    
    public function confirmAction(): void
    {
        switch ($this->confirmationAction) {
            case 'release':
                $this->wrestler->release();
                $this->dispatch('show-notification', 'Wrestler released successfully!');
                break;
                
            case 'suspend':
                $this->wrestler->suspend();
                $this->dispatch('show-notification', 'Wrestler suspended successfully!');
                break;
                
            case 'delete':
                $this->wrestler->delete();
                $this->dispatch('show-notification', 'Wrestler deleted successfully!');
                break;
        }
        
        $this->dispatch('refresh-table');
        $this->cancelConfirmation();
    }
    
    public function cancelConfirmation(): void
    {
        $this->showConfirmation = false;
        $this->confirmationAction = '';
    }
    
    public function confirmDelete(): void
    {
        if ($this->wrestler->hasMatches()) {
            $this->dispatch('show-error', 'Cannot delete wrestler with match history');
            return;
        }
        
        $this->confirmationAction = 'delete';
        $this->showConfirmation = true;
    }
    
    public function getCanEditProperty(): bool
    {
        return auth()->user()->can('update', $this->wrestler);
    }
    
    public function getCanDeleteProperty(): bool
    {
        return auth()->user()->can('delete', $this->wrestler) && 
               !$this->wrestler->hasMatches();
    }
    
    public function getCanManageEmploymentProperty(): bool
    {
        return auth()->user()->can('manage-employment', $this->wrestler);
    }
    
    public function getConfirmationMessageProperty(): string
    {
        return match ($this->confirmationAction) {
            'release' => 'Are you sure you want to release this wrestler?',
            'suspend' => 'Are you sure you want to suspend this wrestler?',
            'delete' => 'Are you sure you want to delete this wrestler? This action cannot be undone.',
            default => '',
        };
    }
}
```

## Blade Template Examples

### Event Form Template

```blade
{{-- resources/views/livewire/events/forms/create-edit-form.blade.php --}}
<div class="space-y-6">
    <form wire:submit.prevent="save" class="space-y-4">
        {{-- Event Name --}}
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">
                Event Name <span class="text-red-500">*</span>
            </label>
            <input type="text" 
                   wire:model.lazy="name" 
                   id="name"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                   placeholder="Enter event name">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        
        {{-- Date and Time --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="date" class="block text-sm font-medium text-gray-700">
                    Date <span class="text-red-500">*</span>
                </label>
                <input type="date" 
                       wire:model="date" 
                       id="date"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            
            <div>
                <label for="time" class="block text-sm font-medium text-gray-700">
                    Time <span class="text-red-500">*</span>
                </label>
                <input type="time" 
                       wire:model="time" 
                       id="time"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('time') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        
        {{-- Venue --}}
        <div>
            <label for="venue_id" class="block text-sm font-medium text-gray-700">
                Venue <span class="text-red-500">*</span>
            </label>
            <select wire:model="venue_id" 
                    id="venue_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">Select a venue</option>
                @foreach($this->venues as $venue)
                    <option value="{{ $venue->id }}">{{ $venue->name }} - {{ $venue->city }}</option>
                @endforeach
            </select>
            @error('venue_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        
        {{-- Preview --}}
        <div>
            <label for="preview" class="block text-sm font-medium text-gray-700">
                Event Preview
            </label>
            <textarea wire:model="preview" 
                      id="preview"
                      rows="4"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                      placeholder="Enter event description..."></textarea>
            @error('preview') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        
        {{-- Published --}}
        <div class="flex items-center">
            <input type="checkbox" 
                   wire:model="published" 
                   id="published"
                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
            <label for="published" class="ml-2 block text-sm text-gray-900">
                Publish event immediately
            </label>
        </div>
        
        {{-- Form Actions --}}
        <div class="flex justify-between items-center pt-4">
            <button type="button" 
                    wire:click="generateDummyData"
                    class="text-sm text-gray-500 hover:text-gray-700">
                Fill with dummy data
            </button>
            
            <div class="flex space-x-3">
                <button type="button" 
                        wire:click="cancel"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </button>
                
                <button type="submit" 
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    {{ $model ? 'Update' : 'Create' }} Event
                </button>
            </div>
        </div>
    </form>
</div>
```

### Events Table Template

```blade
{{-- resources/views/livewire/events/tables/events-table.blade.php --}}
<div class="space-y-4">
    {{-- Search and Filters --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-2 md:space-y-0">
        <div class="flex-1 max-w-md">
            <input type="text" 
                   wire:model.live="search" 
                   placeholder="Search events..."
                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>
        
        <div class="flex items-center space-x-2">
            {{-- Status Filter --}}
            <select wire:model.live="filters.status" 
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">All Status</option>
                @foreach($this->getFilterOptions('status') as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            
            {{-- Venue Filter --}}
            <select wire:model.live="filters.venue_id" 
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">All Venues</option>
                @foreach($this->getFilterOptions('venue_id') as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            
            {{-- Clear Filters --}}
            <button wire:click="resetFilters" 
                    class="text-sm text-gray-500 hover:text-gray-700">
                Clear
            </button>
        </div>
    </div>
    
    {{-- Table --}}
    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
        <table class="min-w-full divide-y divide-gray-300">
            <thead class="bg-gray-50">
                <tr>
                    @foreach($this->getColumns() as $column => $label)
                        <th scope="col" 
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                            wire:click="sortBy('{{ $column }}')">
                            {{ $label }}
                            @if($sortBy === $column)
                                <span class="ml-1">
                                    @if($sortDirection === 'asc')
                                        ↑
                                    @else
                                        ↓
                                    @endif
                                </span>
                            @endif
                        </th>
                    @endforeach
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($this->getRecords() as $event)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $event->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $event->date->format('M j, Y g:i A') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $event->venue->name ?? 'No venue' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {!! $this->getStatusColumnAttribute($event) !!}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {!! $this->getPublishedColumnAttribute($event) !!}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <livewire:events.components.actions-component 
                                :event="$event" 
                                :key="'event-actions-' . $event->id" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                            No events found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    {{-- Pagination --}}
    @if($this->getRecords()->hasPages())
        <div class="px-4 py-3 bg-white border-t border-gray-200 sm:px-6">
            {{ $this->getRecords()->links() }}
        </div>
    @endif
</div>
```

## Testing Examples

### Form Component Test

```php
// tests/Integration/Livewire/Events/Forms/CreateEditFormTest.php
<?php

use App\Livewire\Events\Forms\CreateEditForm;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Users\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
});

describe('CreateEditForm Configuration', function () {
    test('returns correct model class', function () {
        $form = new CreateEditForm();
        expect($form->getModelClass())->toBe(Event::class);
    });
    
    test('has correct validation rules', function () {
        $form = new CreateEditForm();
        $rules = $form->getRules();
        
        expect($rules)->toHaveKey('name');
        expect($rules)->toHaveKey('date');
        expect($rules)->toHaveKey('venue_id');
        expect($rules['name'])->toContain('required');
        expect($rules['venue_id'])->toContain('exists:venues,id');
    });
});

describe('CreateEditForm Creation', function () {
    test('creates event with valid data', function () {
        $venue = Venue::factory()->create();
        
        $component = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Event')
            ->set('date', '2024-12-01')
            ->set('time', '19:00')
            ->set('venue_id', $venue->id)
            ->set('preview', 'Test preview')
            ->call('save');
        
        expect(Event::where('name', 'Test Event')->exists())->toBeTrue();
        $component->assertDispatched('eventCreated');
    });
    
    test('validates required fields', function () {
        $component = Livewire::test(CreateEditForm::class)
            ->call('save');
        
        $component->assertHasErrors(['name', 'date', 'time', 'venue_id']);
    });
    
    test('validates unique event name', function () {
        $venue = Venue::factory()->create();
        Event::factory()->create(['name' => 'Existing Event']);
        
        $component = Livewire::test(CreateEditForm::class)
            ->set('name', 'Existing Event')
            ->set('date', '2024-12-01')
            ->set('time', '19:00')
            ->set('venue_id', $venue->id)
            ->call('save');
        
        $component->assertHasErrors(['name']);
    });
});

describe('CreateEditForm Editing', function () {
    test('updates existing event', function () {
        $venue = Venue::factory()->create();
        $event = Event::factory()->create([
            'name' => 'Original Event',
            'venue_id' => $venue->id,
        ]);
        
        $component = Livewire::test(CreateEditForm::class)
            ->call('mount', $event)
            ->set('name', 'Updated Event')
            ->call('save');
        
        expect($event->fresh()->name)->toBe('Updated Event');
        $component->assertDispatched('eventUpdated');
    });
    
    test('allows same name when editing', function () {
        $venue = Venue::factory()->create();
        $event = Event::factory()->create([
            'name' => 'Test Event',
            'venue_id' => $venue->id,
        ]);
        
        $component = Livewire::test(CreateEditForm::class)
            ->call('mount', $event)
            ->set('name', 'Test Event') // Same name should be allowed
            ->call('save');
        
        $component->assertHasNoErrors();
    });
});
```

### Table Component Test

```php
// tests/Integration/Livewire/Events/Tables/EventsTableTest.php
<?php

use App\Livewire\Events\Tables\EventsTable;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Users\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
});

describe('EventsTable Configuration', function () {
    test('returns correct model class', function () {
        $table = new EventsTable();
        expect($table->getModelClass())->toBe(Event::class);
    });
    
    test('has correct columns', function () {
        $table = new EventsTable();
        $columns = $table->getColumns();
        
        expect($columns)->toHaveKey('name');
        expect($columns)->toHaveKey('date');
        expect($columns)->toHaveKey('venue.name');
        expect($columns)->toHaveKey('status');
    });
});

describe('EventsTable Data Display', function () {
    test('displays events correctly', function () {
        $venue = Venue::factory()->create(['name' => 'Test Arena']);
        $event = Event::factory()->create([
            'name' => 'Test Event',
            'venue_id' => $venue->id,
        ]);
        
        $component = Livewire::test(EventsTable::class);
        
        $component->assertSee('Test Event');
        $component->assertSee('Test Arena');
    });
    
    test('displays empty state when no events', function () {
        $component = Livewire::test(EventsTable::class);
        
        $component->assertSee('No events found');
    });
});

describe('EventsTable Filtering', function () {
    test('filters events by status', function () {
        Event::factory()->create(['status' => 'scheduled', 'name' => 'Scheduled Event']);
        Event::factory()->create(['status' => 'completed', 'name' => 'Completed Event']);
        
        $component = Livewire::test(EventsTable::class)
            ->set('filters.status', 'scheduled');
        
        $component->assertSee('Scheduled Event');
        $component->assertDontSee('Completed Event');
    });
    
    test('searches events by name', function () {
        Event::factory()->create(['name' => 'WrestleMania']);
        Event::factory()->create(['name' => 'SummerSlam']);
        
        $component = Livewire::test(EventsTable::class)
            ->set('search', 'WrestleMania');
        
        $component->assertSee('WrestleMania');
        $component->assertDontSee('SummerSlam');
    });
});
```

## Related Documentation

- [Component Architecture](../../architecture/livewire/component-architecture.md) - Architecture patterns
- [Testing Guide](../testing/testing-guide.md) - Testing approaches
- [Form Patterns](../../architecture/livewire/form-patterns.md) - Form implementation
- [Modal Patterns](../../architecture/livewire/modal-patterns.md) - Modal implementation
- [Migration Guide](migration-guide.md) - Migration from old patterns