<?php

declare(strict_types=1);
use App\Actions\Tournaments\CancelTournamentAction;
use App\Enums\Tournaments\TournamentStatus;

it('performs the approved CancelTournamentAction operation without changing historical outcomes', function (): void {
    $world = tournamentFixture();
    $node = $world['tournament']->nodes()->firstOrFail();
    $match = bookTournamentNode($world, $node);
    resolve(CancelTournamentAction::class)->handle($world['tournament'], $world['tournament']->refresh()->version, 'Event cancelled');
    expect($world['tournament']->refresh()->status)->toBe(TournamentStatus::Cancelled)->and($match->refresh()->trashed())->toBeTrue()->and($node->links()->firstOrFail()->active_node_id)->toBeNull();
});
