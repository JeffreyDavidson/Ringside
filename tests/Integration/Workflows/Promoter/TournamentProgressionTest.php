<?php

declare(strict_types=1);
use App\Actions\Matches\AddMatchForEventAction;
use App\Actions\Matches\RecordResultAction;
use App\Actions\Tournaments\BookTournamentMatchAction;
use App\Actions\Tournaments\SaveTournamentAction;
use App\Data\Matches\EventMatchData;
use App\Data\Matches\MatchResultData;
use App\Data\Tournaments\TournamentData;
use App\Enums\MatchFinish;
use App\Enums\MatchType;
use App\Enums\Titles\TitleType;
use App\Enums\Tournaments\EntrantKind;
use App\Enums\Tournaments\TournamentStatus;
use App\Exceptions\BaseBusinessException;
use App\Exceptions\Tournaments\TournamentException;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Models\Tournaments\TournamentNode;
use App\Services\Tournaments\TournamentBracketService;
use Illuminate\Database\Eloquent\Collection;

it('advances actual qualifiers and completes a four person tournament on one card', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $wrestlers = Wrestler::factory()->employed()->for($promotion)->count(4)->create();
    $tournament = resolve(SaveTournamentAction::class)->handle($promotion, new TournamentData('Autumn Cup', null, EntrantKind::Wrestler, 4, true, array_values($wrestlers->map(fn ($competitor): int => $competitor->id)->all())));
    $event = Event::factory()->for($promotion)->create(['date' => now()->subHour()]);
    $referees = new Collection([Referee::factory()->employed()->for($promotion)->create()]);
    $book = resolve(BookTournamentMatchAction::class);
    $result = resolve(RecordResultAction::class);
    $nodes = $tournament->nodes()->get();

    // Act
    $first = $book->handle($nodes->sole(fn (TournamentNode $n): bool => $n->round === 1 && $n->position === 1), $event, $referees, $tournament->refresh()->version);
    $result->handle($first, new MatchResultData(MatchFinish::Pinfall, $first->sides()->firstOrFail(), collect(), $tournament->refresh()->version));
    $second = $book->handle($nodes->sole(fn (TournamentNode $n): bool => $n->round === 1 && $n->position === 2), $event, $referees, $tournament->refresh()->version);
    $result->handle($second, new MatchResultData(MatchFinish::Countout, $second->sides()->firstOrFail(), collect(), $tournament->refresh()->version));
    $final = $book->handle($nodes->sole(fn (TournamentNode $n): bool => $n->round === 2 && $n->position === 1), $event, $referees, $tournament->refresh()->version);
    $result->handle($final, new MatchResultData(MatchFinish::Disqualification, $final->sides()->firstOrFail(), collect(), $tournament->refresh()->version));

    // Assert
    expect($event->matches()->count())->toBe(3)
        ->and($final->wrestlers()->pluck('wrestlers.id')->all())->toEqualCanonicalizing([$wrestlers->pluck('id')->all()[0], $wrestlers->pluck('id')->all()[1]])
        ->and($tournament->refresh()->status)->toBe(TournamentStatus::Completed)
        ->and(resolve(TournamentBracketService::class)->bouts($tournament)->last()?->winner?->competitor_id)->toBe($wrestlers->pluck('id')->all()[0]);
});

it('keeps a drawn attempt and advances only after a real rematch result', function (): void {
    $world = tournamentFixture();
    $node = $world['tournament']->nodes()->firstOrFail();
    $first = bookTournamentNode($world, $node);
    recordTournamentWinner($first, $world['tournament'], MatchFinish::TimeLimitDraw);
    expect(resolve(TournamentBracketService::class)->bout($node)->winner)->toBeNull();
    $rematch = bookTournamentNode($world, $node);
    recordTournamentWinner($rematch, $world['tournament']);
    expect($node->links()->count())->toBe(2)->and($first->refresh()->match_finish)->toBe(MatchFinish::TimeLimitDraw)
        ->and(resolve(TournamentBracketService::class)->bout($node)->winner)->not->toBeNull();
});

it('uses tag teams as bracket entrants without weakening team booking rules', function (): void {
    $world = tournamentFixture(kind: EntrantKind::TagTeam);
    $nodes = $world['tournament']->nodes()->get();
    $first = bookTournamentNode($world, $nodes->sole(fn (TournamentNode $n): bool => $n->round === 1 && $n->position === 1));
    recordTournamentWinner($first, $world['tournament']);
    $second = bookTournamentNode($world, $nodes->sole(fn (TournamentNode $n): bool => $n->round === 1 && $n->position === 2));
    recordTournamentWinner($second, $world['tournament']);
    $final = bookTournamentNode($world, $nodes->sole(fn (TournamentNode $n): bool => $n->round === 2 && $n->position === 1));
    recordTournamentWinner($final, $world['tournament']);
    expect($final->tagTeams()->count())->toBe(2)->and($world['tournament']->refresh()->status)->toBe(TournamentStatus::Completed);
});

it('advances only decisive recorded finishes', function (MatchFinish $finish): void {
    $world = tournamentFixture();
    $node = $world['tournament']->nodes()->firstOrFail();
    $match = bookTournamentNode($world, $node);
    recordTournamentWinner($match, $world['tournament'], $finish);
    expect(resolve(TournamentBracketService::class)->bout($node)->complete)->toBe($finish->requiresWinningSide());
})->with(MatchFinish::cases());

it('rejects a conflicting stale result and treats an identical retry as a no-op', function (): void {
    $world = tournamentFixture();
    $node = $world['tournament']->nodes()->firstOrFail();
    $match = bookTournamentNode($world, $node);
    $version = $world['tournament']->refresh()->version;
    $winner = $match->sides()->firstOrFail();
    $loser = $match->sides()->reorder('position', 'desc')->firstOrFail();
    $action = resolve(RecordResultAction::class);
    $data = new MatchResultData(MatchFinish::Pinfall, $winner, collect(), $version);
    $action->handle($match, $data);
    $after = $world['tournament']->refresh()->version;
    $action->handle($match, $data);
    expect(fn () => $action->handle($match, new MatchResultData(MatchFinish::Pinfall, $loser, collect(), $version)))->toThrow(TournamentException::class)
        ->and($world['tournament']->refresh()->version)->toBe($after)
        ->and($match->refresh()->winning_side_id)->toBe($winner->id);
});

it('refuses a same-card next round when repeat appearances are disabled', function (): void {
    $world = tournamentFixture(repeat: false);
    $nodes = $world['tournament']->nodes()->get();
    $first = bookTournamentNode($world, $nodes->sole(fn (TournamentNode $n): bool => $n->round === 1 && $n->position === 1));
    recordTournamentWinner($first, $world['tournament']);
    $second = bookTournamentNode($world, $nodes->sole(fn (TournamentNode $n): bool => $n->round === 1 && $n->position === 2));
    recordTournamentWinner($second, $world['tournament']);
    expect(fn () => bookTournamentNode($world, $nodes->sole(fn (TournamentNode $n): bool => $n->round === 2 && $n->position === 1)))->toThrow(TournamentException::class)
        ->and($world['event']->matches()->count())->toBe(2);
});

it('accepts independently recorded sibling winners without losing either qualification', function (): void {
    $world = tournamentFixture();
    $nodes = $world['tournament']->nodes()->where('round', 1)->get();
    $first = bookTournamentNode($world, $nodes->sole('position', 1));
    $second = bookTournamentNode($world, $nodes->sole('position', 2));
    $version = $world['tournament']->refresh()->version;
    $action = resolve(RecordResultAction::class);
    $action->handle($first, new MatchResultData(MatchFinish::Pinfall, $first->sides()->firstOrFail(), collect(), $version));
    $action->handle($second, new MatchResultData(MatchFinish::Pinfall, $second->sides()->firstOrFail(), collect(), $version));
    expect(resolve(TournamentBracketService::class)->bouts($world['tournament'])->last()?->inputsResolved)->toBeTrue();
});

it('completes every seeded round of an eight entrant tournament', function (EntrantKind $kind): void {
    $world = tournamentFixture(capacity: 8, kind: $kind);
    $t = $world['tournament'];
    foreach ($t->nodes()->orderBy('round')->orderBy('position')->get() as $node) {
        $match = bookTournamentNode($world, $node);
        recordTournamentWinner($match, $t);
    }
    expect($world['event']->matches()->count())->toBe(7)->and($t->refresh()->status)->toBe(TournamentStatus::Completed)
        ->and(resolve(TournamentBracketService::class)->bouts($t)->last()?->winner?->seed)->toBe(1);
})->with(EntrantKind::cases());

it('keeps final title reconciliation authoritative while deriving the tournament winner', function (MatchFinish $finish): void {
    $world = tournamentFixture(count: 2);
    $t = $world['tournament'];
    $node = $t->nodes()->where('round', 2)->firstOrFail();
    $entries = $t->entries()->get();
    $champion = Wrestler::query()->findOrFail($entries->sole('seed', 1)->competitor_id);
    $challenger = Wrestler::query()->findOrFail($entries->sole('seed', 2)->competitor_id);
    $title = Title::factory()->active()->for($world['promotion'])->create(['type' => TitleType::Singles]);
    $reign = TitleChampionship::factory()->for($title)->forWrestler($champion)->wonOn(now()->subMonth()->toDateTimeString())->create();
    $final = resolve(BookTournamentMatchAction::class)->handle($node, $world['event'], $world['referees'], $t->version, new Collection([$title]));
    $winner = $final->sides()->where('position', 2)->firstOrFail();
    resolve(RecordResultAction::class)->handle($final, new MatchResultData($finish, $winner, collect(), $t->refresh()->version));
    $transfers = in_array($finish, [MatchFinish::Pinfall, MatchFinish::Submission], true);
    expect($t->refresh()->status)->toBe(TournamentStatus::Completed)->and(resolve(TournamentBracketService::class)->bout($node)->winner?->competitor_id)->toBe($challenger->id)
        ->and($title->championships()->current()->sole()->champion->is($transfers ? $challenger : $champion))->toBeTrue()
        ->and($reign->refresh()->lost_at !== null)->toBe($transfers);
})->with([MatchFinish::Pinfall, MatchFinish::Submission, MatchFinish::Countout, MatchFinish::Disqualification]);

it('treats an identical final retry as existing state after later championship history', function (): void {
    $world = tournamentFixture(count: 2);
    $t = $world['tournament'];
    $node = $t->nodes()->where('round', 2)->firstOrFail();
    $entries = $t->entries()->get();
    $first = Wrestler::query()->findOrFail($entries->sole('seed', 1)->competitor_id);
    $second = Wrestler::query()->findOrFail($entries->sole('seed', 2)->competitor_id);
    $title = Title::factory()->active()->for($world['promotion'])->create(['type' => TitleType::Singles]);
    $final = resolve(BookTournamentMatchAction::class)->handle($node, $world['event'], $world['referees'], $t->version, new Collection([$title]));
    $data = new MatchResultData(MatchFinish::Pinfall, $final->sides()->where('position', 1)->firstOrFail(), collect(), $t->refresh()->version);
    resolve(RecordResultAction::class)->handle($final, $data);
    $event = Event::factory()->for($world['promotion'])->create(['date' => now()->subMinutes(30)]);
    $third = Wrestler::factory()->employed()->for($world['promotion'])->create();
    $later = resolve(AddMatchForEventAction::class)->handle($event, new EventMatchData(MatchType::Singles, $world['referees'], new Collection([$title]), collect([1 => ['wrestlers' => [$first]], 2 => ['wrestlers' => [$third]]]), null));
    resolve(RecordResultAction::class)->handle($later, new MatchResultData(MatchFinish::Pinfall, $later->sides()->where('position', 2)->firstOrFail(), collect()));
    $version = $t->refresh()->version;
    resolve(RecordResultAction::class)->handle($final, $data);
    expect($t->refresh()->version)->toBe($version)->and($t->status)->toBe(TournamentStatus::Completed)->and($title->championships()->current()->sole()->champion->is($third))->toBeTrue();
});

it('rolls back tournament advancement when final championship reconciliation is refused', function (): void {
    $world = tournamentFixture(count: 2);
    $tournament = $world['tournament'];
    $node = $tournament->nodes()->where('round', 2)->firstOrFail();
    $title = Title::factory()->active()->for($world['promotion'])->create(['type' => TitleType::Singles]);
    $final = resolve(BookTournamentMatchAction::class)->handle($node, $world['event'], $world['referees'], $tournament->version, new Collection([$title]));
    $version = $tournament->refresh()->version;
    $title->delete();
    expect(fn () => resolve(RecordResultAction::class)->handle($final, new MatchResultData(MatchFinish::Pinfall, $final->sides()->firstOrFail(), collect(), $version)))->toThrow(BaseBusinessException::class)
        ->and($final->refresh()->match_finish)->toBeNull()
        ->and($tournament->refresh()->status)->toBe(TournamentStatus::Active)
        ->and($tournament->version)->toBe($version)
        ->and($title->championships()->count())->toBe(0);
});
