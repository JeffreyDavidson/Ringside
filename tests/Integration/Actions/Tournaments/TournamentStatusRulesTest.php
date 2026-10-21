<?php

declare(strict_types=1);

use App\Actions\Tournaments\AwardTournamentWalkoverAction;
use App\Actions\Tournaments\BookTournamentMatchAction;
use App\Actions\Tournaments\CancelTournamentAction;
use App\Actions\Tournaments\PlanTournamentBoutAction;
use App\Actions\Tournaments\ReplaceTournamentEntryAction;
use App\Actions\Tournaments\SaveTournamentAction;
use App\Actions\Tournaments\WithdrawTournamentEntryAction;
use App\Data\Tournaments\TournamentBookingData;
use App\Data\Tournaments\TournamentData;
use App\Enums\Tournaments\EntrantKind;
use App\Enums\Tournaments\TournamentStatus;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Tournaments\TournamentException;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentEntry;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Runs one tournament operation against the fixture's tournament at its current version.
 *
 * @param  array{tournament:Tournament,promotion:Promotion,event:Event,referees:Collection<int,Referee>}  $world
 */
function runTournamentOperation(array $world, string $operation, ?int $version = null): void
{
    $t = $world['tournament'];
    $version ??= $t->version;
    $node = $t->nodes()->orderBy('id')->firstOrFail();
    $entry = $t->entries()->orderBy('id')->firstOrFail();

    match ($operation) {
        default => throw new InvalidArgumentException('Unknown operation'),
        'book' => resolve(BookTournamentMatchAction::class)->handle($node, $version, new TournamentBookingData($world['event'], $world['referees'])),
        'plan' => resolve(PlanTournamentBoutAction::class)->handle($node, $world['event'], $version),
        'withdraw' => resolve(WithdrawTournamentEntryAction::class)->handle($entry, $version, 'Unavailable'),
        'replace' => resolve(ReplaceTournamentEntryAction::class)->handle($entry, $entry->competitor_id, $version, 'Unavailable'),
        'walkover' => resolve(AwardTournamentWalkoverAction::class)->handle($node, $version, 'Unavailable'),
        'cancel' => resolve(CancelTournamentAction::class)->handle($t, $version, 'Cancelled show'),
        'save' => resolve(SaveTournamentAction::class)->handle($world['promotion'], new TournamentData('Changed', null, EntrantKind::Wrestler, 4, true, array_values($t->entries()->orderBy('seed')->get()->map(fn (TournamentEntry $entry): int => $entry->competitor_id)->all())), $t, $version),
    };
}

it('refuses an operation the tournament status does not allow', function (TournamentStatus $status, string $operation, string $messageKey): void {
    // Arrange
    $world = tournamentFixture();
    $world['tournament']->forceFill(['status' => $status])->save();
    $versionBefore = $world['tournament']->version;

    // Act
    $attempt = fn () => runTournamentOperation($world, $operation);

    // Assert
    expect($attempt)->toThrow(TournamentException::class, __($messageKey))
        ->and($world['tournament']->refresh()->status)->toBe($status)
        ->and($world['tournament']->version)->toBe($versionBefore)
        ->and($world['event']->matches()->count())->toBe(0);
})->with([
    'plan when completed' => [TournamentStatus::Completed, 'plan', 'tournaments.errors.closed'],
    'plan when cancelled' => [TournamentStatus::Cancelled, 'plan', 'tournaments.errors.closed'],
    'book when completed' => [TournamentStatus::Completed, 'book', 'tournaments.errors.closed'],
    'book when cancelled' => [TournamentStatus::Cancelled, 'book', 'tournaments.errors.closed'],
    'withdraw when draft' => [TournamentStatus::Draft, 'withdraw', 'tournaments.errors.closed'],
    'withdraw when completed' => [TournamentStatus::Completed, 'withdraw', 'tournaments.errors.closed'],
    'withdraw when cancelled' => [TournamentStatus::Cancelled, 'withdraw', 'tournaments.errors.closed'],
    'replace when draft' => [TournamentStatus::Draft, 'replace', 'tournaments.errors.closed'],
    'replace when completed' => [TournamentStatus::Completed, 'replace', 'tournaments.errors.closed'],
    'replace when cancelled' => [TournamentStatus::Cancelled, 'replace', 'tournaments.errors.closed'],
    'walkover when draft' => [TournamentStatus::Draft, 'walkover', 'tournaments.errors.closed'],
    'walkover when completed' => [TournamentStatus::Completed, 'walkover', 'tournaments.errors.closed'],
    'walkover when cancelled' => [TournamentStatus::Cancelled, 'walkover', 'tournaments.errors.closed'],
    'cancel when completed' => [TournamentStatus::Completed, 'cancel', 'tournaments.errors.closed'],
    'save when active' => [TournamentStatus::Active, 'save', 'tournaments.errors.structure_frozen'],
    'save when completed' => [TournamentStatus::Completed, 'save', 'tournaments.errors.structure_frozen'],
    'save when cancelled' => [TournamentStatus::Cancelled, 'save', 'tournaments.errors.structure_frozen'],
]);

it('checks the reviewed version before the tournament status', function (string $operation): void {
    // Arrange
    $world = tournamentFixture();
    $world['tournament']->forceFill(['status' => TournamentStatus::Cancelled])->save();

    // Act
    $attempt = fn () => runTournamentOperation($world, $operation, 0);

    // Assert
    expect($attempt)->toThrow(RecordChangedException::class, __('core.errors.record.changed'));
})->with(['book', 'plan', 'withdraw', 'replace', 'walkover', 'cancel']);

it('plans a bout while the tournament is still open', function (TournamentStatus $status): void {
    // Arrange
    $world = tournamentFixture();
    $world['tournament']->forceFill(['status' => $status])->save();
    $node = $world['tournament']->nodes()->orderBy('id')->firstOrFail();

    // Act
    runTournamentOperation($world, 'plan');

    // Assert
    expect($node->refresh()->planned_event_id)->toBe($world['event']->id)
        ->and($world['tournament']->refresh()->status)->toBe($status);
})->with([
    'draft' => [TournamentStatus::Draft],
    'active' => [TournamentStatus::Active],
]);

it('activates a draft tournament on its first booking and stays active on the next', function (): void {
    // Arrange
    $world = tournamentFixture();
    $statusBefore = $world['tournament']->status;

    // Act
    bookTournamentNode($world, $world['tournament']->nodes()->where('round', 1)->where('position', 1)->firstOrFail());
    $afterFirst = $world['tournament']->refresh()->status;
    $activationsAfterFirst = Activity::query()->where('description', 'tournament.activated')->count();
    bookTournamentNode($world, $world['tournament']->nodes()->where('round', 1)->where('position', 2)->firstOrFail());

    // Assert
    expect($statusBefore)->toBe(TournamentStatus::Draft)
        ->and($afterFirst)->toBe(TournamentStatus::Active)
        ->and($activationsAfterFirst)->toBe(1)
        ->and($world['tournament']->refresh()->status)->toBe(TournamentStatus::Active)
        ->and($world['tournament']->version)->toBe(3)
        ->and(Activity::query()->where('description', 'tournament.activated')->count())->toBe(1);
});

it('cancels a tournament that has not finished', function (TournamentStatus $status): void {
    // Arrange
    $world = tournamentFixture();
    $world['tournament']->forceFill(['status' => $status])->save();

    // Act
    runTournamentOperation($world, 'cancel');

    // Assert
    expect($world['tournament']->refresh()->status)->toBe(TournamentStatus::Cancelled);
})->with([
    'draft' => [TournamentStatus::Draft],
    'active' => [TournamentStatus::Active],
]);

it('treats cancelling a cancelled tournament as a no-op', function (): void {
    // Arrange
    $world = tournamentFixture();
    $world['tournament']->forceFill(['status' => TournamentStatus::Cancelled])->save();
    $versionBefore = $world['tournament']->version;

    // Act
    runTournamentOperation($world, 'cancel');

    // Assert
    expect($world['tournament']->refresh()->status)->toBe(TournamentStatus::Cancelled)
        ->and($world['tournament']->version)->toBe($versionBefore);
});
