<?php

declare(strict_types=1);

use App\Livewire\Events\Tables\Main;
use App\Models\Events\Event;
use App\Models\Events\Venue;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

it('returns non-deleted venues in alphabetical order keyed by their identifiers', function (): void {
    // Arrange
    actingAs(administrator());
    $zeta = Venue::factory()->create(['name' => 'Zeta Arena']);
    $alpha = Venue::factory()->create(['name' => 'Alpha Arena']);
    $deleted = Venue::factory()->trashed()->create(['name' => 'Deleted Arena']);
    Event::factory()->atVenue($zeta)->create();
    Event::factory()->atVenue($alpha)->create();
    Event::factory()->atVenue($deleted)->create();

    // Act
    $venues = livewire(Main::class)->instance()->getVenues();

    // Assert
    expect($venues)->toBe([$alpha->id => $alpha->name, $zeta->id => $zeta->name]);
});

it('returns no venue options when only deleted venues exist', function (): void {
    // Arrange
    actingAs(administrator());
    Event::factory()->atVenue(Venue::factory()->trashed()->create())->create();

    // Act
    $venues = livewire(Main::class)->instance()->getVenues();

    // Assert
    expect($venues)->toBeEmpty();
});
