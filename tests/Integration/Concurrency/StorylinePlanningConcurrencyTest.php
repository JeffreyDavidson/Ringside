<?php

declare(strict_types=1);
use App\Exceptions\RecordChangedException;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Storylines\Storyline;
use Illuminate\Support\Str;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

test('two real booker requests commit exactly one planned or actual development', function (string $operation) {
    withCommittedData(function () use ($operation): void {
        // Arrange
        $actor = administrator();
        $promotion = Promotion::factory()->create(['timezone' => 'UTC']);
        $story = Storyline::factory()->for($promotion)->create();
        $event = Event::factory()->for($promotion)->create(['date' => now()]);
        $spec = ['user' => $actor->id, 'story' => $story->id, 'event' => $event->id, 'version' => 1, 'submission' => (string) Str::uuid(), 'clock' => now()->toIso8601String(), 'operation' => $operation];
        $deadlocksBefore = resolvedDeadlocks();
        $processes = [];
        $inputs = [];

        foreach (range(1, 2) as $index) {
            $input = new InputStream;
            $process = new Process([PHP_BINARY, __DIR__.'/storyline-worker.php', json_encode($spec, JSON_THROW_ON_ERROR)]);
            $process->setTimeout(30);
            $process->setInput($input);
            $process->start();
            $processes[] = $process;
            $inputs[] = $input;
        }

        foreach ($processes as $process) {
            while (! str_contains($process->getOutput(), 'READY') && $process->isRunning()) {
                usleep(10000);
            }
        }

        // Act
        $start = microtime(true) + .2;

        foreach ($inputs as $input) {
            $input->write($start."\n");
            $input->close();
        }

        while (microtime(true) < $start) {
            foreach ($processes as $process) {
                $process->isRunning();
            } usleep(1000);
        }

        $results = [];

        foreach ($processes as $process) {
            $process->wait();
            preg_match('/RESULT:(.*)/', $process->getOutput(), $matches);
            $results[] = json_decode($matches[1] ?? '{}', true, flags: JSON_THROW_ON_ERROR);
        }

        // Assert
        expect(deadlocksResolvedSince($deadlocksBefore))->toBe(0)
            ->and(collect($results)->where('ok', true))->toHaveCount(1)
            ->and(collect($results)->where('ok', false)->pluck('exception')->all())->toBe([RecordChangedException::class])
            ->and($operation === 'step' ? $story->steps()->count() : $story->developments()->count())->toBe(1)
            ->and($story->revisions()->count())->toBe(1);
    });
})->with(['step', 'development'])->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)->group('concurrency');
