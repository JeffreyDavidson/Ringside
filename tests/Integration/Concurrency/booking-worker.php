<?php

declare(strict_types=1);

/*
 * Child process for BookingConcurrencyTest: books one match through AddMatchForEventAction.
 *
 * Usage: php booking-worker.php '<json spec>'
 *
 * The worker boots the application, opens its database connection, announces READY and then blocks on
 * STDIN so the parent can release every worker at once. It prints a single RESULT line as JSON.
 */

use App\Actions\Matches\AddMatchForEventAction;
use App\Data\Matches\EventMatchData;
use App\Enums\MatchType;
use App\Exceptions\BaseBusinessException;
use App\Models\Events\Event;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
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

/** @var array{event_id: int, first_wrestler_id: int, second_wrestler_id: int, referee_id: int} $spec */
$spec = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

DB::select('select 1');
fwrite(STDOUT, "READY\n");
fgets(STDIN);

$result = ['ok' => true, 'exception' => null, 'deadlock' => false];

try {
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
} catch (BaseBusinessException $exception) {
    $result = ['ok' => false, 'exception' => $exception::class, 'deadlock' => false];
} catch (QueryException $exception) {
    $result = ['ok' => false, 'exception' => $exception::class, 'deadlock' => ($exception->errorInfo[0] ?? null) === '40P01'];
}

fwrite(STDOUT, 'RESULT:'.json_encode($result)."\n");
