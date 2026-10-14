<?php

declare(strict_types=1);
use App\Actions\Tournaments\BookTournamentMatchAction;
use App\Actions\Tournaments\SaveTournamentAction;
use App\Data\Tournaments\TournamentData;
use App\Enums\Tournaments\EntrantKind;
use App\Enums\Tournaments\TournamentStatus;
use App\Exceptions\Tournaments\TournamentException;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

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
