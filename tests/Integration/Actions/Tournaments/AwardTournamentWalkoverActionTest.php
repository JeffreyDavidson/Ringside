<?php

declare(strict_types=1);
use App\Actions\Tournaments\AwardTournamentWalkoverAction;
use App\Exceptions\Tournaments\TournamentException;

it('refuses to award a walkover while both entrants can compete', function (): void {
    $world = tournamentFixture();
    $nodes = $world['tournament']->nodes()->get();
    bookTournamentNode($world, $nodes->sole(fn ($node): bool => $node->round === 1 && $node->position === 2));
    $node = $nodes->sole(fn ($node): bool => $node->round === 1 && $node->position === 1);
    $version = $world['tournament']->refresh()->version;
    expect(fn () => resolve(AwardTournamentWalkoverAction::class)->handle($node, $version, 'No recorded withdrawal'))->toThrow(TournamentException::class)
        ->and($node->refresh()->walkover_entry_id)->toBeNull()
        ->and($world['tournament']->refresh()->version)->toBe($version);
});
