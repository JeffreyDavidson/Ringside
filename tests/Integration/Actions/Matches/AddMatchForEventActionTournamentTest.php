<?php

declare(strict_types=1);

use App\Actions\Matches\AddMatchForEventAction;
use App\Data\Matches\EventMatchData;
use App\Enums\Matches\MatchFinish;
use App\Enums\Matches\MatchType;
use App\Enums\Tournaments\TournamentStatus;
use App\Exceptions\Tournaments\TournamentException;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Tournaments\Tournament;
use App\ValueObjects\MatchSides;
use Illuminate\Database\Eloquent\Collection;

use function Pest\Laravel\actingAs;

/**
 * Match data for the first-round bout of seeds 1 and 4, with the given overrides.
 *
 * @param  array{tournament:Tournament,promotion:Promotion,event:Event,referees:Collection<int,Referee>}  $world
 * @param  Collection<int, Title>|null  $titles
 */
function tournamentBoutData(array $world, ?Wrestler $second = null, ?Collection $titles = null, MatchType $type = MatchType::Singles): EventMatchData
{
    $entries = $world['tournament']->entries()->get();
    $first = Wrestler::query()->findOrFail($entries->sole('seed', 1)->competitor_id);
    $second ??= Wrestler::query()->findOrFail($entries->sole('seed', 4)->competitor_id);

    return new EventMatchData($type, $world['referees'], $titles ?? new Collection, new MatchSides(collect([1 => ['wrestlers' => [$first]], 2 => ['wrestlers' => [$second]]])), null);
}

it('applies the tournament booking checks in a fixed order', function (Closure $setup, string $messageKey): void {
    // Arrange
    ['world' => $world, 'event' => $event, 'data' => $data, 'node' => $node] = $setup();
    $version = $world['tournament']->refresh()->version;

    // Act
    $attempt = fn () => resolve(AddMatchForEventAction::class)->handle($event, $data, administrator(), $node, $version);

    // Assert
    expect($attempt)->toThrow(TournamentException::class, __($messageKey))
        ->and($event->matches()->count())->toBe(0)
        ->and($world['tournament']->refresh()->version)->toBe($version);
})->with([
    'a closed tournament before an unresolved bout' => [
        function (): array {
            $world = tournamentFixture();
            $world['tournament']->forceFill(['status' => TournamentStatus::Completed])->save();

            return ['world' => $world, 'event' => $world['event'], 'data' => tournamentBoutData($world), 'node' => $world['tournament']->nodes()->where('round', 2)->firstOrFail()];
        },
        'tournaments.errors.closed',
    ],
    'an unresolved bout before the wrong entrants and a non-final title' => [
        function (): array {
            $world = tournamentFixture();
            $stranger = Wrestler::factory()->employed()->for($world['promotion'])->create();
            $titles = new Collection([Title::factory()->active()->for($world['promotion'])->create()]);

            return ['world' => $world, 'event' => $world['event'], 'data' => tournamentBoutData($world, $stranger, $titles), 'node' => $world['tournament']->nodes()->where('round', 2)->firstOrFail()];
        },
        'tournaments.errors.unresolved',
    ],
    'an existing booking before the wrong entrants' => [
        function (): array {
            $world = tournamentFixture();
            $node = $world['tournament']->nodes()->where('round', 1)->where('position', 1)->firstOrFail();
            bookTournamentNode($world, $node);
            $stranger = Wrestler::factory()->employed()->for($world['promotion'])->create();

            return ['world' => $world, 'event' => Event::factory()->for($world['promotion'])->create(['date' => now()->addWeek()]), 'data' => tournamentBoutData($world, $stranger), 'node' => $node];
        },
        'tournaments.errors.already_booked',
    ],
    'the wrong entrants before a non-final title' => [
        function (): array {
            $world = tournamentFixture();
            $stranger = Wrestler::factory()->employed()->for($world['promotion'])->create();
            $titles = new Collection([Title::factory()->active()->for($world['promotion'])->create()]);

            return ['world' => $world, 'event' => $world['event'], 'data' => tournamentBoutData($world, $stranger, $titles), 'node' => $world['tournament']->nodes()->where('round', 1)->where('position', 1)->firstOrFail()];
        },
        'tournaments.errors.entrants_changed',
    ],
    'the wrong match type for the entrants' => [
        function (): array {
            $world = tournamentFixture();

            return ['world' => $world, 'event' => $world['event'], 'data' => tournamentBoutData($world, type: MatchType::TagTeam), 'node' => $world['tournament']->nodes()->where('round', 1)->where('position', 1)->firstOrFail()];
        },
        'tournaments.errors.entrants_changed',
    ],
    'a non-final title before chronology' => [
        function (): array {
            $world = tournamentFixture();
            $node = $world['tournament']->nodes()->where('round', 1)->where('position', 1)->firstOrFail();
            recordTournamentWinner(bookTournamentNode($world, $node), $world['tournament'], MatchFinish::TimeLimitDraw);
            $titles = new Collection([Title::factory()->active()->for($world['promotion'])->create()]);

            return ['world' => $world, 'event' => Event::factory()->for($world['promotion'])->create(['date' => $world['event']->date?->copy()->subDay()]), 'data' => tournamentBoutData($world, titles: $titles), 'node' => $node];
        },
        'tournaments.errors.final_titles_only',
    ],
    'chronology after every other check passes' => [
        function (): array {
            $world = tournamentFixture();
            $node = $world['tournament']->nodes()->where('round', 1)->where('position', 1)->firstOrFail();
            recordTournamentWinner(bookTournamentNode($world, $node), $world['tournament'], MatchFinish::TimeLimitDraw);

            return ['world' => $world, 'event' => Event::factory()->for($world['promotion'])->create(['date' => $world['event']->date?->copy()->subDay()]), 'data' => tournamentBoutData($world), 'node' => $node];
        },
        'tournaments.errors.chronology',
    ],
]);

test('adding a tournament match names the actor passed in as the causer of the activation and the booking', function (): void {
    // Arrange
    actingAs(administrator());
    $actor = administrator();
    $world = tournamentFixture();
    $node = $world['tournament']->nodes()->where('round', 1)->where('position', 1)->firstOrFail();
    $data = tournamentBoutData($world);
    $since = lastActivityId();

    // Act
    resolve(AddMatchForEventAction::class)->handle($world['event'], $data, $actor, $node, $world['tournament']->version);

    // Assert
    expect(activityEntriesAfter($since, 'tournaments'))->toBe(['tournament.activated|'.causerTag($actor), 'tournament.booked|'.causerTag($actor)]);
});
