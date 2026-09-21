<?php

declare(strict_types=1);

use App\Livewire\Venues\Tables\PreviousEvents;
use App\Models\Events\Event;
use App\Models\Events\Venue;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('table configuration', function (): void {
    it('configures a related history table for its resource', function (): void {
        // Arrange
        $venue = Venue::factory()->create();
        Event::factory()->atVenue($venue)->create([
            'name' => 'Historic Arena Event',
        ]);

        // Act
        $table = livewire(PreviousEvents::class, ['venueId' => $venue->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search events"')
            ->assertSee('Historic Arena Event');
    });
});

describe('pagination', function (): void {
    it('accepts a shared pagination option', function (int $perPage): void {
        // Arrange
        $venue = Venue::factory()->create();
        $table = livewire(PreviousEvents::class, ['venueId' => $venue->id]);

        // Act
        $table->set('perPage', $perPage);

        // Assert
        $table->assertSet('perPage', $perPage);
    })->with([5, 10, 25, 50, 100]);

    it('rejects unsupported pagination options', function (int $perPage): void {
        // Arrange
        $venue = Venue::factory()->create();
        $table = livewire(PreviousEvents::class, ['venueId' => $venue->id]);

        // Act
        $table->set('perPage', $perPage);

        // Assert
        $table->assertSet('perPage', 5);
    })->with([0, 15, 999]);
});
