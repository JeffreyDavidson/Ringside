<?php

declare(strict_types=1);

use App\Actions\Tournaments\AwardTournamentWalkoverAction;
use App\Actions\Tournaments\BookTournamentMatchAction;
use App\Actions\Tournaments\CancelTournamentAction;
use App\Actions\Tournaments\PlanTournamentBoutAction;
use App\Actions\Tournaments\RenameTournamentAction;
use App\Actions\Tournaments\ReplaceTournamentEntryAction;
use App\Actions\Tournaments\ResetTournamentBranchAction;
use App\Actions\Tournaments\SaveTournamentAction;
use App\Actions\Tournaments\WithdrawTournamentEntryAction;
use App\Data\Tournaments\TournamentBookingData;
use App\Data\Tournaments\TournamentData;
use App\Enums\Tournaments\EntrantKind;
use App\Enums\Tournaments\TournamentStatus;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Tournaments\TournamentException;
use App\Lifecycle\Tournaments\TournamentLocks;
use App\Lifecycle\Tournaments\TournamentOutcomeManager;
use App\Models\Events\Event;
use App\Services\Tournaments\TournamentBracketService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

it('rejects stale tournament reviews with no partial mutations', function (string $operation): void {
    $world = tournamentFixture();
    $t = $world['tournament'];
    $node = $t->nodes()->firstOrFail();
    $entry = $t->entries()->firstOrFail();
    $snapshot = [$t->version, $t->name, $t->entries()->pluck('competitor_id')->all(), $t->nodes()->pluck('planned_event_id')->all()];
    $call = fn () => match ($operation) {
        default => throw new InvalidArgumentException('Unknown operation'),
        'book' => resolve(BookTournamentMatchAction::class)->handle($node, 0, new TournamentBookingData($world['event'], $world['referees']), administrator()),
        'plan' => resolve(PlanTournamentBoutAction::class)->handle($node, $world['event'], 0, administrator()),
        'reset' => resolve(ResetTournamentBranchAction::class)->handle($node, 0, 'Reviewed reset', administrator()),
        'withdraw' => resolve(WithdrawTournamentEntryAction::class)->handle($entry, 0, 'Unavailable', administrator()),
        'replace' => resolve(ReplaceTournamentEntryAction::class)->handle($entry, $entry->competitor_id, 0, 'Unavailable', administrator()),
        'walkover' => resolve(AwardTournamentWalkoverAction::class)->handle($node, 0, 'Unavailable', administrator()),
        'cancel' => resolve(CancelTournamentAction::class)->handle($t, 0, 'Cancelled show', administrator()),
        'rename' => resolve(RenameTournamentAction::class)->handle($t, 0, 'Changed', null, administrator()),
        'save' => resolve(SaveTournamentAction::class)->handle($world['promotion'], new TournamentData('Changed', null, EntrantKind::Wrestler, 4, true, array_values($t->entries()->get()->map(fn ($entry): int => $entry->competitor_id)->all())), administrator(), $t, 0),
    };
    expect($call)->toThrow(RecordChangedException::class, __('core.errors.record.changed'));
    $t->refresh();
    expect([$t->version, $t->name, $t->entries()->pluck('competitor_id')->all(), $t->nodes()->pluck('planned_event_id')->all()])->toBe($snapshot)
        ->and($world['event']->matches()->count())->toBe(0);
})->with(['book', 'plan', 'reset', 'withdraw', 'replace', 'walkover', 'cancel', 'rename', 'save']);

it('refuses closed tournament mutations', function (string $operation): void {
    $world = tournamentFixture();
    $t = $world['tournament'];
    $node = $t->nodes()->firstOrFail();
    $entry = $t->entries()->firstOrFail();
    $t->forceFill(['status' => TournamentStatus::Completed])->save();
    $call = fn () => match ($operation) {
        default => throw new InvalidArgumentException('Unknown operation'),
        'book' => resolve(BookTournamentMatchAction::class)->handle($node, $t->version, new TournamentBookingData($world['event'], $world['referees']), administrator()),
        'plan' => resolve(PlanTournamentBoutAction::class)->handle($node, $world['event'], $t->version, administrator()),
        'withdraw' => resolve(WithdrawTournamentEntryAction::class)->handle($entry, $t->version, 'Unavailable', administrator()),
        'replace' => resolve(ReplaceTournamentEntryAction::class)->handle($entry, $entry->competitor_id, $t->version, 'Unavailable', administrator()),
        'walkover' => resolve(AwardTournamentWalkoverAction::class)->handle($node, $t->version, 'Unavailable', administrator()),
        'cancel' => resolve(CancelTournamentAction::class)->handle($t, $t->version, 'Cancelled show', administrator()),
        'save' => resolve(SaveTournamentAction::class)->handle($world['promotion'], new TournamentData('Changed', null, EntrantKind::Wrestler, 4, true, array_values($t->entries()->get()->map(fn ($entry): int => $entry->competitor_id)->all())), administrator(), $t, $t->version),
    };
    expect($call)->toThrow(TournamentException::class)
        ->and($t->refresh()->status)->toBe(TournamentStatus::Completed)
        ->and($world['event']->matches()->count())->toBe(0);
})->with(['book', 'plan', 'withdraw', 'replace', 'walkover', 'cancel', 'save']);

it('cannot book a projected finalist before its qualifying results', function (): void {
    $world = tournamentFixture();
    $node = $world['tournament']->nodes()->where('round', 2)->firstOrFail();
    expect(fn () => bookTournamentNode($world, $node))->toThrow(TournamentException::class)
        ->and($world['event']->matches()->count())->toBe(0);
});

it('preserves a played entrant and rejects duplicate replacements', function (bool $played): void {
    $world = tournamentFixture();
    $t = $world['tournament'];
    $entry = $t->entries()->firstOrFail();
    $match = bookTournamentNode($world, $t->nodes()->firstOrFail());
    if ($played) {
        recordTournamentWinner($match, $t);
    }
    $replacement = $t->entries()->whereKeyNot($entry->id)->firstOrFail();
    expect(fn () => resolve(ReplaceTournamentEntryAction::class)->handle($entry, $replacement->competitor_id, $t->refresh()->version, 'Replace entry', administrator()))->toThrow(TournamentException::class)
        ->and($entry->refresh()->competitor_id)->not->toBe($replacement->competitor_id)
        ->and($match->refresh()->trashed())->toBeFalse();
})->with([true, false]);

it('withdraws a pending entrant once and retains the removed match for history', function (): void {
    $world = tournamentFixture();
    $t = $world['tournament'];
    $entry = $t->entries()->firstOrFail();
    $match = bookTournamentNode($world, $t->nodes()->firstOrFail());
    $action = resolve(WithdrawTournamentEntryAction::class);
    $action->handle($entry, $t->refresh()->version, 'Unavailable tonight', administrator());
    $version = $t->refresh()->version;
    $action->handle($entry, $version, 'Unavailable tonight', administrator());
    expect($entry->refresh()->withdrawn)->toBeTrue()->and($match->refresh()->trashed())->toBeTrue()->and($t->refresh()->version)->toBe($version);
});

it('refuses an unavailable opponent or two withdrawn entrants as a walkover', function (bool $both): void {
    $world = tournamentFixture();
    $t = $world['tournament'];
    bookTournamentNode($world, $t->nodes()->where('position', 2)->firstOrFail());
    $node = $t->nodes()->where('round', 1)->where('position', 1)->firstOrFail();
    $bout = resolve(TournamentBracketService::class)->bout($node);
    $first = $bout->first ?? throw new RuntimeException('Expected entrant');
    $second = $bout->second ?? throw new RuntimeException('Expected entrant');
    resolve(WithdrawTournamentEntryAction::class)->handle($first, $t->refresh()->version, 'Cannot compete', administrator());
    if ($both) {
        resolve(WithdrawTournamentEntryAction::class)->handle($second, $t->refresh()->version, 'Cannot compete', administrator());
    }
    if (! $both) {
        $second->competitor()->firstOrFail()->delete();
    }
    expect(fn () => resolve(AwardTournamentWalkoverAction::class)->handle($node, $t->refresh()->version, 'Advance opponent', administrator()))->toThrow(TournamentException::class)
        ->and($node->refresh()->walkover_entry_id)->toBeNull();
})->with([true, false]);

it('keeps cancellation retries and subsequent synchronization unchanged', function (): void {
    $world = tournamentFixture();
    $t = $world['tournament'];
    $action = resolve(CancelTournamentAction::class);
    $action->handle($t, $t->version, 'No show', administrator());
    $version = $t->refresh()->version;
    $action->handle($t, $version, 'No show', administrator());
    resolve(TournamentOutcomeManager::class)->synchronize($t, administrator());
    expect($t->refresh()->status)->toBe(TournamentStatus::Cancelled)->and($t->version)->toBe($version);
});

it('rejects locking a tournament id that does not exist', function (): void {
    expect(fn () => resolve(TournamentLocks::class)->lockMany([PHP_INT_MAX]))->toThrow(RecordChangedException::class);
});
it('refuses a review if event membership or dates changed while obtaining its union locks', function (bool $dateChanged): void {
    $world = tournamentFixture();
    $t = $world['tournament'];
    $event = $world['event'];
    $node = $t->nodes()->firstOrFail();
    resolve(PlanTournamentBoutAction::class)->handle($node, $event, $t->version, administrator());
    $other = Event::factory()->for($world['promotion'])->create();
    $pending = true;
    DB::listen(function (QueryExecuted $query) use (&$pending, $event, $other, $node, $dateChanged): void {
        $sql = normalizedSql($query->sql);
        if (! $pending || ! str_contains($sql, 'select "id" from "events"') || ! str_contains($sql, 'order by "id" asc')) {
            return;
        }
        $pending = false;
        if ($dateChanged) {
            $event->update(['date' => now()->addWeek()]);

            return;
        }
        $node->forceFill(['planned_event_id' => $other->id])->save();
    });
    expect(fn () => DB::transaction(fn () => resolve(TournamentLocks::class)->lock($t->id)))->toThrow(RecordChangedException::class)
        ->and($t->refresh()->version)->toBe(2)
        ->and($node->refresh()->planned_event_id)->toBe($event->id);
})->with([true, false]);
