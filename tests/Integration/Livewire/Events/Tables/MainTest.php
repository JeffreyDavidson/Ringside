<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Livewire\Events\Tables\Main;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

it('renders event scheduling details and excludes deleted events', function (): void {
    // Arrange
    $venue = Venue::factory()->create(['name' => 'Madison Square Garden']);
    $scheduledDate = Date::tomorrow()->hour(19);
    Event::factory()->atVenue($venue)->create([
        'name' => 'Future Showcase',
        'date' => $scheduledDate,
    ]);
    Event::factory()->past()->atVenue($venue)->create(['name' => 'Past Showcase']);
    Event::factory()->unscheduled()->create(['name' => 'Draft Showcase']);
    $deletedEvent = Event::factory()->unscheduled()->create(['name' => 'Deleted Showcase']);
    $deletedEvent->delete();

    // Act
    $component = livewire(Main::class);

    // Assert
    $component
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

it('searches events by name and clears the search', function (): void {
    // Arrange
    Event::factory()->unscheduled()->create(['name' => 'Summer Spectacular']);
    Event::factory()->unscheduled()->create(['name' => 'Winter Warfare']);
    $component = livewire(Main::class);

    // Act
    $component->set('search', 'Summer');

    // Assert
    $component
        ->assertSee('Summer Spectacular')
        ->assertDontSee('Winter Warfare');

    // Act
    $component->set('search', '');

    // Assert
    $component
        ->assertSee('Summer Spectacular')
        ->assertSee('Winter Warfare');
});

it('filters events by scheduling status', function (
    EventStatus $status,
    string $visibleEvent,
    string $firstHiddenEvent,
    string $secondHiddenEvent,
): void {
    // Arrange
    Event::factory()->past()->create(['name' => 'Past Showcase']);
    Event::factory()->scheduled()->create(['name' => 'Scheduled Showcase']);
    Event::factory()->unscheduled()->create(['name' => 'Draft Showcase']);
    $component = livewire(Main::class);

    // Act
    $component->set('filterValues.status', $status->value);

    // Assert
    $component
        ->assertSee($visibleEvent)
        ->assertDontSee($firstHiddenEvent)
        ->assertDontSee($secondHiddenEvent);
})->with([
    'past' => [EventStatus::Past, 'Past Showcase', 'Scheduled Showcase', 'Draft Showcase'],
    'scheduled' => [EventStatus::Scheduled, 'Scheduled Showcase', 'Past Showcase', 'Draft Showcase'],
    'unscheduled' => [EventStatus::Unscheduled, 'Draft Showcase', 'Past Showcase', 'Scheduled Showcase'],
]);

it('filters events by venue', function (): void {
    // Arrange
    $selectedVenue = Venue::factory()->create(['name' => 'Selected Arena']);
    $otherVenue = Venue::factory()->create(['name' => 'Other Arena']);
    Event::factory()->scheduled()->atVenue($selectedVenue)->create(['name' => 'Selected Venue Event']);
    Event::factory()->scheduled()->atVenue($otherVenue)->create(['name' => 'Other Venue Event']);
    Event::factory()->scheduled()->create(['name' => 'No Venue Event']);
    $component = livewire(Main::class);

    // Act
    $component->set('filterValues.venue', (string) $selectedVenue->id);

    // Assert
    $component
        ->assertSee('Selected Venue Event')
        ->assertDontSee('Other Venue Event')
        ->assertDontSee('No Venue Event');
});

it('filters events within an inclusive date range', function (): void {
    // Arrange
    Event::factory()->scheduledOn('2026-05-31 23:59:59')->create(['name' => 'Before Range']);
    Event::factory()->scheduledOn('2026-06-01 00:00:00')->create(['name' => 'Range Start']);
    Event::factory()->scheduledOn('2026-06-15 19:00:00')->create(['name' => 'Within Range']);
    Event::factory()->scheduledOn('2026-06-30 23:59:59')->create(['name' => 'Range End']);
    Event::factory()->scheduledOn('2026-07-01 00:00:00')->create(['name' => 'After Range']);
    $component = livewire(Main::class);

    // Act
    $component->set('filterValues.event_dates', [
        'minDate' => '2026-06-01',
        'maxDate' => '2026-06-30',
    ]);

    // Assert
    $component
        ->assertDontSee('Before Range')
        ->assertSee('Range Start')
        ->assertSee('Within Range')
        ->assertSee('Range End')
        ->assertDontSee('After Range');
});

it('orders dated events newest first and unscheduled events last', function (): void {
    // Arrange
    Event::factory()->create([
        'name' => 'Later Event',
        'date' => Date::now()->addDays(3),
    ]);
    Event::factory()->create([
        'name' => 'Earlier Event',
        'date' => Date::tomorrow(),
    ]);
    Event::factory()->unscheduled()->create(['name' => 'Unscheduled Event']);

    // Act
    $component = livewire(Main::class);

    // Assert
    $component->assertSeeInOrder([
        'Later Event',
        'Earlier Event',
        'Unscheduled Event',
    ]);
});

it('soft deletes an event and reports success', function (): void {
    // Arrange
    $event = Event::factory()->unscheduled()->create();
    $component = livewire(Main::class);

    // Act
    $component->call('delete', $event);

    // Assert
    $component
        ->assertHasNoErrors()
        ->assertDispatched(
            'flash-message',
            type: 'status',
            message: __('events.actions.deleted'),
        );
    $this->assertSoftDeleted($event);
});

it('restores an event and reports success', function (): void {
    // Arrange
    $event = Event::factory()->trashed()->create();
    $component = livewire(Main::class);

    // Act
    $component->call('restore', $event->id);

    // Assert
    $component
        ->assertHasNoErrors()
        ->assertDispatched(
            'flash-message',
            type: 'status',
            message: __('events.actions.restored'),
        )
        ->assertRedirectToRoute('events.index');
    $this->assertNotSoftDeleted($event);
});

it('renders an empty state when there are no events', function (): void {
    // Act
    $component = livewire(Main::class);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee('No records found.');
});

it('forbids users without administrative access', function (string $actor): void {
    // Arrange
    if ($actor === 'guest') {
        Auth::logout();
    } else {
        actingAs(basicUser());
    }

    // Act
    $component = livewire(Main::class);

    // Assert
    $component->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);
