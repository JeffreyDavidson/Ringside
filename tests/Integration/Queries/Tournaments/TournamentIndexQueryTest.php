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
    $tournaments = resolve(TournamentIndexQuery::class)->get()->get();

    // Assert
    $newerRow = $tournaments->sole('id', $newer['tournament']->id);
    $olderRow = $tournaments->sole('id', $older['tournament']->id);

    expect($tournaments->pluck('id')->all())->toBe([$newer['tournament']->id, $older['tournament']->id])
        ->and([$newerRow->entries_count, $newerRow->getAttribute('recorded_results_count')])->toBe([3, 0])
        ->and([$olderRow->entries_count, $olderRow->getAttribute('recorded_results_count')])->toBe([4, 1]);
});

test('lists only the enforced promotion tournaments', function (): void {
    // Arrange
    actingAs(administrator());
    $own = tournamentFixture();
    $other = tournamentFixture();
    enforcePromotionContext($own['promotion']);

    // Act
    $tournaments = resolve(TournamentIndexQuery::class)->get()->get();

    // Assert
    expect($tournaments->pluck('id')->all())->toBe([$own['tournament']->id])
        ->and($tournaments->pluck('id')->all())->not->toContain($other['tournament']->id);
});

test('returns nothing when there are no tournaments', function (): void {
    // Arrange
    actingAs(administrator());

    // Act
    $tournaments = resolve(TournamentIndexQuery::class)->get()->get();

    // Assert
    expect($tournaments)->toBeEmpty();
});

test('runs one database statement and leaves paging to the caller', function (): void {
    // Arrange
    actingAs(administrator());
    Tournament::factory()
        ->count(21)
        ->create();

    // Act
    $statements = recordStatements(fn (): mixed => resolve(TournamentIndexQuery::class)->get()->get());
    $page = resolve(TournamentIndexQuery::class)->get()->paginate(20);

    // Assert
    expect($statements)->toHaveCount(1)
        ->and($page->count())->toBe(20)
        ->and($page->total())->toBe(21);
});
