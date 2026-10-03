<?php

declare(strict_types=1);

/*
 * Child process for BookingConcurrencyTest: books one match through AddMatchForEventAction, or, when the spec
 * carries a reschedule_date, moves an event to that date through Events\UpdateAction. A spec with a
 * create_event_at_venue_id creates an event at that venue through Events\CreateAction, and one with a
 * demote_user_id makes that owner a plain member of the promotion through UpdatePromotionMemberRoleAction.
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
use App\Data\Events\EventData;
use App\Data\Matches\EventMatchData;
use App\Enums\MatchType;
use App\Enums\Promotions\MembershipRole;
use App\Exceptions\BaseBusinessException;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Users\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$arguments = $_SERVER['argv'] ?? [];
$payload = is_array($arguments) ? ($arguments[1] ?? null) : null;

if (! is_string($payload)) {
    fwrite(STDERR, "Missing booking spec.\n");
    exit(1);
}

/** @var array{restore_event_id: int, start_delay_ms?: int}|array{event_id: int, reschedule_date: string}|array{create_event_at_venue_id: int, date: string}|array{promotion_id: int, demote_user_id: int}|array{event_id: int, first_wrestler_id: int, second_wrestler_id: int, referee_id: int} $spec */
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
    if (isset($spec['create_event_at_venue_id'])) {
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
} catch (QueryException $exception) {
    $result = ['ok' => false, 'exception' => $exception::class, 'deadlock' => ($exception->errorInfo[0] ?? null) === '40P01'];
}

fwrite(STDOUT, 'RESULT:'.json_encode($result)."\n");
