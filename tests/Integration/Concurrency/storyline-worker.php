<?php

declare(strict_types=1);
use App\Actions\Storylines\RecordDevelopmentAction;
use App\Actions\Storylines\SaveStepAction;
use App\Data\Storylines\DevelopmentData;
use App\Data\Storylines\SourceData;
use App\Data\Storylines\StepData;
use App\Exceptions\BaseBusinessException;
use App\Models\Storylines\Storyline;
use App\Models\Users\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;

require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$arguments = $_SERVER['argv'] ?? [];
$payload = is_array($arguments) ? ($arguments[1] ?? null) : null;
if (! is_string($payload)) {
    throw new InvalidArgumentException('Missing test-worker specification.');
}
/** @var array{user: int, story: int, event: int, version: int, submission: string, clock: string, operation: string} $spec */
$spec = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
$actor = User::query()->findOrFail($spec['user']);
auth()->login($actor);
Carbon::setTestNow($spec['clock']);
$story = Storyline::query()->findOrFail($spec['story']);
fwrite(STDOUT, "READY\n");
$start = (float) fgets(STDIN);
while (microtime(true) < $start) {
    usleep(1000);
}
try {
    $source = new SourceData($spec['event']);
    if ($spec['operation'] === 'development') {
        resolve(RecordDevelopmentAction::class)->handle($story, $spec['version'], new DevelopmentData('Concurrent outcome', 'The argument happened.', today()->toDateString(), 1, [], $source, $spec['submission']), $actor);
    } else {
        resolve(SaveStepAction::class)->handle($story, $spec['version'], new StepData('Concurrent plan', 'The planned argument.', 1, $source, $spec['submission']), $actor);
    }
    fwrite(STDOUT, 'RESULT:'.json_encode(['ok' => true], JSON_THROW_ON_ERROR)."\n");
} catch (BaseBusinessException $exception) {
    fwrite(STDOUT, 'RESULT:'.json_encode(['ok' => false, 'exception' => $exception::class, 'message' => $exception->getMessage()], JSON_THROW_ON_ERROR)."\n");
}
