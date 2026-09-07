<?php

declare(strict_types=1);

use App\Models\Events\Venue;

test('orders venues alphabetically by name', function () {
    // Arrange
    Venue::factory()->create(['name' => 'Zenith Arena']);
    Venue::factory()->create(['name' => 'Capitol Center']);
    Venue::factory()->create(['name' => 'Metro Hall']);

    // Act
    $query = Venue::query();
    $query->alphabetical();
    $venues = $query->get();

    // Assert
    expect($venues->pluck('name')->all())->toBe([
        'Capitol Center',
        'Metro Hall',
        'Zenith Arena',
    ]);
});

test('remains chainable with other query constraints', function () {
    // Arrange
    $zenith = Venue::factory()->create(['name' => 'Zenith Arena', 'city' => 'Chicago']);
    $capitol = Venue::factory()->create(['name' => 'Capitol Center', 'city' => 'Chicago']);
    Venue::factory()->create(['name' => 'Metro Hall', 'city' => 'Boston']);
    Venue::factory()->trashed()->create(['name' => 'Closed Arena', 'city' => 'Chicago']);

    // Act
    $query = Venue::query();
    $query->alphabetical();
    $query->where('city', 'Chicago');
    $venues = $query->get();

    // Assert
    expect($venues->modelKeys())->toBe([$capitol->id, $zenith->id]);
});
