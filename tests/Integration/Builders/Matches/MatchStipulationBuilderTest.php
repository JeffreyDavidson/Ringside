<?php

declare(strict_types=1);

use App\Builders\Matches\MatchStipulationBuilder;
use App\Models\Matches\MatchStipulation;

test('match stipulations use the typed builder', function () {
    expect(MatchStipulation::query())->toBeInstanceOf(MatchStipulationBuilder::class);
});

test('it keeps only active stipulations', function () {
    // Arrange
    $active = MatchStipulation::factory()->active()->create();
    MatchStipulation::factory()->inactive()->create();

    // Act
    $stipulations = MatchStipulation::query()
        ->active()
        ->get();

    // Assert
    expect($stipulations->modelKeys())->toBe([$active->id]);
});

test('it orders stipulations alphabetically by name', function () {
    // Arrange
    MatchStipulation::factory()->create(['name' => 'Steel Cage']);
    MatchStipulation::factory()->create(['name' => 'Ladder']);
    MatchStipulation::factory()->create(['name' => 'Tables']);

    // Act
    $stipulations = MatchStipulation::query()
        ->alphabetical()
        ->get();

    // Assert
    expect($stipulations->pluck('name')->all())->toBe(['Ladder', 'Steel Cage', 'Tables']);
});
