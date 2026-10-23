<?php

declare(strict_types=1);
use App\Actions\Events\UpdateAction;
use App\Actions\Matches\AddWrestlersToMatchAction;
use App\Actions\Matches\RecordResultAction;
use App\Actions\Tournaments\BookTournamentMatchAction;
use App\Actions\Tournaments\ResetTournamentBranchAction;
use App\Actions\Wrestlers\ReleaseAction;
use App\Data\Events\EventData;
use App\Data\Matches\MatchResultData;
use App\Data\Tournaments\TournamentBookingData;
use App\Enums\Matches\MatchFinish;
use App\Exceptions\BaseBusinessException;
use App\Lifecycle\Matches\MatchSchedulingLock;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchSide;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Tournaments\TournamentNode;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
require __DIR__.'/worker-support.php';
$arguments = $_SERVER['argv'];

if (! is_array($arguments) || ! isset($arguments[1]) || ! is_string($arguments[1])) {
    throw new RuntimeException('Missing worker specification');
}

$spec = json_decode($arguments[1], true, flags: JSON_THROW_ON_ERROR);

if (! is_array($spec) || ! isset($spec['operation'],$spec['node_id'],$spec['event_id'],$spec['referee_id'],$spec['version'],$spec['match_id'],$spec['side_id']) || ! is_string($spec['operation']) || ! is_int($spec['node_id']) || ! is_int($spec['event_id']) || ! is_int($spec['referee_id']) || ! is_int($spec['version']) || ! is_int($spec['match_id']) || ! is_int($spec['side_id'])) {
    throw new RuntimeException('Invalid worker specification');
}

$defaultActor = workerActor();
DB::select('select 1');
fwrite(STDOUT, "READY\n");
$startAt = (float) fgets(STDIN);

while (microtime(true) < $startAt) {
    usleep(1000);
}

$result = ['ok' => true, 'exception' => null, 'deadlock' => false, 'match_id' => null];

try {
    if ($spec['operation'] === 'book') {
        $match = resolve(BookTournamentMatchAction::class)->handle(TournamentNode::query()->findOrFail($spec['node_id']), $spec['version'], new TournamentBookingData(Event::query()->findOrFail($spec['event_id']), Referee::query()->whereKey($spec['referee_id'])->get()), $defaultActor);
        $result['match_id'] = $match->id;
    }

    if ($spec['operation'] === 'result') {
        resolve(RecordResultAction::class)->handle(EventMatch::query()->findOrFail($spec['match_id']), new MatchResultData(MatchFinish::Pinfall, MatchSide::query()->findOrFail($spec['side_id']), collect(), $spec['version']), $defaultActor);
    }

    if ($spec['operation'] === 'reset') {
        resolve(ResetTournamentBranchAction::class)->handle(TournamentNode::query()->findOrFail($spec['node_id']), $spec['version'], 'Concurrency reset', $defaultActor);
    }

    if ($spec['operation'] === 'ordinary') {
        DB::transaction(function () use ($spec): void {
            $lockedMatch = resolve(MatchSchedulingLock::class)->lockMatchWithEventSet(EventMatch::query()->findOrFail($spec['match_id']));
            resolve(AddWrestlersToMatchAction::class)->handle($lockedMatch, collect([Wrestler::query()->findOrFail($spec['side_id'])]), 1);
        }, attempts: 3);
    }

    if ($spec['operation'] === 'reschedule') {
        $event = Event::query()->findOrFail($spec['event_id']);
        resolve(UpdateAction::class)->handle($event, new EventData($event->name, $event->date?->copy()->addHours(2), null, null));
    }

    if ($spec['operation'] === 'release') {
        resolve(ReleaseAction::class)->handle(Wrestler::query()->findOrFail($spec['side_id']));
    }
} catch (BaseBusinessException $exception) {
    $result = ['ok' => false, 'exception' => $exception::class, 'deadlock' => false, 'message' => $exception->getMessage()];
} catch (DeadlockException|QueryException $exception) {
    $result = ['ok' => false, 'exception' => $exception::class, 'deadlock' => isDeadlock($exception), 'message' => $exception->getMessage()];
}

fwrite(STDOUT, 'RESULT:'.json_encode($result, JSON_THROW_ON_ERROR)."\n");
