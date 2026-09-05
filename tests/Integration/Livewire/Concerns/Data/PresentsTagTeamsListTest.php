<?php

declare(strict_types=1);

use App\Livewire\Stables\Modals\FormModal;
use App\Models\Roster\TagTeams\TagTeam;

it('returns tag teams keyed by their identifiers', function () {
    // Arrange
    $tagTeam = TagTeam::factory()->create(['name' => 'The Example Team']);

    // Act
    $tagTeams = app(FormModal::class)->getTagTeams();

    // Assert
    expect($tagTeams)->toBe([$tagTeam->id => $tagTeam->name]);
});
