<?php

declare(strict_types=1);

use App\Livewire\Venues\Tables\PreviousEvents;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->venue = Venue::factory()->create();
    actingAs(administrator());
});

describe('PreviousEvents configuration', function (): void {
    it('requires a venue', function (): void {
        // Act & Assert
        expect(fn () => (new PreviousEvents())->builder())
            ->toThrow(LogicException::class, 'A venue was not provided.');
    });
});

describe('PreviousEvents query', function (): void {
    it('returns only the selected venue events in reverse chronological order with unscheduled events last', function (): void {
        // Arrange
        $recentEvent = Event::factory()->atVenue($this->venue)->create([
            'date' => Date::parse('2026-08-15'),
        ]);
        $olderEvent = Event::factory()->atVenue($this->venue)->create([
            'date' => Date::parse('2026-07-15'),
        ]);
        $futureEvent = Event::factory()->atVenue($this->venue)->create([
            'date' => Date::parse('2026-09-15'),
        ]);
        $unscheduledEvent = Event::factory()->atVenue($this->venue)->unscheduled()->create();
        Event::factory()->atVenue(Venue::factory()->create())->create([
            'date' => Date::parse('2026-08-01'),
        ]);
        $table = new PreviousEvents();
        $table->venueId = $this->venue->id;

        // Act
        $events = $table->builder()->get();

        // Assert
        expect($events->modelKeys())->toBe([
            $futureEvent->id,
            $recentEvent->id,
            $olderEvent->id,
            $unscheduledEvent->id,
        ]);
    });
});

describe('PreviousEvents rendering', function (): void {
    it('renders event links, formatted dates, and search controls', function (): void {
        // Arrange
        $event = Event::factory()->atVenue($this->venue)->create([
            'name' => 'Linked Wrestling Event',
            'date' => Date::parse('2026-08-15 19:00:00'),
        ]);

        // Act
        $table = livewire(PreviousEvents::class, ['venueId' => $this->venue->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search events"')
            ->assertSee('Linked Wrestling Event')
            ->assertSeeHtml(route('events.show', $event))
            ->assertSee('2026-08-15');
    });

    it('searches the selected venue event history', function (): void {
        // Arrange
        Event::factory()->atVenue($this->venue)->create([
            'name' => 'Summer Spectacular',
        ]);
        Event::factory()->atVenue($this->venue)->create([
            'name' => 'Winter Warfare',
        ]);
        Event::factory()->atVenue(Venue::factory()->create())->create([
            'name' => 'Summer Elsewhere',
        ]);
        Event::factory()->atVenue($this->venue)->trashed()->create([
            'name' => 'Summer Deleted',
        ]);

        // Act
        $table = livewire(PreviousEvents::class, ['venueId' => $this->venue->id]);
        $table->set('search', 'Summer');

        // Assert
        $table
            ->assertSee('Summer Spectacular')
            ->assertDontSee('Winter Warfare')
            ->assertDontSee('Summer Elsewhere')
            ->assertDontSee('Summer Deleted');

        // Act
        $table->set('search', 'Elsewhere');

        // Assert
        $table
            ->assertSee('No records found.')
            ->assertDontSee('Summer Elsewhere');

        // Act
        $table->set('search', '');

        // Assert
        $table
            ->assertSee('Summer Spectacular')
            ->assertSee('Winter Warfare')
            ->assertDontSee('Summer Elsewhere')
            ->assertDontSee('Summer Deleted');
    });

    it('renders an empty state when the venue has no events', function (): void {
        // Act
        $table = livewire(PreviousEvents::class, ['venueId' => $this->venue->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSee('No records found.');
    });
});

describe('PreviousEvents authorization', function (): void {
    it('allows administrators to view venue event history', function (): void {
        // Act
        $table = livewire(PreviousEvents::class, ['venueId' => $this->venue->id]);

        // Assert
        $table->assertSuccessful();
    });

    it('forbids users without access to the venue', function (string $actor): void {
        // Arrange
        if ($actor === 'guest') {
            Auth::logout();
        } else {
            actingAs(basicUser());
        }

        // Act
        $table = livewire(PreviousEvents::class, ['venueId' => $this->venue->id]);

        // Assert
        $table->assertForbidden();
    })->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});
