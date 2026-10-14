<?php

declare(strict_types=1);
use App\Actions\Tournaments\PlanTournamentBoutAction;

it('performs the approved PlanTournamentBoutAction operation without changing historical outcomes', function (): void {
    $world = tournamentFixture();
    $node = $world['tournament']->nodes()->where('round', 2)->firstOrFail();
    resolve(PlanTournamentBoutAction::class)->handle($node, $world['event'], $world['tournament']->version);
    expect($node->refresh()->planned_event_id)->toBe($world['event']->id)->and($world['event']->matches()->count())->toBe(0);
});
