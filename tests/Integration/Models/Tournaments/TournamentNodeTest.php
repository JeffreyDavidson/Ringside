<?php

declare(strict_types=1);

use App\Models\Tournaments\TournamentNode;

test('mass assignment accepts the walkover reason and ignores ownership, bracket position, seeds and links', function (): void {
    // Arrange
    $node = new TournamentNode;

    // Act
    $node->fill([
        'walkover_reason' => 'Opponent withdrew',
        'promotion_id' => 99,
        'tournament_id' => 98,
        'round' => 1,
        'position' => 1,
        'first_seed' => 1,
        'second_seed' => 2,
        'planned_event_id' => 97,
        'walkover_entry_id' => 96,
    ]);

    // Assert
    expect(array_keys($node->getAttributes()))->toBe(['walkover_reason']);
});
