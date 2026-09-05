<?php

declare(strict_types=1);

use App\Livewire\Matches\Modals\FormModal;
use App\Models\Roster\Referees\Referee;

it('returns referees keyed by their identifiers', function (): void {
    // Arrange
    $referee = Referee::factory()->create([
        'first_name' => 'Earl',
        'last_name' => 'Hebner',
    ]);
    $referee->refresh();

    // Act
    $referees = app(FormModal::class)->getReferees();

    // Assert
    expect($referees)->toBe([$referee->id => $referee->full_name]);
});
