<?php

declare(strict_types=1);

use Illuminate\Bus\Batch;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

test('the queue tables have Laravel\'s standard columns', function (string $table, array $columns) {
    // Act
    $hasColumns = Schema::hasColumns($table, $columns);

    // Assert
    expect($hasColumns)->toBeTrue();
})->with([
    'jobs' => ['jobs', ['id', 'queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at']],
    'failed jobs' => ['failed_jobs', ['id', 'uuid', 'connection', 'queue', 'payload', 'exception', 'failed_at']],
    'job batches' => ['job_batches', ['id', 'name', 'total_jobs', 'pending_jobs', 'failed_jobs', 'failed_job_ids', 'options', 'cancelled_at', 'created_at', 'finished_at']],
]);

test('a failed job is recorded instead of throwing', function () {
    // Arrange
    $failer = resolve(FailedJobProviderInterface::class);
    $uuid = (string) Str::uuid();
    $payload = json_encode(['uuid' => $uuid, 'displayName' => 'ProbeJob'], JSON_THROW_ON_ERROR);

    // Act
    $failer->log('database', 'default', $payload, new RuntimeException('Probe failure'));

    // Assert
    $recorded = $failer->find($uuid);
    expect($recorded)->not->toBeNull()
        ->and($recorded->queue)->toBe('default')
        ->and($recorded->exception)->toContain('Probe failure');
});

test('a job batch is stored and can be found again', function () {
    // Act
    $batch = Bus::batch([])
        ->name('Probe batch')
        ->dispatch();

    // Assert
    $found = Bus::findBatch($batch->id);
    expect($found)->toBeInstanceOf(Batch::class)
        ->and($found?->name)->toBe('Probe batch');
});
