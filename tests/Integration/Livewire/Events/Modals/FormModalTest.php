<?php

declare(strict_types=1);

use App\Livewire\Events\Modals\FormModal;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
        $modal->call('openModal');

        $modal
            ->assertSet('isModalOpen', true)
            ->assertSet('form.name', '')
            ->assertSet('form.date', '')
            ->assertSet('form.venue_id', 0)
            ->assertSet('form.preview', '')
            ->assertSee('Create Event');
    });

    it('loads an existing event for editing', function () {
        $venue = Venue::factory()->create();
        $eventDate = now()->addWeek()->startOfSecond();
        $event = Event::factory()->for($venue)->create([
            'name' => 'Summer Showcase',
            'date' => $eventDate,
            'preview' => 'A championship showcase.',
        ]);

        $modal = livewire(FormModal::class);
        $modal->call('openModal', $event->id);
        $modal->set('form.name', 'Summer Showcase');

        $modal
            ->assertSet('isModalOpen', true)
            ->assertSet('form.name', 'Summer Showcase')
            ->assertSet('form.date', $eventDate->toDateTimeString())
            ->assertSet('form.venue_id', $venue->id)
            ->assertSet('form.preview', 'A championship showcase.')
            ->assertSee('Edit Event');
    });

    it('propagates a missing event failure', function () {
        expect(fn () => livewire(FormModal::class)->call('openModal', PHP_INT_MAX))
            ->toThrow(ModelNotFoundException::class);
    });

    it('creates a scheduled event and closes the modal', function () {
        $venue = Venue::factory()->create();
        $eventDate = now()->addMonth()->startOfMinute();
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.name' => 'WrestleMania 40',
            'form.date' => $eventDate->toDateTimeString(),
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
            ->assertDispatched('form-submitted')
            ->assertDispatched('closeModal')
            ->assertSet('isModalOpen', false);
    });

    it('creates an unscheduled event without a venue', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
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

        $modal->call('openModal');
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
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $event->id);
        $modal->set([
            'form.name' => 'Updated Event',
            'form.date' => $updatedDate->toDateTimeString(),
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
            ->assertDispatched('form-submitted')
            ->assertSet('isModalOpen', false);
    });

    it('requires an event name', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set('form.name', '');
        $modal->call('save');

        $modal
            ->assertHasErrors(['form.name' => 'required'])
            ->assertNotDispatched('closeModal')
            ->assertSet('isModalOpen', true);
        expect(Event::query()->count())->toBe(0);
    });

    it('rejects invalid event field values', function (string $field, mixed $value, string $rule) {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
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
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $event->id);
        $modal->set('form.name', 'Existing Event');
        $modal->call('save');

        $modal->assertHasErrors(['form.name' => 'unique']);
        expect($event->refresh()->name)->toBe('Editable Event');
    });

    it('keeps the date of an occurred event immutable', function () {
        $event = Event::factory()->past()->create();
        $originalDate = $event->date;
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $event->id);
        $modal->set('form.date', today()->addMonth()->toDateString());
        $modal->call('save');

        $modal->assertHasErrors(['form.date']);
        expect($event->refresh()->date?->toDateTimeString())
            ->toBe($originalDate?->toDateTimeString());
    });

    it('resets existing event data when reopening in create mode', function () {
        $event = Event::factory()->future()->withPreview()->withVenue()->create();
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $event->id);
        $modal->call('openModal');

        $modal
            ->assertSet('form.name', '')
            ->assertSet('form.date', '')
            ->assertSet('form.venue_id', 0)
            ->assertSet('form.preview', '');
    });

    it('generates valid dummy data that can create an event', function () {
        Venue::factory()->create();
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->call('fillDummyFields');
        $modal->call('save');

        $modal
            ->assertHasNoErrors()
            ->assertDispatched('form-submitted')
            ->assertSet('isModalOpen', false);
        expect(Event::query()->count())->toBe(1);
    });
});

it('forbids users without administrative access from opening the event form', function (string $actor, string $operation) {
    $event = $operation === 'update' ? Event::factory()->create() : null;

    if ($actor === 'basic user') {
        actingAs(basicUser());
    }

    $modal = livewire(FormModal::class);
    $modal->call('openModal', $event?->id);

    $modal->assertForbidden();
})->with([
    'guest creating' => ['guest', 'create'],
    'basic user creating' => ['basic user', 'create'],
    'guest updating' => ['guest', 'update'],
    'basic user updating' => ['basic user', 'update'],
]);
