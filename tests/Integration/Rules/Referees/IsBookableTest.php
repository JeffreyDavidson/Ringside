<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Roster\Referees\Referee;
use App\Rules\Referees\IsBookable;
use Illuminate\Support\Facades\Validator;

test('booking validation reads employment on the event date', function (int $eventDaysAhead, bool $passes): void {
    // Arrange
    $person = Referee::factory()->bookable()->create();
    $person->employments()->update(['started_at' => now()->subDays(10)]);
    $event = Event::factory()->create(['date' => now()->addDays($eventDaysAhead)]);

    // Act
    $validator = Validator::make(['person' => $person->id], ['person' => [new IsBookable($event)]]);

    // Assert
    expect($validator->passes())->toBe($passes);
})->with([
    'a referee on a back-filled event' => [-5, true],
    'a referee on an event before the start' => [-20, false],
]);
