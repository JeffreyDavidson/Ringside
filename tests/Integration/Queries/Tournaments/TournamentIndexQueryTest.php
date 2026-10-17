<?php

declare(strict_types=1);

use App\Models\Tournaments\Tournament;
use App\Queries\Tournaments\TournamentIndexQuery;

use function Pest\Laravel\actingAs;

test('lists tournaments newest first with entry and recorded result counts', function (): void {
    // Arrange
    actingAs(administrator());
    $older = tournamentFixture();
    $newer = tournamentFixture(capacity: 4, count: 3);
    $node = $older['tournament']->nodes()->firstOrFail();
    recordTournamentWinner(bookTournamentNode($older, $node), $older['tournament']);

    // Act
    $page = resolve(TournamentIndexQuery::class)->get();

    // Assert
    $newerRow = $page->getCollection()->sole('id', $newer['tournament']->id);
    $olderRow = $page->getCollection()->sole('id', $older['tournament']->id);

    expect($page->pluck('id')->all())->toBe([$newer['tournament']->id, $older['tournament']->id])
        ->and([$newerRow->entries_count, $newerRow->getAttribute('recorded_results_count')])->toBe([3, 0])
        ->and([$olderRow->entries_count, $olderRow->getAttribute('recorded_results_count')])->toBe([4, 1]);
});

test('pages twenty tournaments at a time', function (): void {
    // Arrange
    actingAs(administrator());
    Tournament::factory()
        ->count(21)
        ->create();

    // Act
    $page = resolve(TournamentIndexQuery::class)->get();

    // Assert
    expect($page->count())->toBe(20)
        ->and($page->total())->toBe(21);
});
