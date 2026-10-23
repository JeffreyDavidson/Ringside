<?php

declare(strict_types=1);
use App\Actions\Events\ArchiveBudgetLineAction;
use App\Actions\Events\DeleteAction;
use App\Actions\Events\RecordBudgetMovementAction;
use App\Actions\Events\SaveBudgetLineAction;
use App\Data\Events\BudgetLineData;
use App\Data\Events\BudgetMovementData;
use App\Exceptions\BaseBusinessException;
use App\Models\Events\Event;
use App\Models\Users\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$arguments = $_SERVER['argv'] ?? [];
$payload = is_array($arguments) ? ($arguments[1] ?? null) : null;

if (! is_string($payload)) {
    throw new InvalidArgumentException('Missing budget spec.');
}
/** @var array{event_id: int,user_id: int,operation: string,line_id?: int,version: int,key: string,input: array{direction: string, category: string, description: string, counterparty: string, planned?: ?string, confirmed?: ?string, due_date?: ?string, notes?: ?string, reason?: ?string, kind: string, amount: string, effective_date: string, method: string, reference?: ?string, source_id?: ?int, acknowledge?: bool},clock: string} $spec */
$spec = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
$actor = User::query()->findOrFail($spec['user_id']);
auth()->login($actor);
Carbon::setTestNow($spec['clock']);
DB::select('select 1');
fwrite(STDOUT, "READY\n");
$start = (float) fgets(STDIN);

while (microtime(true) < $start) {
    usleep(1000);
}

try {
    $event = Event::query()->findOrFail($spec['event_id']);
    match ($spec['operation']) {
        'line' => resolve(SaveBudgetLineAction::class)->handle($event, $spec['version'], $spec['key'], $spec['line_id'] ?? null, BudgetLineData::fromValidated($spec['input']), $actor),
        'movement' => resolve(RecordBudgetMovementAction::class)->handle($event, $spec['version'], $spec['key'], $spec['line_id'] ?? 0, BudgetMovementData::fromValidated($spec['input']), $actor),
        'archive' => resolve(ArchiveBudgetLineAction::class)->handle($event, $spec['version'], $spec['key'], $spec['line_id'] ?? 0, 'No longer needed', $actor),
        'delete' => resolve(DeleteAction::class)->handle($event),
        default => throw new InvalidArgumentException('Unknown budget worker operation'),
    };
    $result = ['ok' => true, 'exception' => null];
} catch (BaseBusinessException|QueryException|ModelNotFoundException|AuthorizationException $exception) {
    $result = ['ok' => false, 'exception' => $exception::class];
}
fwrite(STDOUT, 'RESULT '.json_encode($result, JSON_THROW_ON_ERROR)."\n");
