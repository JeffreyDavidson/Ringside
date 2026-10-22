<?php

declare(strict_types=1);
use App\Actions\Matches\RecordResultAction;
use App\Actions\Tournaments\BookTournamentMatchAction;
use App\Actions\Tournaments\ResetTournamentBranchAction;
use App\Actions\Tournaments\SaveTournamentAction;
use App\Data\Matches\MatchResultData;
use App\Data\Tournaments\TournamentBookingData;
use App\Data\Tournaments\TournamentData;
use App\Enums\Matches\MatchFinish;
use App\Enums\Tournaments\EntrantKind;
use App\Enums\Tournaments\TournamentStatus;
use App\Exceptions\Tournaments\TournamentException;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Tournaments\TournamentNode;
use Illuminate\Database\Eloquent\Collection;

use function Pest\Laravel\actingAs;

it('requires a reset of unplayed dependencies before changing a qualifier and preserves booking history', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $wrestlers = Wrestler::factory()->employed()->for($promotion)->count(4)->create();
    $tournament = resolve(SaveTournamentAction::class)->handle($promotion, new TournamentData('Autumn Cup', null, EntrantKind::Wrestler, 4, true, array_values($wrestlers->map(fn ($competitor): int => $competitor->id)->all())), administrator());
    $event = Event::factory()->for($promotion)->create(['date' => now()->subHour()]);
    $referees = new Collection([Referee::factory()->employed()->for($promotion)->create()]);
    $nodes = $tournament->nodes()->get();
    $book = resolve(BookTournamentMatchAction::class);
    $result = resolve(RecordResultAction::class);
    $first = $book->handle($nodes->sole(fn (TournamentNode $n): bool => $n->round === 1 && $n->position === 1), $tournament->refresh()->version, new TournamentBookingData($event, $referees), administrator());
    $result->handle($first, new MatchResultData(MatchFinish::Pinfall, $first->sides()->firstOrFail(), collect(), $tournament->refresh()->version), administrator());
    $second = $book->handle($nodes->sole(fn (TournamentNode $n): bool => $n->round === 1 && $n->position === 2), $tournament->refresh()->version, new TournamentBookingData($event, $referees), administrator());
    $result->handle($second, new MatchResultData(MatchFinish::Pinfall, $second->sides()->firstOrFail(), collect(), $tournament->refresh()->version), administrator());
    $final = $book->handle($nodes->sole(fn (TournamentNode $n): bool => $n->round === 2 && $n->position === 1), $tournament->refresh()->version, new TournamentBookingData($event, $referees), administrator());
    $loser = $first->sides()->reorder('position', 'desc')->firstOrFail();
    expect(fn () => $result->handle($first, new MatchResultData(MatchFinish::Submission, $loser, collect(), $tournament->refresh()->version), administrator()))->toThrow(TournamentException::class);

    // Act
    resolve(ResetTournamentBranchAction::class)->handle($nodes->sole(fn (TournamentNode $n): bool => $n->round === 1 && $n->position === 1), $tournament->refresh()->version, 'Quarterfinal outcome was entered incorrectly', administrator());
    $result->handle($first, new MatchResultData(MatchFinish::Submission, $loser, collect(), $tournament->refresh()->version), administrator());

    // Assert
    expect($final->refresh()->trashed())->toBeTrue()
        ->and($nodes->sole(fn (TournamentNode $n): bool => $n->round === 2 && $n->position === 1)->links()->firstOrFail()->active_node_id)->toBeNull()
        ->and($first->refresh()->winning_side_id)->toBe($loser->id)
        ->and($event->matches()->withTrashed()->count())->toBe(3);
});

it('preserves a draft when a reviewed reset has no booked or administrative dependency', function (): void {
    $world = tournamentFixture();
    $tournament = $world['tournament'];
    resolve(ResetTournamentBranchAction::class)->handle($tournament->nodes()->firstOrFail(), $tournament->version, 'Review future bookings', administrator());
    expect($tournament->refresh()->status)->toBe(TournamentStatus::Draft)
        ->and($tournament->version)->toBe(1);
});

test('resetting a branch names the actor passed in as the causer of every entry it writes', function (): void {
    // Arrange
    actingAs(administrator());
    $actor = administrator();
    $world = tournamentFixture();
    $node = $world['tournament']->nodes()->where('round', 1)->where('position', 1)->firstOrFail();
    bookTournamentNode($world, $node);
    $version = $world['tournament']->refresh()->version;
    $since = lastActivityId();

    // Act
    resolve(ResetTournamentBranchAction::class)->handle($node, $version, 'Reschedule the bout', $actor, true);

    // Assert
    expect(activityEntriesAfter($since, 'tournaments'))->toBe(['tournament.reset|'.causerTag($actor), 'tournament.progressed|'.causerTag($actor)]);
});
