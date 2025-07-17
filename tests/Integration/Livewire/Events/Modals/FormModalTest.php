<?php

declare(strict_types=1);

use App\Livewire\Events\Forms\Form;
use App\Livewire\Events\Modals\FormModal;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Users\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
});

describe('FormModal Configuration', function () {
    it('returns correct form class', function () {
        $modal = new FormModal();
        $reflection = new ReflectionClass($modal);
        $method = $reflection->getMethod('getFormClass');
        $method->setAccessible(true);

        expect($method->invoke($modal))->toBe(Form::class);
    });

    it('returns correct model class', function () {
        $modal = new FormModal();
        $reflection = new ReflectionClass($modal);
        $method = $reflection->getMethod('getModelClass');
        $method->setAccessible(true);

        expect($method->invoke($modal))->toBe(Event::class);
    });
});

describe('FormModal Rendering', function () {
    it('can render in create mode', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode');

        $component->assertOk();
    });

    it('can render in edit mode', function () {
        $event = Event::factory()->create();

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $event->id);

        $component->assertOk();
    });

    it('displays correct title in create mode', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode');

        $component->assertSee('Create Event');
    });

    it('displays correct title in edit mode', function () {
        $event = Event::factory()->create(['name' => 'Test Event']);

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $event->id);

        $component->assertSee('Edit Event');
    });

    it('presents venues list for selection', function () {
        $venue = Venue::factory()->create(['name' => 'Test Arena']);

        $component = Livewire::test(FormModal::class)
            ->call('createMode');

        $component->assertSee('Test Arena');
    });
});

describe('FormModal Create Operations', function () {
    it('can create a new event with valid data', function () {
        $venue = Venue::factory()->create();

        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'WrestleMania 40')
            ->set('form.date', '2024-04-06')
            ->set('form.venue_id', $venue->id)
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('eventCreated');

        $this->assertDatabaseHas('events', [
            'name' => 'WrestleMania 40',
            'date' => '2024-04-06',
            'venue_id' => $venue->id,
        ]);
    });

    it('validates required fields when creating', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', '')
            ->set('form.date', '')
            ->set('form.venue_id', '')
            ->call('save');

        $component->assertHasErrors([
            'form.name' => 'required',
            'form.date' => 'required',
            'form.venue_id' => 'required',
        ]);
    });

    it('validates event name uniqueness', function () {
        Event::factory()->create(['name' => 'Existing Event']);

        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'Existing Event')
            ->set('form.date', '2024-04-06')
            ->call('save');

        $component->assertHasErrors(['form.name']);
    });

    it('validates date format', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'Test Event')
            ->set('form.date', 'invalid-date')
            ->call('save');

        $component->assertHasErrors(['form.date']);
    });

    it('validates venue exists', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'Test Event')
            ->set('form.date', '2024-04-06')
            ->set('form.venue_id', 999)
            ->call('save');

        $component->assertHasErrors(['form.venue_id']);
    });

    it('validates date is not in the past', function () {
        $yesterday = Carbon::yesterday()->toDateString();
        $venue = Venue::factory()->create();

        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'Test Event')
            ->set('form.date', $yesterday)
            ->set('form.venue_id', $venue->id)
            ->call('save');

        $component->assertHasErrors(['form.date']);
    });
});

describe('FormModal Edit Operations', function () {
    it('can edit an existing event', function () {
        $venue1 = Venue::factory()->create();
        $venue2 = Venue::factory()->create();
        $event = Event::factory()->create([
            'name' => 'Original Event',
            'date' => '2024-04-06',
            'venue_id' => $venue1->id,
        ]);

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $event->id)
            ->set('form.name', 'Updated Event')
            ->set('form.date', '2024-04-07')
            ->set('form.venue_id', $venue2->id)
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('eventUpdated');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'name' => 'Updated Event',
            'date' => '2024-04-07',
            'venue_id' => $venue2->id,
        ]);
    });

    it('loads existing event data in edit mode', function () {
        $venue = Venue::factory()->create();
        $event = Event::factory()->create([
            'name' => 'Test Event',
            'date' => '2024-04-06',
            'venue_id' => $venue->id,
        ]);

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $event->id);

        $component->assertSet('form.name', 'Test Event');
        $component->assertSet('form.date', '2024-04-06');
        $component->assertSet('form.venue_id', $venue->id);
    });

    it('validates name uniqueness excluding current event when editing', function () {
        $event1 = Event::factory()->create(['name' => 'Event One']);
        $event2 = Event::factory()->create(['name' => 'Event Two']);

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $event2->id)
            ->set('form.name', 'Event One')
            ->call('save');

        $component->assertHasErrors(['form.name']);
    });

    it('allows keeping same name when editing', function () {
        $venue = Venue::factory()->create();
        $event = Event::factory()->create([
            'name' => 'Test Event',
            'date' => '2024-04-06',
            'venue_id' => $venue->id,
        ]);

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $event->id)
            ->set('form.name', 'Test Event')
            ->set('form.date', '2024-04-07')
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('eventUpdated');
    });

    it('validates date change rules for existing events', function () {
        $event = Event::factory()->past()->create();

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $event->id)
            ->set('form.date', '2025-01-01')
            ->call('save');

        // Should use DateCanBeChanged rule
        $component->assertHasNoErrors();
    });
});

describe('FormModal Venue Integration', function () {
    it('displays available venues in dropdown', function () {
        $venue1 = Venue::factory()->create(['name' => 'Arena One']);
        $venue2 = Venue::factory()->create(['name' => 'Arena Two']);

        $component = Livewire::test(FormModal::class)
            ->call('createMode');

        $component->assertSee('Arena One');
        $component->assertSee('Arena Two');
    });

    it('filters venues correctly', function () {
        $activeVenue = Venue::factory()->active()->create(['name' => 'Active Arena']);
        $inactiveVenue = Venue::factory()->inactive()->create(['name' => 'Inactive Arena']);

        $component = Livewire::test(FormModal::class)
            ->call('createMode');

        $component->assertSee('Active Arena');
        $component->assertDontSee('Inactive Arena');
    });

    it('shows venue details when selected', function () {
        $venue = Venue::factory()->create([
            'name' => 'Test Arena',
            'city' => 'Test City',
            'state' => 'Test State',
        ]);

        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.venue_id', $venue->id);

        $component->assertSee('Test Arena');
        $component->assertSee('Test City');
        $component->assertSee('Test State');
    });
});

describe('FormModal State Management', function () {
    it('resets form when switching modes', function () {
        $venue = Venue::factory()->create();
        $event = Event::factory()->create(['name' => 'Test Event']);

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $event->id)
            ->call('createMode');

        $component->assertSet('form.name', null);
        $component->assertSet('form.date', null);
        $component->assertSet('form.venue_id', null);
    });

    it('closes modal after successful save', function () {
        $venue = Venue::factory()->create();

        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'Test Event')
            ->set('form.date', '2024-04-06')
            ->set('form.venue_id', $venue->id)
            ->call('save');

        $component->assertDispatched('closeModal');
    });

    it('keeps modal open when validation fails', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', '')
            ->call('save');

        $component->assertNotDispatched('closeModal');
    });
});

describe('FormModal Business Logic', function () {
    it('handles event descriptions correctly', function () {
        $venue = Venue::factory()->create();

        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'Test Event')
            ->set('form.date', '2024-04-06')
            ->set('form.venue_id', $venue->id)
            ->set('form.description', 'Epic wrestling event')
            ->call('save');

        $component->assertHasNoErrors();

        $this->assertDatabaseHas('events', [
            'name' => 'Test Event',
            'description' => 'Epic wrestling event',
        ]);
    });

    it('handles promotional content fields', function () {
        $venue = Venue::factory()->create();

        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'Test Event')
            ->set('form.date', '2024-04-06')
            ->set('form.venue_id', $venue->id)
            ->set('form.preview', 'Event preview text')
            ->call('save');

        $component->assertHasNoErrors();

        $this->assertDatabaseHas('events', [
            'name' => 'Test Event',
            'preview' => 'Event preview text',
        ]);
    });
});

describe('FormModal Authorization', function () {
    it('requires authentication', function () {
        auth()->logout();

        $component = Livewire::test(FormModal::class)
            ->call('createMode');

        $component->assertUnauthorized();
    });

    it('requires administrator privileges', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test(FormModal::class)
            ->call('createMode');

        $component->assertUnauthorized();
    });
});