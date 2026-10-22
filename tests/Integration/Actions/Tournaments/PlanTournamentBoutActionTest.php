<?php

declare(strict_types=1);
use App\Actions\Tournaments\PlanTournamentBoutAction;

use function Pest\Laravel\actingAs;

it('performs the approved PlanTournamentBoutAction operation without changing historical outcomes', function (): void {
    $world = tournamentFixture();
    $node = $world['tournament']->nodes()->where('round', 2)->firstOrFail();
    resolve(PlanTournamentBoutAction::class)->handle($node, $world['event'], $world['tournament']->version, administrator());
    expect($node->refresh()->planned_event_id)->toBe($world['event']->id)->and($world['event']->matches()->count())->toBe(0);
});

test('planning a bout names the actor passed in as the audit causer', function (): void {
    // Arrange
    actingAs(administrator());
    $actor = administrator();
    $world = tournamentFixture();
    $node = $world['tournament']->nodes()->where('round', 2)->firstOrFail();
    $since = lastActivityId();

    // Act
    resolve(PlanTournamentBoutAction::class)->handle($node, $world['event'], $world['tournament']->version, $actor);

    // Assert
    expect(activityEntriesAfter($since, 'tournaments'))->toBe(['tournament.planned|'.causerTag($actor)]);
});
