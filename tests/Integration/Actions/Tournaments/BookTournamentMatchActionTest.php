<?php

declare(strict_types=1);
use App\Actions\Roster\Availability\CreateAction;
use App\Actions\Tournaments\BookTournamentMatchAction;
use App\Actions\Tournaments\SaveTournamentAction;
use App\Data\Tournaments\TournamentData;
use App\Enums\Tournaments\EntrantKind;
use App\Enums\Tournaments\TournamentStatus;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Exceptions\Tournaments\TournamentException;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Tournaments\TournamentNode;
use Illuminate\Database\Eloquent\Collection;

use function Pest\Laravel\actingAs;

it('books a resolved quarterfinal through the ordinary match flow and freezes its bracket', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $wrestlers = Wrestler::factory()->employed()->for($promotion)->count(8)->create();
    $tournament = resolve(SaveTournamentAction::class)->handle($promotion, new TournamentData('Autumn Cup', null, EntrantKind::Wrestler, 8, false, array_values($wrestlers->map(fn ($competitor): int => $competitor->id)->all())));
    $event = Event::factory()->for($promotion)->create();
    $referee = Referee::factory()->employed()->for($promotion)->create();
    $node = $tournament->nodes()->firstOrFail();

    // Act
    $match = resolve(BookTournamentMatchAction::class)->handle($node, $event, new Collection([$referee]), $tournament->version);

    // Assert
    expect($match->wrestlers()->pluck('wrestlers.id')->all())->toEqualCanonicalizing([$wrestlers->pluck('id')->all()[0], $wrestlers->pluck('id')->all()[7]])
        ->and($match->sides()->pluck('position')->all())->toBe([1, 2])
        ->and($match->referees()->count())->toBe(1)
        ->and($tournament->refresh()->status)->toBe(TournamentStatus::Active)
        ->and($tournament->version)->toBe(2)
        ->and($node->links()->firstOrFail()->match_id)->toBe($match->id);
});

it('rejects an incompatible stored entrant identity rather than booking a mixed tournament', function (EntrantKind $kind): void {
    $world = tournamentFixture(kind: $kind);
    $t = $world['tournament'];
    $entry = $t->entries()->firstOrFail();
    $foreignKind = $kind === EntrantKind::Wrestler ? TagTeam::factory()->bookable()->for($world['promotion'])->recycle($world['promotion'])->create() : Wrestler::factory()->employed()->for($world['promotion'])->create();
    $entry->forceFill(['competitor_type' => $foreignKind->getMorphClass(), 'competitor_id' => $foreignKind->id])->save();
    expect(fn () => bookTournamentNode($world, $t->nodes()->firstOrFail()))->toThrow(TournamentException::class)
        ->and($world['event']->matches()->count())->toBe(0);
})->with(EntrantKind::cases());

it('keeps calendar restrictions binding on a newly qualified same-card bout', function (EntrantKind $kind): void {
    // Arrange
    $actor = administrator();
    actingAs($actor);
    $world = tournamentFixture(kind: $kind);
    $tournament = $world['tournament'];
    $nodes = $tournament->nodes()->get();
    $first = bookTournamentNode($world, $nodes->sole(fn (TournamentNode $node): bool => $node->round === 1 && $node->position === 1));
    $second = bookTournamentNode($world, $nodes->sole(fn (TournamentNode $node): bool => $node->round === 1 && $node->position === 2));
    recordTournamentWinner($first, $tournament);
    recordTournamentWinner($second, $tournament);
    $competitor = $tournament->entries()->where('seed', 1)->firstOrFail()->competitor()->firstOrFail();
    $person = $competitor instanceof TagTeam ? $competitor->currentWrestlers()->firstOrFail() : $competitor;
    $person->forceFill(['promotion_id' => $world['promotion']->id])->save();
    $day = $world['event']->local_date?->toDateString() ?? throw new LogicException('Expected a dated event');
    resolve(CreateAction::class)->handle($person, $day, $day, null, $actor);
    $final = $nodes->sole(fn (TournamentNode $node): bool => $node->round === 2);
    $version = $tournament->refresh()->version;

    // Act / Assert
    expect(fn () => bookTournamentNode($world, $final))->toThrow(EntityNotAvailableException::class)
        ->and($world['event']->matches()->count())->toBe(2)
        ->and($final->links()->count())->toBe(0)
        ->and($tournament->refresh()->version)->toBe($version);
})->with(EntrantKind::cases());
