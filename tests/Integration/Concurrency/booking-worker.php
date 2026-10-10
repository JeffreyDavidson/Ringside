<?php

declare(strict_types=1);

/*
 * Child process for BookingConcurrencyTest: books one match through AddMatchForEventAction, or, when the spec
 * carries a reschedule_date, moves an event to that date through Events\UpdateAction. A spec with a
 * create_event_at_venue_id creates an event at that venue through Events\CreateAction, and one with a
 * demote_user_id makes that owner a plain member of the promotion through UpdatePromotionMemberRoleAction. A spec
 * with a split_stable_id splits that stable through SplitStableAction, moving the listed wrestlers (comma-separated ids) into a new
 * stable named new_name. A create_stable_name creates an unformed stable with that name through Stables\CreateAction, a
 * restore_stable_id restores that deleted stable through Stables\RestoreAction, and an update_stable_id renames that stable to
 * new_name through Stables\UpdateAction.
 *
 * Usage: php booking-worker.php '<json spec>'
 *
 * The worker boots the application, opens its database connection, announces READY and then blocks on
 * STDIN until the parent sends a common start time, so every worker is released at the same instant.
 * A start_delay_ms in the spec holds that one worker back after the common start, so it lands inside the
 * other worker's transaction. It prints a single RESULT line as JSON.
 */

use App\Actions\Events\CreateAction;
use App\Actions\Events\RestoreAction;
use App\Actions\Events\UpdateAction;
use App\Actions\Matches\AddMatchForEventAction;
use App\Actions\Promotions\UpdatePromotionMemberRoleAction;
use App\Actions\Stables\CreateAction as CreateStableAction;
use App\Actions\Stables\RestoreAction as RestoreStableAction;
use App\Actions\Stables\SplitStableAction;
use App\Actions\Stables\UpdateAction as UpdateStableAction;
use App\Data\Events\EventData;
use App\Data\Matches\EventMatchData;
use App\Data\Stables\StableData;
use App\Data\Stables\StableMembershipData;
use App\Enums\MatchType;
use App\Enums\Promotions\MembershipRole;
use App\Exceptions\BaseBusinessException;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Users\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 3).'/vendor/autoload.php';
require_once __DIR__.'/worker-support.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$arguments = $_SERVER['argv'] ?? [];
$payload = is_array($arguments) ? ($arguments[1] ?? null) : null;

if (! is_string($payload)) {
    fwrite(STDERR, "Missing booking spec.\n");
    exit(1);
}

/** @var array{split_stable_id: int, new_name: string, wrestler_ids: string}|array{create_stable_name: string}|array{restore_stable_id: int}|array{update_stable_id: int, new_name: string}|array{restore_event_id: int, start_delay_ms?: int}|array{event_id: int, reschedule_date: string}|array{create_event_at_venue_id: int, date: string}|array{promotion_id: int, demote_user_id: int}|array{event_id: int, first_wrestler_id: int, second_wrestler_id: int, referee_id: int} $spec */
$spec = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

DB::select('select 1');
fwrite(STDOUT, "READY\n");
// The parent sends the same absolute start time to every worker; spin until then so the actions overlap.
$startAt = (float) fgets(STDIN) + (($spec['start_delay_ms'] ?? 0) / 1000);

while (microtime(true) < $startAt) {
    // Busy-wait for the release time.
}

$result = ['ok' => true, 'exception' => null, 'deadlock' => false];

try {
    if (isset($spec['split_stable_id'])) {
        resolve(SplitStableAction::class)->handle(
            Stable::query()->findOrFail($spec['split_stable_id']),
            $spec['new_name'],
            new StableMembershipData(wrestlers: Wrestler::query()->whereKey(explode(',', $spec['wrestler_ids']))->get()),
            Carbon::now(),
        );
    } elseif (isset($spec['create_stable_name'])) {
        resolve(CreateStableAction::class)->handle(
            new StableData($spec['create_stable_name'], null, new StableMembershipData),
        );
    } elseif (isset($spec['restore_stable_id'])) {
        resolve(RestoreStableAction::class)->handle(Stable::withTrashed()->findOrFail($spec['restore_stable_id']));
    } elseif (isset($spec['update_stable_id'])) {
        resolve(UpdateStableAction::class)->handle(
            Stable::query()->findOrFail($spec['update_stable_id']),
            new StableData($spec['new_name'], null, new StableMembershipData),
        );
    } elseif (isset($spec['create_event_at_venue_id'])) {
        resolve(CreateAction::class)->handle(
            new EventData('Concurrent Venue Event', Carbon::parse($spec['date']), Venue::query()->findOrFail($spec['create_event_at_venue_id']), null),
        );
    } elseif (isset($spec['demote_user_id'])) {
        resolve(UpdatePromotionMemberRoleAction::class)->handle(
            Promotion::query()->findOrFail($spec['promotion_id']),
            User::query()->findOrFail($spec['demote_user_id']),
            MembershipRole::Member,
        );
    } elseif (isset($spec['restore_event_id'])) {
        resolve(RestoreAction::class)->handle(Event::withTrashed()->findOrFail($spec['restore_event_id']));
    } elseif (isset($spec['reschedule_date'])) {
        $event = Event::query()->findOrFail($spec['event_id']);

        resolve(UpdateAction::class)->handle(
            $event,
            new EventData($event->name, Carbon::parse($spec['reschedule_date']), null, $event->preview),
        );
    } else {
        resolve(AddMatchForEventAction::class)->handle(
            Event::query()->findOrFail($spec['event_id']),
            new EventMatchData(
                MatchType::Singles,
                Referee::query()->whereKey($spec['referee_id'])->get(),
                Title::query()->whereKey([])->get(),
                collect([
                    1 => ['wrestlers' => [Wrestler::query()->findOrFail($spec['first_wrestler_id'])]],
                    2 => ['wrestlers' => [Wrestler::query()->findOrFail($spec['second_wrestler_id'])]],
                ]),
                null,
            ),
        );
    }
} catch (BaseBusinessException $exception) {
    $result = ['ok' => false, 'exception' => $exception::class, 'deadlock' => false];
} catch (DeadlockException|QueryException $exception) {
    $result = ['ok' => false, 'exception' => $exception::class, 'deadlock' => isDeadlock($exception), 'message' => mb_substr($exception->getMessage(), 0, 400)];
}

fwrite(STDOUT, 'RESULT:'.json_encode($result)."\n");
