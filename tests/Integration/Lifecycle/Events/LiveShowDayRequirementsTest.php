<?php

declare(strict_types=1);

use App\Enums\Events\LiveShowStatus;
use App\Exceptions\Events\LiveShowException;
use App\Lifecycle\Events\LiveShowDayRequirements;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;

describe('live show day requirements', function (): void {
    it('allows a prepared show on the event day', function (): void {
        // Arrange
        $run = EventLiveRun::factory()
            ->for(Event::factory()->state(['date' => now()]))
            ->create();

        // Act
        $ensure = fn () => resolve(LiveShowDayRequirements::class)->ensureShowDay($run);

        // Assert
        expect($ensure)->not->toThrow(Throwable::class);
    });

    it('refuses a prepared show on another day', function (): void {
        // Arrange
        $run = EventLiveRun::factory()
            ->for(Event::factory()->state(['date' => now()->addDays(2)]))
            ->create();

        // Act
        $ensure = fn () => resolve(LiveShowDayRequirements::class)->ensureShowDay($run);

        // Assert
        expect($ensure)->toThrow(LiveShowException::class, __('live-show.show_day'));
    });

    it('lets a running show continue on another day', function (): void {
        // Arrange
        $run = EventLiveRun::factory()
            ->for(Event::factory()->state(['date' => now()->subDays(2)]))
            ->create(['status' => LiveShowStatus::Running]);

        // Act
        $ensure = fn () => resolve(LiveShowDayRequirements::class)->ensureShowDay($run);

        // Assert
        expect($ensure)->not->toThrow(Throwable::class);
    });
});
