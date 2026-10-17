<?php

declare(strict_types=1);

use App\Enums\Tournaments\EntrantKind;
use App\Enums\Tournaments\TournamentStatus;
use App\Models\Tournaments\Tournament;

test('mass assignment accepts the tournament setup and ignores ownership, request key, status and version', function (): void {
    // Arrange
    $tournament = new Tournament;

    // Act
    $tournament->fill([
        'name' => 'Crown Cup',
        'description' => 'Annual',
        'entrant_kind' => EntrantKind::TagTeam,
        'capacity' => 8,
        'repeat_appearances' => true,
        'promotion_id' => 99,
        'request_id' => 'forged',
        'status' => TournamentStatus::Cancelled,
        'version' => 9,
    ]);

    // Assert
    expect(array_keys($tournament->getAttributes()))->toEqualCanonicalizing(['name', 'description', 'entrant_kind', 'capacity', 'repeat_appearances']);
});

test('casts survive a persisted round trip', function (): void {
    // Arrange
    $created = Tournament::factory()
        ->create([
            'entrant_kind' => EntrantKind::TagTeam,
            'status' => TournamentStatus::Active,
            'capacity' => 16,
            'repeat_appearances' => true,
            'version' => 5,
        ]);

    // Act
    $tournament = Tournament::query()->findOrFail($created->id);

    // Assert
    expect($tournament->entrant_kind)->toBe(EntrantKind::TagTeam)
        ->and($tournament->status)->toBe(TournamentStatus::Active)
        ->and($tournament->capacity)->toBe(16)
        ->and($tournament->repeat_appearances)->toBeTrue()
        ->and($tournament->version)->toBe(5);
});
