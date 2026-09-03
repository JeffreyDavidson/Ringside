<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Livewire\Events\Tables\Main;
use App\Models\Events\Event;
use App\Models\Events\Venue;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

it('renders event scheduling details for administrators', function () {
    $venue = Venue::factory()->create(['name' => 'Madison Square Garden']);
    $scheduledDate = now()->addDay()->hour(19);
    Event::factory()->atVenue($venue)->create([
        'name' => 'Future Showcase',
        'date' => $scheduledDate,
    ]);
    Event::factory()->past()->atVenue($venue)->create([
        'name' => 'Past Showcase',
    ]);
    Event::factory()->unscheduled()->create([
        'name' => 'Draft Showcase',
    ]);
    $deletedEvent = Event::factory()->unscheduled()->create([
        'name' => 'Deleted Showcase',
    ]);
    $deletedEvent->delete();
    actingAs(administrator());

    $table = livewire(Main::class);

    $table
        ->assertSuccessful()
        ->assertSee('Future Showcase')
        ->assertSee('Past Showcase')
        ->assertSee('Draft Showcase')
        ->assertSee($scheduledDate->format('Y-m-d'))
        ->assertSee('No Date Set')
        ->assertSee($venue->name)
        ->assertSee('No Venue')
        ->assertDontSee('Deleted Showcase');
});

it('forbids users without administrative access', function (string $actor) {
    if ($actor === 'basic user') {
        actingAs(basicUser());
    }

    $table = livewire(Main::class);

    $table->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);

it('searches events by name', function () {
    Event::factory()->unscheduled()->create(['name' => 'Summer Spectacular']);
    Event::factory()->unscheduled()->create(['name' => 'Winter Warfare']);
    actingAs(administrator());

    $table = livewire(Main::class);
    $table->set('search', 'Summer');

    $table
        ->assertSee('Summer Spectacular')
        ->assertDontSee('Winter Warfare');
});

it('filters events by scheduling status', function (
    EventStatus $status,
    string $visibleEvent,
    array $hiddenEvents,
) {
    Event::factory()->past()->create(['name' => 'Past Showcase']);
    Event::factory()->scheduled()->create(['name' => 'Scheduled Showcase']);
    Event::factory()->unscheduled()->create(['name' => 'Draft Showcase']);
    actingAs(administrator());

    $table = livewire(Main::class);
    $table->set('filterValues.status', $status->value);

    $table->assertSee($visibleEvent);
    foreach ($hiddenEvents as $hiddenEvent) {
        $table->assertDontSee($hiddenEvent);
    }
})->with([
    'past' => [EventStatus::Past, 'Past Showcase', ['Scheduled Showcase', 'Draft Showcase']],
    'scheduled' => [EventStatus::Scheduled, 'Scheduled Showcase', ['Past Showcase', 'Draft Showcase']],
    'unscheduled' => [EventStatus::Unscheduled, 'Draft Showcase', ['Past Showcase', 'Scheduled Showcase']],
]);

it('filters events by venue', function () {
    $selectedVenue = Venue::factory()->create(['name' => 'Selected Arena']);
    $otherVenue = Venue::factory()->create(['name' => 'Other Arena']);
    Event::factory()->scheduled()->atVenue($selectedVenue)->create([
        'name' => 'Selected Venue Event',
    ]);
    Event::factory()->scheduled()->atVenue($otherVenue)->create([
        'name' => 'Other Venue Event',
    ]);
    Event::factory()->scheduled()->create([
        'name' => 'No Venue Event',
    ]);
    actingAs(administrator());

    $table = livewire(Main::class);
    $table->set('filterValues.venue', (string) $selectedVenue->id);

    $table
        ->assertSee('Selected Venue Event')
        ->assertDontSee('Other Venue Event')
        ->assertDontSee('No Venue Event');
});

it('filters events within an inclusive date range', function () {
    Event::factory()->scheduledOn('2026-05-31 23:59:59')->create(['name' => 'Before Range']);
    Event::factory()->scheduledOn('2026-06-01 00:00:00')->create(['name' => 'Range Start']);
    Event::factory()->scheduledOn('2026-06-15 19:00:00')->create(['name' => 'Within Range']);
    Event::factory()->scheduledOn('2026-06-30 23:59:59')->create(['name' => 'Range End']);
    Event::factory()->scheduledOn('2026-07-01 00:00:00')->create(['name' => 'After Range']);
    actingAs(administrator());

    $table = livewire(Main::class);
    $table->set('filterValues.event_dates', [
        'minDate' => '2026-06-01',
        'maxDate' => '2026-06-30',
    ]);

    $table
        ->assertDontSee('Before Range')
        ->assertSee('Range Start')
        ->assertSee('Within Range')
        ->assertSee('Range End')
        ->assertDontSee('After Range');
});

it('orders dated events newest first and unscheduled events last', function () {
    Event::factory()->create([
        'name' => 'Later Event',
        'date' => now()->addDays(3),
    ]);
    Event::factory()->create([
        'name' => 'Earlier Event',
        'date' => now()->addDay(),
    ]);
    Event::factory()->unscheduled()->create([
        'name' => 'Unscheduled Event',
    ]);
    actingAs(administrator());

    $table = livewire(Main::class);

    $table->assertSeeInOrder([
        'Later Event',
        'Earlier Event',
        'Unscheduled Event',
    ]);
});

it('soft deletes an event and reports success', function () {
    $event = Event::factory()->unscheduled()->create();
    actingAs(administrator());

    $table = livewire(Main::class);
    $table->call('delete', $event);

    $table
        ->assertHasNoErrors()
        ->assertDispatched(
            'flash-message',
            type: 'status',
            message: __('events.actions.deleted'),
        );
    $this->assertSoftDeleted($event);
});

it('restores an event and reports success', function () {
    $event = Event::factory()->trashed()->create();
    actingAs(administrator());

    $table = livewire(Main::class);
    $table->call('restore', $event->id);

    $table
        ->assertHasNoErrors()
        ->assertDispatched(
            'flash-message',
            type: 'status',
            message: __('events.actions.restored'),
        )
        ->assertRedirectToRoute('events.index');
    $this->assertNotSoftDeleted($event);
});

it('renders an empty state when there are no events', function () {
    actingAs(administrator());

    $table = livewire(Main::class);

    $table
        ->assertSuccessful()
        ->assertSee('No records found.');
});
