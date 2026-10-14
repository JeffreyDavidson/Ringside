<?php

declare(strict_types=1);
use App\Actions\Matches\RecordResultAction;
use App\Actions\Tournaments\BookTournamentMatchAction;
use App\Actions\Tournaments\SaveTournamentAction;
use App\Data\Matches\MatchResultData;
use App\Data\Tournaments\TournamentData;
use App\Enums\MatchFinish;
use App\Enums\Tournaments\EntrantKind;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentNode;
use Illuminate\Database\Eloquent\Collection;

/** @return array{tournament:Tournament,promotion:Promotion,event:Event,referees:Collection<int,Referee>} */
function tournamentFixture(bool $repeat = true, int $capacity = 4, ?int $count = null, EntrantKind $kind = EntrantKind::Wrestler): array
{
    $promotion = Promotion::factory()->create();
    $competitors = $kind === EntrantKind::Wrestler ? Wrestler::factory()->employed()->for($promotion)->count($count ?? $capacity)->create() : TagTeam::factory()->bookable()->for($promotion)->recycle($promotion)->count($count ?? $capacity)->create();
    $tournament = resolve(SaveTournamentAction::class)->handle($promotion, new TournamentData('Autumn Cup', null, $kind, $capacity, $repeat, array_values($competitors->map(fn ($competitor): int => $competitor->id)->all())));

    return ['tournament' => $tournament, 'promotion' => $promotion, 'event' => Event::factory()->for($promotion)->create(['date' => now()->subHour()]), 'referees' => new Collection([Referee::factory()->employed()->for($promotion)->create()])];
}
/** @param array{tournament:Tournament,promotion:Promotion,event:Event,referees:Collection<int,Referee>} $world */
function bookTournamentNode(array $world, TournamentNode $node): EventMatch
{
    return resolve(BookTournamentMatchAction::class)->handle($node, $world['event'], $world['referees'], $world['tournament']->refresh()->version);
}
function recordTournamentWinner(EventMatch $match, Tournament $tournament, MatchFinish $finish = MatchFinish::Pinfall): void
{
    resolve(RecordResultAction::class)->handle($match, new MatchResultData($finish, $finish->requiresWinningSide() ? $match->sides()->firstOrFail() : null, collect(), $tournament->refresh()->version));
}
