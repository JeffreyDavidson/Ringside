<?php

declare(strict_types=1);
use App\Actions\Tournaments\RenameTournamentAction;

it('performs the approved RenameTournamentAction operation without changing historical outcomes', function (): void {
    $world = tournamentFixture();
    bookTournamentNode($world, $world['tournament']->nodes()->firstOrFail());
    resolve(RenameTournamentAction::class)->handle($world['tournament'], $world['tournament']->refresh()->version, 'Autumn Invitational', 'Updated description');
    expect($world['tournament']->refresh()->name)->toBe('Autumn Invitational')->and($world['tournament']->description)->toBe('Updated description')->and($world['tournament']->nodes()->count())->toBe(3);
});
