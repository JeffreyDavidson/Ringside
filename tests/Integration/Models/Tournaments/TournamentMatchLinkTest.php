<?php

declare(strict_types=1);

use App\Models\Tournaments\TournamentMatchLink;
use Illuminate\Database\Eloquent\MassAssignmentException;

test('mass assignment is refused for every column because links are bracket rows written only by the booking and reset actions', function (): void {
    // Arrange
    $link = new TournamentMatchLink;

    // Act / Assert
    expect(fn () => $link->fill([
        'promotion_id' => 99,
        'tournament_id' => 98,
        'node_id' => 97,
        'active_node_id' => 96,
        'match_id' => 95,
        'attempt' => 1,
    ]))
        ->toThrow(MassAssignmentException::class);
});

test('forMatchId returns only the link for that match', function (): void {
    // Arrange
    $wanted = TournamentMatchLink::factory()
        ->create();
    TournamentMatchLink::factory()
        ->create();

    // Act
    $links = TournamentMatchLink::query()
        ->forMatchId($wanted->match_id)
        ->get();

    // Assert
    expect($links->modelKeys())->toBe([$wanted->id]);
});
