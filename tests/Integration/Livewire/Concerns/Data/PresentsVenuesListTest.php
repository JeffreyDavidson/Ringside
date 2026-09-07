<?php

declare(strict_types=1);

use App\Livewire\Events\Modals\FormModal;
use App\Models\Events\Venue;

it('returns non-deleted venues in alphabetical order keyed by their identifiers', function (): void {
    // Arrange
    $zeta = Venue::factory()->create(['name' => 'Zeta Arena']);
    $alpha = Venue::factory()->create(['name' => 'Alpha Arena']);
    Venue::factory()->trashed()->create(['name' => 'Deleted Arena']);

    // Act
    $venues = app(FormModal::class)->getVenues();

    // Assert
    expect($venues)->toBe([$alpha->id => $alpha->name, $zeta->id => $zeta->name]);
});

it('returns no venue options when only deleted venues exist', function (): void {
    // Arrange
    Venue::factory()->trashed()->create();
    $modal = app(FormModal::class);

    // Act
    $venues = $modal->getVenues();

    // Assert
    expect($venues)->toBe([]);
});
