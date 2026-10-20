<?php

declare(strict_types=1);

use App\Models\Tournaments\TournamentNode;
use App\Services\Tournaments\TournamentBracketService;

it('lists a node\'s ancestors and descendants by round and position', function (int $capacity, int $round, int $position, array $ancestors, array $descendants): void {
    // Arrange
    $world = tournamentFixture(capacity: $capacity);
    $node = TournamentNode::query()
        ->where('tournament_id', $world['tournament']->id)
        ->where('round', $round)
        ->where('position', $position)
        ->firstOrFail();
    $service = resolve(TournamentBracketService::class);
    $label = fn (int $id): string => TournamentNode::query()->findOrFail($id)->round.':'.TournamentNode::query()->findOrFail($id)->position;

    // Act
    $ancestorIds = $service->ancestorNodeIds($node);
    $descendantIds = $service->descendantNodeIds($node);

    // Assert
    expect(array_map($label, $ancestorIds))->toBe($ancestors)
        ->and(array_map($label, $descendantIds))->toBe($descendants);
})->with([
    'final of 4' => [4, 2, 1, ['1:1', '1:2'], []],
    'first round of 4' => [4, 1, 2, [], ['2:1']],
    'semi of 8' => [8, 2, 2, ['1:3', '1:4'], ['3:1']],
    'final of 8' => [8, 3, 1, ['2:1', '2:2', '1:1', '1:2', '1:3', '1:4'], []],
    'first round of 8' => [8, 1, 3, [], ['2:2', '3:1']],
    'final of 16' => [16, 4, 1, ['3:1', '3:2', '2:1', '2:2', '2:3', '2:4', '1:1', '1:2', '1:3', '1:4', '1:5', '1:6', '1:7', '1:8'], []],
    'quarter of 16' => [16, 3, 2, ['2:3', '2:4', '1:5', '1:6', '1:7', '1:8'], ['4:1']],
    'first round of 16' => [16, 1, 7, [], ['2:4', '3:2', '4:1']],
]);

it('reads every ancestor of a node in one nodes query', function (): void {
    // Arrange
    $world = tournamentFixture(capacity: 16);
    $final = TournamentNode::query()
        ->where('tournament_id', $world['tournament']->id)
        ->where('round', 4)
        ->firstOrFail();
    $service = resolve(TournamentBracketService::class);

    // Act
    $statements = collect(recordStatements(fn (): array => $service->ancestorNodeIds($final)))
        ->filter(fn (array $statement): bool => str_contains($statement['sql'], 'from "tournament_nodes"'));

    // Assert
    expect($statements)->toHaveCount(1);
});
