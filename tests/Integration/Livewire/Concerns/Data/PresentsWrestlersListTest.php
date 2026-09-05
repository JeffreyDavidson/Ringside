<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Modals\FormModal;
use App\Models\Roster\Wrestlers\Wrestler;

it('returns wrestlers keyed by their identifiers', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create(['name' => 'Example Wrestler']);

    // Act
    $wrestlers = app(FormModal::class)->getWrestlers();

    // Assert
    expect($wrestlers)->toBe([$wrestler->id => $wrestler->name]);
});
