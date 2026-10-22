<?php

declare(strict_types=1);
use App\Actions\Tournaments\CancelTournamentAction;
use App\Enums\Tournaments\TournamentStatus;

use function Pest\Laravel\actingAs;

it('performs the approved CancelTournamentAction operation without changing historical outcomes', function (): void {
    $world = tournamentFixture();
    $node = $world['tournament']->nodes()->firstOrFail();
    $match = bookTournamentNode($world, $node);
    resolve(CancelTournamentAction::class)->handle($world['tournament'], $world['tournament']->refresh()->version, 'Event cancelled', administrator());
    expect($world['tournament']->refresh()->status)->toBe(TournamentStatus::Cancelled)->and($match->refresh()->trashed())->toBeTrue()->and($node->links()->firstOrFail()->active_node_id)->toBeNull();
});

test('cancelling a tournament names the actor passed in as the causer of every entry it writes', function (): void {
    // Arrange
    actingAs(administrator());
    $actor = administrator();
    $world = tournamentFixture();
    bookTournamentNode($world, $world['tournament']->nodes()->firstOrFail());
    $version = $world['tournament']->refresh()->version;
    $since = lastActivityId();

    // Act
    resolve(CancelTournamentAction::class)->handle($world['tournament'], $version, 'Event cancelled', $actor);

    // Assert
    expect(activityEntriesAfter($since, 'tournaments'))->toBe(['tournament.reset|'.causerTag($actor), 'tournament.progressed|'.causerTag($actor), 'tournament.cancelled|'.causerTag($actor)]);
});
