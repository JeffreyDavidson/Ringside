<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Modals\FormModal;
use App\Models\Roster\Wrestlers\Wrestler;

it('returns non-deleted wrestlers keyed by their identifiers', function (): void {
    // Arrange
    $wrestler = Wrestler::factory()->create(['name' => 'Example Wrestler']);
    Wrestler::factory()->trashed()->create(['name' => 'Deleted Wrestler']);

    // Act
    $wrestlers = app(FormModal::class)->getWrestlers();

    // Assert
    expect($wrestlers)->toBe([$wrestler->id => $wrestler->name]);
});

it('returns no wrestler options when only deleted wrestlers exist', function (): void {
    // Arrange
    Wrestler::factory()->trashed()->create();
    $modal = app(FormModal::class);

    // Act
    $wrestlers = $modal->getWrestlers();

    // Assert
    expect($wrestlers)->toBe([]);
});
