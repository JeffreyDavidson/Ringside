<?php

declare(strict_types=1);

use App\Models\Tournaments\TournamentEntry;
use Illuminate\Database\Eloquent\MassAssignmentException;

test('mass assignment is refused for every column because entries are bracket rows written only by the tournament actions', function (): void {
    // Arrange
    $entry = new TournamentEntry;

    // Act / Assert
    expect(fn () => $entry->fill([
        'promotion_id' => 99,
        'tournament_id' => 98,
        'seed' => 1,
        'competitor_type' => 'wrestler',
        'competitor_id' => 97,
        'withdrawn' => true,
    ]))
        ->toThrow(MassAssignmentException::class);
});

test('casts survive a persisted round trip', function (): void {
    // Arrange
    $created = TournamentEntry::factory()
        ->create([
            'seed' => 3,
            'withdrawn' => true,
        ]);

    // Act
    $entry = TournamentEntry::query()->findOrFail($created->id);

    // Assert
    expect($entry->seed)->toBe(3)
        ->and($entry->withdrawn)->toBeTrue();
});
