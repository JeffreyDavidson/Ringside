<?php

declare(strict_types=1);

use App\Builders\Events\EventBuilder;
use App\Models\Events\Event;

/**
 * Integration tests for EventQueryBuilder query scopes and methods.
 *
 * INTEGRATION TEST SCOPE:
 * - Builder class structure and scope functionality
 * - Event timing filtering scopes (scheduled, unscheduled, past)
 * - Query scope accuracy and entity isolation
 *
 * These tests verify that the EventQueryBuilder correctly implements
 * all query scopes for filtering events by their scheduling status.
 *
 * @see EventBuilder
 */
describe('EventQueryBuilder Integration Tests', function () {
    beforeEach(function () {
        // Create events in all possible states for comprehensive scope testing
        $this->scheduledEvent = Event::factory()->scheduled()->create();
        $this->unscheduledEvent = Event::factory()->unscheduled()->create();
        $this->pastEvent = Event::factory()->past()->create();
    });

    describe('event timing scopes', function () {
        test('scheduled events can be retrieved', function () {
            // Act
            $scheduledEvents = Event::scheduled()->get();

            // Assert
            expect($scheduledEvents->pluck('id'))
                ->toContain($this->scheduledEvent->id)
                ->not->toContain($this->unscheduledEvent->id)
                ->not->toContain($this->pastEvent->id);
        });

        test('unscheduled events can be retrieved', function () {
            // Act
            $unscheduledEvents = Event::unscheduled()->get();

            // Assert
            expect($unscheduledEvents->pluck('id'))
                ->toContain($this->unscheduledEvent->id)
                ->not->toContain($this->scheduledEvent->id)
                ->not->toContain($this->pastEvent->id);
        });

        test('past events can be retrieved', function () {
            // Act
            $pastEvents = Event::past()->get();

            // Assert
            expect($pastEvents->pluck('id'))
                ->toContain($this->pastEvent->id)
                ->not->toContain($this->scheduledEvent->id)
                ->not->toContain($this->unscheduledEvent->id);
        });
    });
});
