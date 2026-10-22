<?php

declare(strict_types=1);
use App\Actions\Tournaments\RenameTournamentAction;

use function Pest\Laravel\actingAs;

it('performs the approved RenameTournamentAction operation without changing historical outcomes', function (): void {
    $world = tournamentFixture();
    bookTournamentNode($world, $world['tournament']->nodes()->firstOrFail());
    resolve(RenameTournamentAction::class)->handle($world['tournament'], $world['tournament']->refresh()->version, 'Autumn Invitational', 'Updated description', administrator());
    expect($world['tournament']->refresh()->name)->toBe('Autumn Invitational')->and($world['tournament']->description)->toBe('Updated description')->and($world['tournament']->nodes()->count())->toBe(3);
});

test('renaming a tournament names the actor passed in as the audit causer', function (): void {
    // Arrange
    actingAs(administrator());
    $actor = administrator();
    $world = tournamentFixture();
    $since = lastActivityId();

    // Act
    resolve(RenameTournamentAction::class)->handle($world['tournament'], $world['tournament']->version, 'Autumn Invitational', null, $actor);

    // Assert
    expect(activityEntriesAfter($since, 'tournaments'))->toBe(['tournament.renamed|'.causerTag($actor)]);
});
