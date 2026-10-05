<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

test('the activity log is pruned daily without asking for confirmation', function (): void {
    // Arrange
    $events = collect(resolve(Schedule::class)->events());

    // Act
    $cleanup = $events->first(fn (Event $event): bool => str_contains($event->command ?? '', 'activitylog:clean'));

    // Assert
    expect($cleanup)->toBeInstanceOf(Event::class)
        ->and($cleanup?->expression)->toBe('0 0 * * *')
        ->and($cleanup?->command)->toContain('--force');
});
