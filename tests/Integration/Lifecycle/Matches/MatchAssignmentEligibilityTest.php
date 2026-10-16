<?php

declare(strict_types=1);

use App\Lifecycle\Matches\MatchAssignmentEligibility;
use App\Models\Events\Event;

test('it returns without checking assignments when an event has no target date', function (): void {
    $event = Event::factory()->unscheduled()->create();

    expect(fn () => resolve(MatchAssignmentEligibility::class)
        ->ensureEventCanBeRescheduled($event, null))
        ->not->toThrow(Throwable::class);
});
