<?php

declare(strict_types=1);
use App\Actions\Tournaments\AwardTournamentWalkoverAction;
use App\Actions\Tournaments\WithdrawTournamentEntryAction;
use App\Exceptions\Tournaments\TournamentException;

use function Pest\Laravel\actingAs;

it('refuses to award a walkover while both entrants can compete', function (): void {
    $world = tournamentFixture();
    $nodes = $world['tournament']->nodes()->get();
    bookTournamentNode($world, $nodes->sole(fn ($node): bool => $node->round === 1 && $node->position === 2));
    $node = $nodes->sole(fn ($node): bool => $node->round === 1 && $node->position === 1);
    $version = $world['tournament']->refresh()->version;
    expect(fn () => resolve(AwardTournamentWalkoverAction::class)->handle($node, $version, 'No recorded withdrawal', administrator()))->toThrow(TournamentException::class)
        ->and($node->refresh()->walkover_entry_id)->toBeNull()
        ->and($world['tournament']->refresh()->version)->toBe($version);
});

test('awarding a walkover names the actor passed in as the causer of every entry it writes', function (): void {
    // Arrange
    actingAs(administrator());
    $actor = administrator();
    $world = tournamentFixture();
    $nodes = $world['tournament']->nodes()->get();
    bookTournamentNode($world, $nodes->sole(fn ($node): bool => $node->round === 1 && $node->position === 2));
    $entry = $world['tournament']->entries()->where('seed', 1)->firstOrFail();
    resolve(WithdrawTournamentEntryAction::class)->handle($entry, $world['tournament']->refresh()->version, 'Unable to compete', $actor);
    $node = $nodes->sole(fn ($node): bool => $node->round === 1 && $node->position === 1);
    $version = $world['tournament']->refresh()->version;
    $since = lastActivityId();

    // Act
    resolve(AwardTournamentWalkoverAction::class)->handle($node, $version, 'Opponent withdrew', $actor);

    // Assert
    expect(activityEntriesAfter($since, 'tournaments'))->toBe(['tournament.walkover|'.causerTag($actor), 'tournament.progressed|'.causerTag($actor)]);
});
