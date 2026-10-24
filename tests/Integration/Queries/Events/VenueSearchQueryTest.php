<?php

declare(strict_types=1);

use App\Models\Events\Venue;
use App\Queries\Events\VenueSearchQuery;

test('finds venues whose name contains the term, A to Z, and treats wildcards literally', function (): void {
    // Arrange
    $zulu = Venue::factory()
        ->create(['name' => 'Zulu Arena']);
    $alpha = Venue::factory()
        ->create(['name' => 'Alpha Arena']);
    Venue::factory()
        ->create(['name' => 'Old Gym']);
    $percent = Venue::factory()
        ->create(['name' => '100% Arena']);

    // Act
    $arenas = resolve(VenueSearchQuery::class)->get('arena');
    $wildcard = resolve(VenueSearchQuery::class)->get('%');

    // Assert
    expect($arenas->modelKeys())->toBe([$percent->id, $alpha->id, $zulu->id])
        ->and($wildcard)->toHaveCount(4);
});

test('limits the results to twenty and leaves out deleted venues', function (): void {
    // Arrange
    Venue::factory()
        ->count(21)
        ->create();
    Venue::factory()
        ->trashed()
        ->create(['name' => 'Deleted Arena']);

    // Act
    $found = resolve(VenueSearchQuery::class)->get('');
    $deleted = resolve(VenueSearchQuery::class)->get('Deleted');

    // Assert
    expect($found)->toHaveCount(20)
        ->and($deleted)->toBeEmpty();
});

test('keeps the stored venue of an edited event even when it was deleted since', function (): void {
    // Arrange
    $stored = Venue::factory()
        ->trashed()
        ->create(['name' => 'Old Arena']);
    Venue::factory()
        ->trashed()
        ->create(['name' => 'Other Old Arena']);
    $live = Venue::factory()
        ->create(['name' => 'New Arena']);

    // Act
    $found = resolve(VenueSearchQuery::class)->get('arena', $stored->id);
    $unrelated = resolve(VenueSearchQuery::class)->get('arena');

    // Assert
    expect($found->modelKeys())->toBe([$live->id, $stored->id])
        ->and($unrelated->modelKeys())->toBe([$live->id]);
});
