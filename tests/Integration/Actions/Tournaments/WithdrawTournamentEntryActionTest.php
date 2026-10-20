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

it('withdraws an entrant explicitly and advances only the opposing entry by a labelled non-match walkover', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $wrestlers = Wrestler::factory()->employed()->for($promotion)->count(4)->create();
    $tournament = resolve(SaveTournamentAction::class)->handle($promotion, new TournamentData('Autumn Cup', null, EntrantKind::Wrestler, 4, true, array_values($wrestlers->map(fn ($competitor): int => $competitor->id)->all())));
    $entry = $tournament->entries()->firstOrFail();
    $nodes = $tournament->nodes()->get();
    $node = $nodes->sole(fn (TournamentNode $n): bool => $n->round === 1 && $n->position === 1);
    resolve(BookTournamentMatchAction::class)->handle($nodes->sole(fn (TournamentNode $n): bool => $n->round === 1 && $n->position === 2), $tournament->version, new TournamentBookingData(Event::factory()->for($promotion)->create(), new Collection([Referee::factory()->employed()->for($promotion)->create()])));
    $tournament->refresh();

    // Act
    resolve(WithdrawTournamentEntryAction::class)->handle($entry, $tournament->version, 'Unable to compete');
    resolve(AwardTournamentWalkoverAction::class)->handle($node, $tournament->refresh()->version, 'Opponent withdrew');

    // Assert
    expect($entry->refresh()->withdrawn)->toBeTrue()
        ->and(resolve(TournamentBracketService::class)->bout($node)->winner?->competitor_id)->toBe($wrestlers->pluck('id')->all()[3])
        ->and($node->refresh()->walkover_reason)->toBe('Opponent withdrew')
        ->and($node->links()->count())->toBe(0);
});
