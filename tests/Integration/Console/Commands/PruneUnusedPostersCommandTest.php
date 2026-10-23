<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('event-posters/orphan.png', 'png');
    touch(Storage::disk('local')->path('event-posters/orphan.png'), now()->subDays(3)->getTimestamp());
});

test('the command deletes unused old posters and logs the count', function (): void {
    // Arrange
    Log::shouldReceive('info')
        ->once()
        ->with('Pruned unused event posters.', ['count' => 1]);

    // Act
    $exitCode = Artisan::call('events:prune-posters');

    // Assert
    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain('Deleted 1 unused poster file(s).')
        ->and(Storage::disk('local')->exists('event-posters/orphan.png'))->toBeFalse();
});

test('a dry run lists the files it would delete and deletes nothing', function (): void {
    // Arrange
    Log::shouldReceive('info')->never();

    // Act
    $exitCode = Artisan::call('events:prune-posters', ['--dry-run' => true]);
    $output = Artisan::output();

    // Assert
    expect($exitCode)->toBe(0)
        ->and($output)->toContain('event-posters/orphan.png')
        ->and($output)->toContain('Dry run: 1 unused poster file(s) would be deleted.')
        ->and(Storage::disk('local')->exists('event-posters/orphan.png'))->toBeTrue();
});

test('the unused posters are pruned daily', function (): void {
    // Arrange
    $events = collect(resolve(Schedule::class)->events());

    // Act
    $prune = $events->first(fn (Event $event): bool => str_contains($event->command ?? '', 'events:prune-posters'));

    // Assert
    expect($prune)->toBeInstanceOf(Event::class)
        ->and($prune?->expression)->toBe('0 0 * * *');
});
