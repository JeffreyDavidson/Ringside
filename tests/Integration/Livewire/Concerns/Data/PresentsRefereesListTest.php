<?php

declare(strict_types=1);

use App\Livewire\Matches\Modals\FormModal;
use App\Models\Roster\Referees\Referee;

it('returns non-deleted referees keyed by their identifiers', function (): void {
    // Arrange
    $referee = Referee::factory()->create([
        'first_name' => 'Earl',
        'last_name' => 'Hebner',
    ]);
    $referee->refresh();
    Referee::factory()->trashed()->create([
        'first_name' => 'Deleted',
        'last_name' => 'Referee',
    ]);

    // Act
    $referees = app(FormModal::class)->getReferees();

    // Assert
    expect($referees)->toBe([$referee->id => $referee->full_name]);
});

it('returns no referee options when only deleted referees exist', function (): void {
    // Arrange
    Referee::factory()->trashed()->create();
    $modal = app(FormModal::class);

    // Act
    $referees = $modal->getReferees();

    // Assert
    expect($referees)->toBe([]);
});
