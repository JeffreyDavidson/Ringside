<?php

declare(strict_types=1);

use App\Livewire\Venues\Tables\PreviousEvents;
use App\Models\Events\Event;
use App\Models\Events\Venue;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

it('configures a related history table for its resource', function () {
    actingAs(administrator());
    $venue = Venue::factory()->create();
    Event::factory()->atVenue($venue)->create([
        'name' => 'Historic Arena Event',
    ]);

    $table = livewire(PreviousEvents::class, ['venueId' => $venue->id]);

    $table
        ->assertSuccessful()
        ->assertSeeHtml('placeholder="Search events"')
        ->assertSee('Historic Arena Event');
});

it('restricts related history pagination to the shared options', function () {
    actingAs(administrator());
    $venue = Venue::factory()->create();
    $table = livewire(PreviousEvents::class, ['venueId' => $venue->id]);

    $table->set('perPage', 999);

    $table->assertSet('perPage', 5);
});
