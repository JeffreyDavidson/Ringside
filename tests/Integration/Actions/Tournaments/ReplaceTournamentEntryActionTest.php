<?php

declare(strict_types=1);
use App\Actions\Tournaments\ReplaceTournamentEntryAction;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Tournaments\TournamentNode;

it('performs the approved ReplaceTournamentEntryAction operation without changing historical outcomes', function (): void {
    $world = tournamentFixture();
    $nodes = $world['tournament']->nodes()->get();
    $match = bookTournamentNode($world, $nodes->sole(fn (TournamentNode $n): bool => $n->round === 1 && $n->position === 1));
    $entry = $world['tournament']->entries()->firstOrFail();
    $replacement = Wrestler::factory()->employed()->for($world['promotion'])->create();
    resolve(ReplaceTournamentEntryAction::class)->handle($entry, $replacement->id, $world['tournament']->refresh()->version, 'Original entrant unavailable');
    expect($entry->refresh()->competitor_id)->toBe($replacement->id)->and($match->refresh()->trashed())->toBeTrue()->and($match->competitors()->count())->toBe(2);
});

it('versions an entry replacement even when its branch has no booking to reset', function (): void {
    $world = tournamentFixture();
    $tournament = $world['tournament'];
    bookTournamentNode($world, $tournament->nodes()->where('round', 1)->where('position', 2)->firstOrFail());
    $entry = $tournament->entries()->where('seed', 1)->firstOrFail();
    $replacement = Wrestler::factory()->employed()->for($world['promotion'])->create();
    $version = $tournament->refresh()->version;
    resolve(ReplaceTournamentEntryAction::class)->handle($entry, $replacement->id, $version, 'Replace an unplayed entry');
    expect($tournament->refresh()->version)->toBe($version + 1)
        ->and($entry->refresh()->competitor_id)->toBe($replacement->id);
});
