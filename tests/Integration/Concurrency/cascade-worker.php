<?php

declare(strict_types=1);

/*
 * Child process for CascadeLockOrderConcurrencyTest: runs one lifecycle cascade or result recording.
 *
 * Usage: php cascade-worker.php '<json spec>'
 *
 * The spec names the action and its subject. Optional planner switches make PostgreSQL read joined and scanned rows in
 * physical (pivot or insertion) order, which is what an unordered query returns on tables large enough for the
 * planner to prefer a nested loop or a sequential scan. It prints a single RESULT line as JSON.
 */

use App\Actions\Matches\RecordResultAction;
use App\Actions\TagTeams\CreateAction as CreateTagTeamAction;
use App\Actions\TagTeams\RetireAction as RetireTagTeamAction;
use App\Actions\TagTeams\UpdateAction as UpdateTagTeamAction;
use App\Actions\Wrestlers\RetireAction as RetireWrestlerAction;
use App\Data\Matches\MatchResultData;
use App\Data\TagTeams\TagTeamData;
use App\Enums\MatchFinish;
use App\Exceptions\BaseBusinessException;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchSide;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 3).'/vendor/autoload.php';
require_once __DIR__.'/worker-support.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$arguments = $_SERVER['argv'] ?? [];
$payload = is_array($arguments) ? ($arguments[1] ?? null) : null;

if (! is_string($payload)) {
    fwrite(STDERR, "Missing cascade spec.\n");
    exit(1);
}

/** @var array{action: string, id: int, name?: string, wrestler_ids?: array<int, int>, nested_loop_joins?: bool, sequential_scans?: bool, winning_position?: int} $spec */
$spec = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

$planner = array_merge(
    ($spec['nested_loop_joins'] ?? false) ? ['enable_hashjoin', 'enable_mergejoin'] : [],
    ($spec['sequential_scans'] ?? false) ? ['enable_indexscan', 'enable_bitmapscan', 'enable_indexonlyscan'] : [],
);

// The planner switches are PostgreSQL settings; other engines ignore the spec flags.
if (DB::connection()->getDriverName() === 'pgsql') {
    foreach ($planner as $setting) {
        DB::statement("set {$setting} = off");
    }
}

$tagTeamData = fn (): TagTeamData => new TagTeamData(
    name: $spec['name'] ?? 'Worker Team',
    signature_move: null,
    employment_date: null,
    wrestlerA: Wrestler::query()->findOrFail($spec['wrestler_ids'][0] ?? 0),
    wrestlerB: Wrestler::query()->findOrFail($spec['wrestler_ids'][1] ?? 0),
);

$result = ['ok' => true, 'exception' => null, 'deadlock' => false];

try {
    match ($spec['action']) {
        'create_tag_team' => resolve(CreateTagTeamAction::class)->handle($tagTeamData()),
        'update_tag_team' => resolve(UpdateTagTeamAction::class)->handle(TagTeam::query()->findOrFail($spec['id']), $tagTeamData()),
        'retire_tag_team' => resolve(RetireTagTeamAction::class)->handle(TagTeam::query()->findOrFail($spec['id'])),
        'retire_wrestler' => resolve(RetireWrestlerAction::class)->handle(Wrestler::query()->findOrFail($spec['id'])),
        'record_result' => resolve(RecordResultAction::class)->handle(
            EventMatch::query()->findOrFail($spec['id']),
            new MatchResultData(
                MatchFinish::Pinfall,
                MatchSide::query()->where('match_id', $spec['id'])->where('position', $spec['winning_position'] ?? 1)->firstOrFail(),
                collect(),
            ),
        ),
        default => throw new InvalidArgumentException("Unknown cascade action {$spec['action']}."),
    };
} catch (BaseBusinessException $exception) {
    $result = ['ok' => false, 'exception' => $exception::class, 'deadlock' => false];
} catch (DeadlockException|QueryException $exception) {
    $result = ['ok' => false, 'exception' => $exception::class, 'deadlock' => isDeadlock($exception)];
}

fwrite(STDOUT, 'RESULT:'.json_encode($result)."\n");
