<?php

declare(strict_types=1);

use App\Actions\Events\CreateAction;
use App\Data\Events\EventData;
use App\Models\Events\Event;
use App\Models\Events\Venue;

test('it creates a scheduled event with its venue and preview', function (): void {
    $venue = Venue::factory()->create();
    $date = now()->addWeek()->setTime(19, 0);
    $data = new EventData('Summer Slam', $date, $venue, 'A major event.');

    $event = resolve(CreateAction::class)->handle($data);

    expect($event)
        ->toBeInstanceOf(Event::class)
        ->name->toBe('Summer Slam')
        ->preview->toBe('A major event.')
        ->venue_id->toBe($venue->id)
        ->and($event->date?->toDateTimeString())->toBe($date->toDateTimeString());
});
