<?php

declare(strict_types=1);
use App\Actions\Events\RecordBudgetMovementAction;
use App\Data\Events\BudgetMovementData;
use App\Exceptions\Events\BudgetException;
use App\Exceptions\RecordChangedException;
use App\Models\Events\Event;
use App\Models\Events\EventBudget;
use App\Models\Events\EventBudgetLine;
use App\Models\Events\EventBudgetMovement;
use App\Models\Users\User;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

require_once __DIR__.'/../../Fixtures/budget.php';

/** @param list<array<string, mixed>> $specs
 * @return list<array{ok: bool, exception: ?string}>
 */
function runBudgetWorkers(array $specs): array
{
    $processes = [];
    $inputs = [];

    foreach ($specs as $i => $spec) {
        $inputs[$i] = new InputStream;
        $processes[$i] = new Process([PHP_BINARY, __DIR__.'/budget-worker.php', json_encode($spec, JSON_THROW_ON_ERROR)]);
        $processes[$i]->setTimeout(60);
        $processes[$i]->setInput($inputs[$i]);
        $processes[$i]->start();
    }

    try {
        foreach ($processes as $process) {
            $deadline = microtime(true) + 15;

            while (! str_contains($process->getOutput(), "READY\n") && $process->isRunning()) {
                if (microtime(true) > $deadline) {
                    throw new RuntimeException('Budget worker did not reach the release barrier.');
                }
                usleep(10000);
            }

            if (! str_contains($process->getOutput(), "READY\n")) {
                throw new RuntimeException($process->getErrorOutput());
            }
        }
        $start = microtime(true) + 0.2;

        foreach ($inputs as $input) {
            $input->write("{$start}\n");
            $input->close();
        }

        while (microtime(true) < $start) {
            foreach ($processes as $process) {
                $process->isRunning();
            } usleep(10000);
        }
        $results = [];

        foreach ($processes as $process) {
            $process->wait();
            expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());
            $result = json_decode(trim(substr($process->getOutput(), strpos($process->getOutput(), 'RESULT ') + 7)), true, flags: JSON_THROW_ON_ERROR);

            if (! is_array($result) || ! is_bool($result['ok'] ?? null)) {
                throw new UnexpectedValueException('The budget worker returned an invalid result.');
            }
            $results[] = ['ok' => $result['ok'], 'exception' => is_string($result['exception'] ?? null) ? $result['exception'] : null];
        }

        return $results;
    } finally {
        foreach ($processes as $process) {
            if ($process->isRunning()) {
                $process->stop();
            }
        }
    }
}

test('concurrent first saves keep one budget and exact retries keep one line', function (bool $sameKey): void {
    withCommittedData(function () use ($sameKey): void {
        // Arrange
        $event = Event::factory()->create();
        $user = administrator();
        $key = budgetKey();
        $spec = ['event_id' => $event->id, 'user_id' => $user->id, 'operation' => 'line', 'version' => 0, 'key' => $key, 'input' => budgetLineInput(), 'clock' => now()->toIso8601String()];
        // Act
        $results = runBudgetWorkers([$spec, [...$spec, 'key' => $sameKey ? $key : budgetKey()]]);
        // Assert
        expect(collect($results)->where('ok', true)->count())->toBe($sameKey ? 2 : 1)
            ->and(EventBudget::query()->where('event_id', $event->id)->count())->toBe(1)
            ->and(EventBudgetLine::query()->count())->toBe(1)
            ->and(EventBudget::query()->firstOrFail()->revisions()->count())->toBe(1);

        if (! $sameKey) {
            expect(collect($results)->where('ok', false)->pluck('exception')->all())->toBe([RecordChangedException::class]);
        }
    });
})->with([true, false])->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');

test('native payment and refund races keep one financial write per version or retry identity', function (string $case): void {
    withCommittedData(function () use ($case): void {
        // Arrange
        $f = budgetFixture();
        $user = User::query()->findOrFail(auth()->id());
        $input = budgetPayment();
        $key = budgetKey();

        if ($case === 'refund') {
            $source = EventBudgetMovement::factory()->for($f['line'], 'line')->create();
            $input = budgetPayment(['kind' => 'refund', 'source_id' => $source->id, 'amount' => '150.00']);
        }
        $spec = ['event_id' => $f['event']->id, 'user_id' => $user->id, 'operation' => 'movement', 'line_id' => $f['line']->id, 'version' => 0, 'key' => $key, 'input' => $input, 'clock' => now()->toIso8601String()];
        // Act
        $results = runBudgetWorkers([$spec, [...$spec, 'key' => $case === 'retry' ? $key : budgetKey()]]);
        // Assert
        expect(collect($results)->where('ok', true)->count())->toBe($case === 'retry' ? 2 : 1)
            ->and($f['line']->movements()->count())->toBe($case === 'refund' ? 2 : 1)
            ->and($f['budget']->revisions()->count())->toBe(1);

        if ($case !== 'retry') {
            expect(collect($results)->where('ok', false)->pluck('exception')->all())->toBe([RecordChangedException::class]);
        }

        if ($case === 'refund') {
            expect(fn () => resolve(RecordBudgetMovementAction::class)->handle($f['event'], 1, budgetKey(), $f['line']->id, BudgetMovementData::fromValidated($input), signedInUser()))->toThrow(BudgetException::class);
        }
    });
})->with(['retry', 'distinct', 'refund'])->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');

test('a payment racing an obligation change archive or deletion never writes after a conflicting state', function (string $case): void {
    withCommittedData(function () use ($case): void {
        // Arrange
        $f = budgetFixture(['confirmed_cents' => $case === 'archive' ? null : 50000]);
        $user = User::query()->findOrFail(auth()->id());
        $spec = ['event_id' => $f['event']->id, 'user_id' => $user->id, 'operation' => 'movement', 'line_id' => $f['line']->id, 'version' => 0, 'key' => budgetKey(), 'input' => budgetPayment(), 'clock' => now()->toIso8601String()];
        $opposite = [...$spec, 'key' => budgetKey(), 'operation' => $case, 'input' => budgetLineInput(['confirmed' => '50.00', 'reason' => 'Agreed reduction', 'acknowledge' => true])];
        // Act
        $results = runBudgetWorkers([$spec, $opposite]);

        // Assert
        if ($case === 'delete') {
            expect(Event::query()->withTrashed()->findOrFail($f['event']->id)->trashed())->toBeTrue()
                ->and($f['budget']->revisions()->count())->toBe($f['line']->movements()->count());

            return;
        }
        expect(collect($results)->where('ok', true)->count())->toBe(1)->and($f['budget']->revisions()->count())->toBe(1);

        if ($case === 'archive' && $f['line']->refresh()->archived_at !== null) {
            expect($f['line']->movements()->count())->toBe(0);
        }
    });
})->with(['line', 'archive', 'delete'])->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency', 'postgres-concurrency');
