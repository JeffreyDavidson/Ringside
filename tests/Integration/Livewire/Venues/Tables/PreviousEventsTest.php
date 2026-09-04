<?php

declare(strict_types=1);

use App\Livewire\Venues\Tables\PreviousEvents;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAs(administrator());
});

it('forbids users without access to the venue', function (string $actor) {
    $venue = Venue::factory()->create();

    if ($actor === 'guest') {
        Auth::logout();
    } else {
        actingAs(basicUser());
    }

    $table = livewire(PreviousEvents::class, ['venueId' => $venue->id]);

    $table->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);

it('requires venue context', function () {
    expect(fn () => (new PreviousEvents())->builder())
        ->toThrow(LogicException::class, 'A venue was not provided.');
});

it('shows only the selected venue events in chronological order', function () {
    $venue = Venue::factory()->create();
    $otherVenue = Venue::factory()->create();
    Event::factory()->atVenue($venue)->create([
        'name' => 'Recent Wrestling Show',
        'date' => now()->subDays(5),
    ]);
    Event::factory()->atVenue($venue)->create([
        'name' => 'Classic Wrestling Event',
        'date' => now()->subDays(30),
    ]);
    Event::factory()->atVenue($venue)->create([
        'name' => 'Upcoming Wrestling Show',
        'date' => now()->addDays(10),
    ]);
    Event::factory()->atVenue($venue)->unscheduled()->create([
        'name' => 'Unscheduled Wrestling Show',
    ]);
    Event::factory()->atVenue($otherVenue)->create([
        'name' => 'Other Venue Event',
        'date' => now()->subDays(10),
    ]);

    $table = livewire(PreviousEvents::class, ['venueId' => $venue->id]);

    $table
        ->assertSuccessful()
        ->assertSeeInOrder([
            'Upcoming Wrestling Show',
            'Recent Wrestling Show',
            'Classic Wrestling Event',
            'Unscheduled Wrestling Show',
        ])
        ->assertDontSee('Other Venue Event');
});

it('renders event links and formatted dates', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->atVenue($venue)->create([
        'name' => 'Linked Wrestling Event',
        'date' => '2026-08-15 19:00:00',
    ]);

    $table = livewire(PreviousEvents::class, ['venueId' => $venue->id]);

    $table
        ->assertSuccessful()
        ->assertSeeHtml(route('events.show', $event))
        ->assertSee('2026-08-15');
});

it('searches the selected venue event history', function () {
    $venue = Venue::factory()->create();
    Event::factory()->atVenue($venue)->create([
        'name' => 'Summer Spectacular',
    ]);
    Event::factory()->atVenue($venue)->create([
        'name' => 'Winter Warfare',
    ]);

    $table = livewire(PreviousEvents::class, ['venueId' => $venue->id]);

    $table->set('search', 'Summer');

    $table
        ->assertSee('Summer Spectacular')
        ->assertDontSee('Winter Warfare');
});

it('renders an empty state when the venue has no events', function () {
    $venue = Venue::factory()->create();

    $table = livewire(PreviousEvents::class, ['venueId' => $venue->id]);

    $table
        ->assertSuccessful()
        ->assertSee('No records found.');
});
