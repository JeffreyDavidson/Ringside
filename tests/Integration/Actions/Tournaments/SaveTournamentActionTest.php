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

it('creates an eight entrant seeded bracket without booking fictional matches', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $wrestlers = Wrestler::factory()->employed()->for($promotion)->count(8)->create();

    // Act
    $tournament = resolve(SaveTournamentAction::class)->handle($promotion, new TournamentData(
        name: 'Autumn Cup', description: null, kind: EntrantKind::Wrestler,
        capacity: 8, repeatAppearances: false, competitorIds: array_values($wrestlers->map(fn ($competitor): int => $competitor->id)->all()),
    ));

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
    expect(fn () => resolve(SaveTournamentAction::class)->handle($promotion, new TournamentData('Foreign', null, EntrantKind::Wrestler, 4, false, array_values($foreign->map(fn ($competitor): int => $competitor->id)->all()))))->toThrow(TournamentException::class)
        ->and(Tournament::query()->count())->toBe(0);
});

it('returns the same tournament when a create request is retried with its client identifier', function (): void {
    $promotion = Promotion::factory()->create();
    $wrestlers = Wrestler::factory()->employed()->for($promotion)->count(2)->create();
    $data = new TournamentData('Retry Cup', null, EntrantKind::Wrestler, 4, false, array_values($wrestlers->map(fn ($competitor): int => $competitor->id)->all()), requestId: Str::uuid()->toString());
    $first = resolve(SaveTournamentAction::class)->handle($promotion, $data);
    $second = resolve(SaveTournamentAction::class)->handle($promotion, $data);
    expect($second->id)->toBe($first->id)->and(Tournament::query()->count())->toBe(1);
});

it('refuses a conflicting retry payload for an already accepted create identifier', function (): void {
    $promotion = Promotion::factory()->create();
    $entrants = Wrestler::factory()->employed()->for($promotion)->count(2)->create();
    $ids = array_values($entrants->map(fn (Wrestler $wrestler): int => $wrestler->id)->all());
    $identifier = Str::uuid()->toString();
    resolve(SaveTournamentAction::class)->handle($promotion, new TournamentData('Original Cup', null, EntrantKind::Wrestler, 4, false, $ids, $identifier));
    expect(fn () => resolve(SaveTournamentAction::class)->handle($promotion, new TournamentData('Different Cup', null, EntrantKind::Wrestler, 4, false, $ids, $identifier)))->toThrow(RecordChangedException::class)
        ->and(Tournament::query()->sole()->name)->toBe('Original Cup');
});
