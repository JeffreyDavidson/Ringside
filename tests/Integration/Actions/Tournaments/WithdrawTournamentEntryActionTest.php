<?php

declare(strict_types=1);
use App\Actions\Tournaments\AwardTournamentWalkoverAction;
use App\Actions\Tournaments\BookTournamentMatchAction;
use App\Actions\Tournaments\SaveTournamentAction;
use App\Actions\Tournaments\WithdrawTournamentEntryAction;
use App\Data\Tournaments\TournamentBookingData;
use App\Data\Tournaments\TournamentData;
use App\Enums\Tournaments\EntrantKind;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Tournaments\TournamentNode;
use App\Services\Tournaments\TournamentBracketService;
use Illuminate\Database\Eloquent\Collection;

use function Pest\Laravel\actingAs;

it('withdraws an entrant explicitly and advances only the opposing entry by a labelled non-match walkover', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $wrestlers = Wrestler::factory()->employed()->for($promotion)->count(4)->create();
    $tournament = resolve(SaveTournamentAction::class)->handle($promotion, new TournamentData('Autumn Cup', null, EntrantKind::Wrestler, 4, true, array_values($wrestlers->map(fn ($competitor): int => $competitor->id)->all())), administrator());
    $entry = $tournament->entries()->firstOrFail();
    $nodes = $tournament->nodes()->get();
    $node = $nodes->sole(fn (TournamentNode $n): bool => $n->round === 1 && $n->position === 1);
    resolve(BookTournamentMatchAction::class)->handle($nodes->sole(fn (TournamentNode $n): bool => $n->round === 1 && $n->position === 2), $tournament->version, new TournamentBookingData(Event::factory()->for($promotion)->create(), new Collection([Referee::factory()->employed()->for($promotion)->create()])), administrator());
    $tournament->refresh();

    // Act
    resolve(WithdrawTournamentEntryAction::class)->handle($entry, $tournament->version, 'Unable to compete', administrator());
    resolve(AwardTournamentWalkoverAction::class)->handle($node, $tournament->refresh()->version, 'Opponent withdrew', administrator());

    // Assert
    expect($entry->refresh()->withdrawn)->toBeTrue()
        ->and(resolve(TournamentBracketService::class)->bout($node)->winner?->competitor_id)->toBe($wrestlers->pluck('id')->all()[3])
        ->and($node->refresh()->walkover_reason)->toBe('Opponent withdrew')
        ->and($node->links()->count())->toBe(0);
});

test('withdrawing an entry names the actor passed in as the causer of every entry it writes', function (): void {
    // Arrange
    actingAs(administrator());
    $actor = administrator();
    $world = tournamentFixture();
    bookTournamentNode($world, $world['tournament']->nodes()->where('round', 1)->where('position', 1)->firstOrFail());
    $entry = $world['tournament']->entries()->where('seed', 1)->firstOrFail();
    $version = $world['tournament']->refresh()->version;
    $since = lastActivityId();

    // Act
    resolve(WithdrawTournamentEntryAction::class)->handle($entry, $version, 'Unable to compete', $actor);

    // Assert
    expect(activityEntriesAfter($since, 'tournaments'))->toBe(['tournament.reset|'.causerTag($actor), 'tournament.progressed|'.causerTag($actor), 'tournament.withdrawn|'.causerTag($actor), 'tournament.progressed|'.causerTag($actor)]);
});
