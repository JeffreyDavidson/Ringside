<?php

declare(strict_types=1);

use App\Livewire\Stables\Modals\FormModal;
use App\Models\Roster\TagTeams\TagTeam;

it('returns non-deleted tag teams keyed by their identifiers', function (): void {
    // Arrange
    $tagTeam = TagTeam::factory()->create(['name' => 'The Example Team']);
    TagTeam::factory()->trashed()->create(['name' => 'The Deleted Team']);

    // Act
    $tagTeams = app(FormModal::class)->getTagTeams();

    // Assert
    expect($tagTeams)->toBe([$tagTeam->id => $tagTeam->name]);
});

it('returns no tag team options when only deleted tag teams exist', function (): void {
    // Arrange
    TagTeam::factory()->trashed()->create();
    $modal = app(FormModal::class);

    // Act
    $tagTeams = $modal->getTagTeams();

    // Assert
    expect($tagTeams)->toBe([]);
});
