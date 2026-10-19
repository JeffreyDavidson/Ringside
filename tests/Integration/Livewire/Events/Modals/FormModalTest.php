<?php

declare(strict_types=1);

use App\Enums\Matches\MatchFinish;
use App\Livewire\Events\Modals\FormModal;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('authorized event form interactions', function () {
    beforeEach(function () {
        actingAs(administrator());
    });

    it('renders the event form fields and venue choices', function () {
        Venue::factory()->create(['name' => 'Madison Square Garden']);

        $modal = livewire(FormModal::class);

        $modal->assertSuccessful();
        $modal->assertViewIs('livewire.events.modals.form-modal');
        $modal
            ->assertPropertyWired('form.name')
            ->assertPropertyWired('form.date')
            ->assertPropertyWired('form.venue_id')
            ->assertPropertyWired('form.preview')
            ->assertSee('Madison Square Garden');
    });

    it('opens an empty form for creating an event', function () {
        $modal = livewire(FormModal::class);

        $modal
            ->assertSet('form.name', '')
            ->assertSet('form.date', '')
            ->assertSet('form.venue_id', 0)
            ->assertSet('form.preview', '')
            ->assertSee('Add Event');
    });

    it('loads an existing event for editing', function () {
        $venue = Venue::factory()->create();
        $eventDate = now()->addWeek()->startOfSecond();
        $event = Event::factory()->for($venue)->create([
            'name' => 'Summer Showcase',
            'date' => $eventDate,
            'preview' => 'A championship showcase.',
        ]);

        $modal = livewire(FormModal::class, ['modelId' => $event->id]);
        $modal->set('form.name', 'Summer Showcase');

        $modal
            ->assertSet('form.name', 'Summer Showcase')
            ->assertSet('form.date', $eventDate->format('Y-m-d\\TH:i'))
            ->assertSet('form.venue_id', $venue->id)
            ->assertSet('form.preview', 'A championship showcase.')
            ->assertSeeHtml('type="datetime-local"')
            ->assertSee('Edit Summer Showcase');
    });

    it('responds not found when opening a missing event', function () {
        livewire(FormModal::class, ['modelId' => PHP_INT_MAX])
            ->assertNotFound();
    });

    it('creates a scheduled event and closes the modal', function () {
        $venue = Venue::factory()->create();
        $eventDate = now()->addMonth()->startOfMinute();
        $modal = livewire(FormModal::class);

        $modal->set([
            'form.name' => 'WrestleMania 40',
            'form.date' => $eventDate->format('Y-m-d\\TH:i'),
            'form.venue_id' => $venue->id,
            'form.preview' => 'The biggest event of the year.',
        ]);
        $modal->call('save');

        $event = Event::query()->whereName('WrestleMania 40')->firstOrFail();
        expect($event->date?->toDateTimeString())->toBe($eventDate->toDateTimeString())
            ->and($event->venue_id)->toBe($venue->id)
            ->and($event->preview)->toBe('The biggest event of the year.');
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('refreshDatatable')
            ->assertDispatched('closeModal');
    });

    it('creates an unscheduled event without a venue', function () {
        $modal = livewire(FormModal::class);

        $modal->set([
            'form.name' => 'Future Announcement',
            'form.date' => null,
            'form.venue_id' => null,
            'form.preview' => null,
        ]);
        $modal->call('save');

        $event = Event::query()->whereName('Future Announcement')->firstOrFail();
        expect($event->date)->toBeNull()
            ->and($event->venue_id)->toBeNull()
            ->and($event->preview)->toBeNull();
        $modal->assertHasNoErrors();
    });

    it('allows creating historical event records', function () {
        $eventDate = today()->subDay();
        $modal = livewire(FormModal::class);

        $modal->set([
            'form.name' => 'Historical Event',
            'form.date' => $eventDate->toDateString(),
            'form.venue_id' => null,
        ]);
        $modal->call('save');

        $event = Event::query()->whereName('Historical Event')->firstOrFail();
        expect($event->date?->toDateString())->toBe($eventDate->toDateString());
        $modal->assertHasNoErrors();
    });

    it('updates an existing event', function () {
        $originalVenue = Venue::factory()->create();
        $updatedVenue = Venue::factory()->create();
        $event = Event::factory()->for($originalVenue)->future()->create([
            'name' => 'Original Event',
            'preview' => null,
        ]);
        $updatedDate = now()->addMonths(2)->startOfMinute();
        $modal = livewire(FormModal::class, ['modelId' => $event->id]);

        $modal->set([
            'form.name' => 'Updated Event',
            'form.date' => $updatedDate->format('Y-m-d\\TH:i'),
            'form.venue_id' => $updatedVenue->id,
            'form.preview' => 'Updated preview.',
        ]);
        $modal->call('save');

        $event->refresh();
        expect($event->name)->toBe('Updated Event')
            ->and($event->date?->toDateTimeString())->toBe($updatedDate->toDateTimeString())
            ->and($event->venue_id)->toBe($updatedVenue->id)
            ->and($event->preview)->toBe('Updated preview.');
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('refreshDatatable')
            ->assertDispatched('closeModal');
    });

    it('keeps the edit form open after a venue scheduling conflict and allows recovery', function () {
        $originalVenue = Venue::factory()->create();
        $conflictingVenue = Venue::factory()->create();
        $originalDate = now()->addMonth()->startOfMinute();
        $conflictingDate = now()->addMonths(2)->startOfMinute();
        $event = Event::factory()->for($originalVenue)->create([
            'name' => 'Original Event',
            'date' => $originalDate,
        ]);
        Event::factory()->for($conflictingVenue)->create(['date' => $conflictingDate]);
        $modal = livewire(FormModal::class, ['modelId' => $event->id]);

        $modal->set([
            'form.name' => 'Rescheduled Event',
            'form.date' => $conflictingDate->format('Y-m-d\\TH:i'),
            'form.venue_id' => $conflictingVenue->id,
        ]);
        $modal->call('save');

        $modal
            ->assertHasErrors(['form.venue_id'])
            ->assertSee("Venue [{$conflictingVenue->name}] is already booked on {$conflictingDate->format('M j, Y')} (venue time).")
            ->assertNotDispatched('closeModal');
        expect($event->refresh()->name)->toBe('Original Event')
            ->and($event->date?->toDateTimeString())->toBe($originalDate->toDateTimeString())
            ->and($event->venue_id)->toBe($originalVenue->id);

        $availableDate = $conflictingDate->copy()->addDay();
        $modal->set('form.date', $availableDate->format('Y-m-d\\TH:i'));
        $modal->call('save');

        $modal
            ->assertHasNoErrors()
            ->assertDispatched('closeModal');
        expect($event->refresh()->name)->toBe('Rescheduled Event')
            ->and($event->date?->toDateTimeString())->toBe($availableDate->toDateTimeString())
            ->and($event->venue_id)->toBe($conflictingVenue->id);
    });

    it('requires an event name', function () {
        $modal = livewire(FormModal::class);

        $modal->set('form.name', '');
        $modal->call('save');

        $modal
            ->assertHasErrors(['form.name' => 'required'])
            ->assertNotDispatched('closeModal');
        expect(Event::query()->count())->toBe(0);
    });

    it('rejects invalid event field values', function (string $field, mixed $value, string $rule) {
        $modal = livewire(FormModal::class);

        $modal->set('form.name', 'Valid Event');
        $modal->set($field, $value);
        $modal->call('save');

        $modal->assertHasErrors([$field => $rule]);
        expect(Event::query()->count())->toBe(0);
    })->with([
        'long name' => ['form.name', str_repeat('a', 256), 'max'],
        'invalid date' => ['form.date', '2023-13-32', 'date'],
        'missing venue' => ['form.venue_id', PHP_INT_MAX, 'exists'],
    ]);

    it('rejects an event name already used by another event', function () {
        Event::factory()->create(['name' => 'Existing Event']);
        $event = Event::factory()->create(['name' => 'Editable Event']);
        $modal = livewire(FormModal::class, ['modelId' => $event->id]);

        $modal->set('form.name', 'Existing Event');
        $modal->call('save');

        $modal->assertHasErrors(['form.name' => 'unique']);
        expect($event->refresh()->name)->toBe('Editable Event');
    });

    it('keeps the date of a past event with a recorded result immutable', function () {
        // Arrange
        $event = Event::factory()->past()->create();
        EventMatch::factory()
            ->forEvent($event)
            ->create(['match_finish' => MatchFinish::NoDecision]);
        $originalDate = $event->date;
        $modal = livewire(FormModal::class, ['modelId' => $event->id]);

        // Act
        $modal->set('form.date', today()->addMonth()->toDateString());
        $modal->call('save');

        // Assert
        $modal->assertHasErrors(['form.date']);
        expect($event->refresh()->date?->toDateTimeString())
            ->toBe($originalDate?->toDateTimeString());
    });

    it('moves a past event that has no recorded results', function () {
        // Arrange
        $event = Event::factory()->past()->create();
        $modal = livewire(FormModal::class, ['modelId' => $event->id]);
        $newDate = today()->addMonth();

        // Act
        $modal->set('form.date', $newDate->toDateString());
        $modal->call('save');

        // Assert
        $modal->assertHasNoErrors();
        expect($event->refresh()->date?->toDateString())->toBe($newDate->toDateString());
    });

    it('generates valid dummy data that can create an event', function () {
        Venue::factory()->create();
        $modal = livewire(FormModal::class);

        $modal->call('fillDummyFields');
        $modal->call('save');

        $modal
            ->assertHasNoErrors()
            ->assertDispatched('refreshDatatable')
            ->assertDispatched('closeModal');
        expect(Event::query()->count())->toBe(1);
    });
});

it('forbids users without administrative access from opening the event form', function (string $actor, string $operation, int $status) {
    $event = $operation === 'update' ? Event::factory()->create() : null;

    if ($actor === 'basic user') {
        actingAs(basicUser());
    }

    livewire(FormModal::class, ['modelId' => $event?->id])
        ->assertStatus($status);
})->with([
    'guest creating' => ['guest', 'create', 403],
    'basic user creating' => ['basic user', 'create', 403],
    'guest updating' => ['guest', 'update', 403],
    'basic user updating' => ['basic user', 'update', 404],
]);

describe('event name races past the form rule', function () {
    beforeEach(function () {
        actingAs(administrator());
    });

    it('shows a name that only differs by leading space as taken on the name field', function () {
        // Arrange
        Event::factory()->create(['name' => 'Summer Slam']);
        $modal = livewire(FormModal::class);

        // Act
        $modal->set([
            'form.name' => ' Summer Slam',
            'form.venue_id' => null,
        ]);
        $modal->call('save');

        // Assert
        $modal
            ->assertHasErrors(['form.name'])
            ->assertSee("An event named 'Summer Slam' already exists in this promotion.")
            ->assertNotDispatched('closeModal');
        expect(Event::query()->where('name', 'Summer Slam')->count())->toBe(1);
    });
});

describe('events at deleted venues', function () {
    beforeEach(function () {
        actingAs(administrator());
    });

    it('saves a past event at a deleted venue and keeps that venue', function () {
        // Arrange
        $venue = Venue::factory()->create(['name' => 'Old Arena']);
        $event = Event::factory()->for($venue)->create(['name' => 'Old Show', 'date' => now()->subMonth()]);
        $venue->delete();
        $modal = livewire(FormModal::class, ['modelId' => $event->id]);

        // Act
        $modal->set('form.name', 'Renamed Show');
        $modal->call('save');

        // Assert
        $modal->assertHasNoErrors()->assertDispatched('closeModal');
        expect($event->refresh())->name->toBe('Renamed Show')->venue_id->toBe($venue->id);
    });

    it('offers the deleted venue of the edited event as an option', function () {
        // Arrange
        $venue = Venue::factory()->create(['name' => 'Old Arena']);
        $event = Event::factory()->for($venue)->create(['date' => now()->subMonth()]);
        $venue->delete();
        Venue::factory()->trashed()->create(['name' => 'Unrelated Deleted Arena']);

        // Act
        $modal = livewire(FormModal::class, ['modelId' => $event->id]);

        // Assert
        $modal->assertSee('Old Arena')->assertDontSee('Unrelated Deleted Arena');
    });

    it('refuses to create an event at a deleted venue', function () {
        // Arrange
        $venue = Venue::factory()->trashed()->create();
        $modal = livewire(FormModal::class);

        // Act
        $modal->set(['form.name' => 'Ghost Show', 'form.date' => null, 'form.venue_id' => $venue->id]);
        $modal->call('save');

        // Assert
        $modal->assertHasErrors(['form.venue_id'])->assertNotDispatched('closeModal');
        expect(Event::query()->whereName('Ghost Show')->exists())->toBeFalse();
    });

    it('refuses to move an event to a deleted venue', function () {
        // Arrange
        $venue = Venue::factory()->create();
        $event = Event::factory()->for($venue)->create(['date' => now()->subMonth()]);
        $deleted = Venue::factory()->trashed()->create();
        $modal = livewire(FormModal::class, ['modelId' => $event->id]);

        // Act
        $modal->set('form.venue_id', $deleted->id);
        $modal->call('save');

        // Assert
        $modal->assertHasErrors(['form.venue_id']);
        expect($event->refresh()->venue_id)->toBe($venue->id);
    });
});
