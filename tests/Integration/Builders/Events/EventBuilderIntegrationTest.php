<?php

declare(strict_types=1);

use App\Models\Events\Event;
use Illuminate\Support\Facades\Date;

describe('event timing queries', function () {
    beforeEach(function () {
        // Arrange
        $this->freezeSecond();
        $this->scheduledEvent = Event::factory()->create(['date' => Date::now()->addSecond()]);
        $this->startingEvent = Event::factory()->create(['date' => Date::now()]);
        $this->unscheduledEvent = Event::factory()->unscheduled()->create();
        $this->pastEvent = Event::factory()->create(['date' => Date::now()->subSecond()]);
        Event::factory()->trashed()->create(['date' => Date::now()->addSecond()]);
        Event::factory()->trashed()->create(['date' => Date::now()]);
        Event::factory()->trashed()->create(['date' => Date::now()->subSecond()]);
        Event::factory()->unscheduled()->trashed()->create();
    });

    describe('event timing scopes', function () {
        test('scheduled events include the current second and future events only', function () {
            // Act
            $query = Event::query();
            $query->scheduled();
            $query->orderBy('id');
            $scheduledEvents = $query->get();

            // Assert
            expect($scheduledEvents->modelKeys())->toBe([
                $this->scheduledEvent->id,
                $this->startingEvent->id,
            ]);
        });

        test('unscheduled events can be retrieved', function () {
            // Act
            $query = Event::query();
            $query->unscheduled();
            $unscheduledEvents = $query->get();

            // Assert
            expect($unscheduledEvents->modelKeys())->toBe([$this->unscheduledEvent->id]);
        });

        test('past events exclude the current second and future events', function () {
            // Act
            $query = Event::query();
            $query->past();
            $pastEvents = $query->get();

            // Assert
            expect($pastEvents->modelKeys())->toBe([$this->pastEvent->id]);
        });
    });
});
