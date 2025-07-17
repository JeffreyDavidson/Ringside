<?php

declare(strict_types=1);

use App\Livewire\Events\Forms\Form;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Users\User;
use Carbon\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
});

describe('Form Validation Rules', function () {
    it('validates required fields', function () {
        $form = Livewire::test(Form::class)
            ->set('name', '')
            ->set('date', '')
            ->set('venue_id', '')
            ->call('store');

        $form->assertHasErrors([
            'name' => 'required',
            'date' => 'required',
            'venue_id' => 'required',
        ]);
    });

    it('validates event name uniqueness', function () {
        Event::factory()->create(['name' => 'Existing Event']);

        $form = Livewire::test(Form::class)
            ->set('name', 'Existing Event')
            ->set('date', '2024-12-31')
            ->set('venue_id', Venue::factory()->create()->id)
            ->call('store');

        $form->assertHasErrors(['name' => 'unique']);
    });

    it('validates date format', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Test Event')
            ->set('date', 'invalid-date')
            ->set('venue_id', Venue::factory()->create()->id)
            ->call('store');

        $form->assertHasErrors(['date' => 'date']);
    });

    it('validates venue exists', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Test Event')
            ->set('date', '2024-12-31')
            ->set('venue_id', 999)
            ->call('store');

        $form->assertHasErrors(['venue_id' => 'exists']);
    });

    it('validates date is not in the past', function () {
        $yesterday = Carbon::yesterday()->toDateString();
        $venue = Venue::factory()->create();

        $form = Livewire::test(Form::class)
            ->set('name', 'Test Event')
            ->set('date', $yesterday)
            ->set('venue_id', $venue->id)
            ->call('store');

        $form->assertHasErrors(['date' => 'after']);
    });

    it('allows future dates', function () {
        $tomorrow = Carbon::tomorrow()->toDateString();
        $venue = Venue::factory()->create();

        $form = Livewire::test(Form::class)
            ->set('name', 'Test Event')
            ->set('date', $tomorrow)
            ->set('venue_id', $venue->id)
            ->call('store');

        $form->assertHasNoErrors();
    });
});

describe('Form Field Validation', function () {
    it('validates name maximum length', function () {
        $longName = str_repeat('a', 256);
        $venue = Venue::factory()->create();

        $form = Livewire::test(Form::class)
            ->set('name', $longName)
            ->set('date', '2024-12-31')
            ->set('venue_id', $venue->id)
            ->call('store');

        $form->assertHasErrors(['name' => 'max']);
    });

    it('validates description maximum length', function () {
        $longDescription = str_repeat('a', 1001);
        $venue = Venue::factory()->create();

        $form = Livewire::test(Form::class)
            ->set('name', 'Test Event')
            ->set('date', '2024-12-31')
            ->set('venue_id', $venue->id)
            ->set('description', $longDescription)
            ->call('store');

        $form->assertHasErrors(['description' => 'max']);
    });

    it('validates preview maximum length', function () {
        $longPreview = str_repeat('a', 501);
        $venue = Venue::factory()->create();

        $form = Livewire::test(Form::class)
            ->set('name', 'Test Event')
            ->set('date', '2024-12-31')
            ->set('venue_id', $venue->id)
            ->set('preview', $longPreview)
            ->call('store');

        $form->assertHasErrors(['preview' => 'max']);
    });

    it('accepts valid optional fields', function () {
        $venue = Venue::factory()->create();

        $form = Livewire::test(Form::class)
            ->set('name', 'Test Event')
            ->set('date', '2024-12-31')
            ->set('venue_id', $venue->id)
            ->set('description', 'Valid description')
            ->set('preview', 'Valid preview')
            ->call('store');

        $form->assertHasNoErrors();
    });
});

describe('Form Store Operations', function () {
    it('can store valid event data', function () {
        $venue = Venue::factory()->create();

        $form = Livewire::test(Form::class)
            ->set('name', 'WrestleMania 40')
            ->set('date', '2024-04-06')
            ->set('venue_id', $venue->id)
            ->set('description', 'The grandest stage of them all')
            ->set('preview', 'Epic wrestling event')
            ->call('store');

        $form->assertHasNoErrors();
        $form->assertDispatched('eventCreated');

        $this->assertDatabaseHas('events', [
            'name' => 'WrestleMania 40',
            'date' => '2024-04-06',
            'venue_id' => $venue->id,
            'description' => 'The grandest stage of them all',
            'preview' => 'Epic wrestling event',
        ]);
    });

    it('stores event with minimal required data', function () {
        $venue = Venue::factory()->create();

        $form = Livewire::test(Form::class)
            ->set('name', 'Basic Event')
            ->set('date', '2024-12-31')
            ->set('venue_id', $venue->id)
            ->call('store');

        $form->assertHasNoErrors();

        $this->assertDatabaseHas('events', [
            'name' => 'Basic Event',
            'date' => '2024-12-31',
            'venue_id' => $venue->id,
        ]);
    });

    it('converts date string to Carbon instance', function () {
        $venue = Venue::factory()->create();

        $form = Livewire::test(Form::class)
            ->set('name', 'Test Event')
            ->set('date', '2024-04-06')
            ->set('venue_id', $venue->id)
            ->call('store');

        $form->assertHasNoErrors();

        $event = Event::where('name', 'Test Event')->first();
        expect($event->date)->toBeInstanceOf(Carbon::class);
        expect($event->date->toDateString())->toBe('2024-04-06');
    });
});

describe('Form Update Operations', function () {
    it('can update existing event', function () {
        $venue1 = Venue::factory()->create();
        $venue2 = Venue::factory()->create();
        $event = Event::factory()->create([
            'name' => 'Original Event',
            'date' => '2024-01-01',
            'venue_id' => $venue1->id,
        ]);

        $form = Livewire::test(Form::class)
            ->call('setModel', $event)
            ->set('name', 'Updated Event')
            ->set('date', '2024-02-01')
            ->set('venue_id', $venue2->id)
            ->call('update');

        $form->assertHasNoErrors();
        $form->assertDispatched('eventUpdated');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'name' => 'Updated Event',
            'date' => '2024-02-01',
            'venue_id' => $venue2->id,
        ]);
    });

    it('validates uniqueness excluding current event when updating', function () {
        $venue = Venue::factory()->create();
        $event1 = Event::factory()->create(['name' => 'Event One']);
        $event2 = Event::factory()->create(['name' => 'Event Two']);

        $form = Livewire::test(Form::class)
            ->call('setModel', $event2)
            ->set('name', 'Event One')
            ->set('date', '2024-12-31')
            ->set('venue_id', $venue->id)
            ->call('update');

        $form->assertHasErrors(['name' => 'unique']);
    });

    it('allows keeping same name when updating', function () {
        $venue = Venue::factory()->create();
        $event = Event::factory()->create([
            'name' => 'Test Event',
            'date' => '2024-01-01',
            'venue_id' => $venue->id,
        ]);

        $form = Livewire::test(Form::class)
            ->call('setModel', $event)
            ->set('name', 'Test Event')
            ->set('date', '2024-02-01')
            ->set('venue_id', $venue->id)
            ->call('update');

        $form->assertHasNoErrors();
        $form->assertDispatched('eventUpdated');
    });
});

describe('Form Business Logic', function () {
    it('validates date change rules for existing events', function () {
        $venue = Venue::factory()->create();
        $event = Event::factory()->past()->create([
            'date' => '2023-01-01',
            'venue_id' => $venue->id,
        ]);

        $form = Livewire::test(Form::class)
            ->call('setModel', $event)
            ->set('name', $event->name)
            ->set('date', '2024-12-31')
            ->set('venue_id', $venue->id)
            ->call('update');

        // Should use DateCanBeChanged rule
        $form->assertHasNoErrors();
    });

    it('handles venue relationship correctly', function () {
        $venue = Venue::factory()->create(['name' => 'Test Arena']);

        $form = Livewire::test(Form::class)
            ->set('name', 'Test Event')
            ->set('date', '2024-12-31')
            ->set('venue_id', $venue->id)
            ->call('store');

        $form->assertHasNoErrors();

        $event = Event::where('name', 'Test Event')->first();
        expect($event->venue->name)->toBe('Test Arena');
    });

    it('validates venue is active', function () {
        $inactiveVenue = Venue::factory()->inactive()->create();

        $form = Livewire::test(Form::class)
            ->set('name', 'Test Event')
            ->set('date', '2024-12-31')
            ->set('venue_id', $inactiveVenue->id)
            ->call('store');

        $form->assertHasErrors(['venue_id']);
    });
});

describe('Form State Management', function () {
    it('resets form after successful store', function () {
        $venue = Venue::factory()->create();

        $form = Livewire::test(Form::class)
            ->set('name', 'Test Event')
            ->set('date', '2024-12-31')
            ->set('venue_id', $venue->id)
            ->call('store');

        $form->assertHasNoErrors();
        $form->assertSet('name', null);
        $form->assertSet('date', null);
        $form->assertSet('venue_id', null);
    });

    it('preserves form state when validation fails', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Test Event')
            ->set('date', 'invalid-date')
            ->call('store');

        $form->assertHasErrors();
        $form->assertSet('name', 'Test Event');
        $form->assertSet('date', 'invalid-date');
    });

    it('loads existing model data correctly', function () {
        $venue = Venue::factory()->create();
        $event = Event::factory()->create([
            'name' => 'Test Event',
            'date' => '2024-01-01',
            'venue_id' => $venue->id,
            'description' => 'Test description',
        ]);

        $form = Livewire::test(Form::class)
            ->call('setModel', $event);

        $form->assertSet('name', 'Test Event');
        $form->assertSet('date', '2024-01-01');
        $form->assertSet('venue_id', $venue->id);
        $form->assertSet('description', 'Test description');
    });
});