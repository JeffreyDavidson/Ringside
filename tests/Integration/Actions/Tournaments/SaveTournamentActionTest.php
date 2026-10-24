<?php

declare(strict_types=1);

use App\Actions\Tournaments\SaveTournamentAction;
use App\Data\Tournaments\TournamentData;
use App\Enums\Tournaments\EntrantKind;
use App\Enums\Tournaments\TournamentStatus;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Tournaments\TournamentException;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Tournaments\Tournament;
use App\Services\Tournaments\TournamentBracketService;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

it('creates an eight entrant seeded bracket without booking fictional matches', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $wrestlers = Wrestler::factory()->employed()->for($promotion)->count(8)->create();

    // Act
    $tournament = resolve(SaveTournamentAction::class)->handle($promotion, new TournamentData(
        name: 'Autumn Cup',
        description: null,
        kind: EntrantKind::Wrestler,
        capacity: 8,
        repeatAppearances: false,
        competitorIds: array_values($wrestlers->map(fn ($competitor): int => $competitor->id)->all()),
    ), administrator());

    // Assert
    expect($tournament->status)->toBe(TournamentStatus::Draft)
        ->and($tournament->entries()->count())->toBe(8)
        ->and($tournament->nodes()->count())->toBe(7)
        ->and($tournament->links()->count())->toBe(0)
        ->and($tournament->nodes()->where('round', 1)->orderBy('position')->get()->map(fn ($node): array => [$node->first_seed, $node->second_seed])->all())
        ->toBe([[1, 8], [4, 5], [2, 7], [3, 6]]);
});

it('pads seeded brackets with explicit byes and never books empty nodes', function (int $capacity): void {
    $world = tournamentFixture(capacity: $capacity, count: 2);
    $bouts = resolve(TournamentBracketService::class)->bouts($world['tournament']);
    expect($bouts->count())->toBe($capacity - 1)->and($bouts->last()?->inputsResolved)->toBeTrue()->and($bouts->last()?->first)->not->toBeNull()->and($bouts->last()?->second)->not->toBeNull()->and($world['event']->matches()->count())->toBe(0);
})->with([4, 8, 16]);

it('rejects duplicate or foreign entrants without creating a bracket', function (): void {
    $promotion = Promotion::factory()->create();
    $foreign = Wrestler::factory()->employed()->count(2)->create();
    expect(fn () => resolve(SaveTournamentAction::class)->handle($promotion, new TournamentData('Foreign', null, EntrantKind::Wrestler, 4, false, array_values($foreign->map(fn ($competitor): int => $competitor->id)->all())), administrator()))->toThrow(TournamentException::class)
        ->and(Tournament::query()->count())->toBe(0);
});

it('returns the same tournament when a create request is retried with its client identifier', function (): void {
    $promotion = Promotion::factory()->create();
    $wrestlers = Wrestler::factory()->employed()->for($promotion)->count(2)->create();
    $data = new TournamentData('Retry Cup', null, EntrantKind::Wrestler, 4, false, array_values($wrestlers->map(fn ($competitor): int => $competitor->id)->all()), requestId: Str::uuid()->toString());
    $first = resolve(SaveTournamentAction::class)->handle($promotion, $data, administrator());
    $second = resolve(SaveTournamentAction::class)->handle($promotion, $data, administrator());
    expect($second->id)->toBe($first->id)->and(Tournament::query()->count())->toBe(1);
});

it('refuses a conflicting retry payload for an already accepted create identifier', function (): void {
    $promotion = Promotion::factory()->create();
    $entrants = Wrestler::factory()->employed()->for($promotion)->count(2)->create();
    $ids = array_values($entrants->map(fn (Wrestler $wrestler): int => $wrestler->id)->all());
    $identifier = Str::uuid()->toString();
    resolve(SaveTournamentAction::class)->handle($promotion, new TournamentData('Original Cup', null, EntrantKind::Wrestler, 4, false, $ids, $identifier), administrator());
    expect(fn () => resolve(SaveTournamentAction::class)->handle($promotion, new TournamentData('Different Cup', null, EntrantKind::Wrestler, 4, false, $ids, $identifier), administrator()))->toThrow(RecordChangedException::class)
        ->and(Tournament::query()->sole()->name)->toBe('Original Cup');
});

it('stores the seeded bracket nodes for every supported capacity', function (int $capacity, array $nodes): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $wrestlers = Wrestler::factory()->employed()->for($promotion)->count($capacity)->create();
    $data = new TournamentData('Seed Cup', null, EntrantKind::Wrestler, $capacity, false, array_values($wrestlers->map(fn ($competitor): int => $competitor->id)->all()));

    // Act
    $tournament = resolve(SaveTournamentAction::class)->handle($promotion, $data, administrator());

    // Assert
    expect($tournament->nodes()->orderBy('id')->get()->map(fn ($node): array => [$node->round, $node->position, $node->first_seed, $node->second_seed])->all())
        ->toBe($nodes);
})->with([
    'four' => [4, [[1, 1, 1, 4], [1, 2, 2, 3], [2, 1, null, null]]],
    'eight' => [8, [[1, 1, 1, 8], [1, 2, 4, 5], [1, 3, 2, 7], [1, 4, 3, 6], [2, 1, null, null], [2, 2, null, null], [3, 1, null, null]]],
    'sixteen' => [16, [
        [1, 1, 1, 16], [1, 2, 8, 9], [1, 3, 4, 13], [1, 4, 5, 12], [1, 5, 2, 15], [1, 6, 7, 10], [1, 7, 3, 14], [1, 8, 6, 11],
        [2, 1, null, null], [2, 2, null, null], [2, 3, null, null], [2, 4, null, null],
        [3, 1, null, null], [3, 2, null, null],
        [4, 1, null, null],
    ]],
]);

test('saving a tournament names the actor passed in as the audit causer', function (): void {
    // Arrange
    actingAs(administrator());
    $actor = administrator();
    $promotion = Promotion::factory()->create();
    $wrestlers = Wrestler::factory()->employed()->for($promotion)->count(4)->create();
    $data = new TournamentData('Autumn Cup', null, EntrantKind::Wrestler, 4, true, array_values($wrestlers->map(fn ($competitor): int => $competitor->id)->all()));
    $since = lastActivityId();

    // Act
    resolve(SaveTournamentAction::class)->handle($promotion, $data, $actor);

    // Assert
    expect(activityEntriesAfter($since, 'tournaments'))->toBe(['tournament.saved|'.causerTag($actor)]);
});
