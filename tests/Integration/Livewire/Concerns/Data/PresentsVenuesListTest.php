<?php

declare(strict_types=1);

use App\Livewire\Events\Modals\FormModal;
use App\Models\Events\Venue;

it('returns venues in alphabetical order keyed by their identifiers', function (): void {
    // Arrange
    $zeta = Venue::factory()->create(['name' => 'Zeta Arena']);
    $alpha = Venue::factory()->create(['name' => 'Alpha Arena']);

    // Act
    $venues = app(FormModal::class)->getVenues();

    // Assert
    expect($venues)->toBe([$alpha->id => $alpha->name, $zeta->id => $zeta->name]);
});
