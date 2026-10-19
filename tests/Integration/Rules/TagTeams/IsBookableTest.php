<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Rules\TagTeams\IsBookable;
use Illuminate\Support\Facades\Validator;

test('booking validation reads employment on the event date', function (int $eventDaysAhead, bool $passes): void {
    // Arrange
    $person = TagTeam::factory()->bookable()->create();
    $person->employments()->update(['started_at' => now()->subDays(10)]);
    $person->currentWrestlers->each(fn (Wrestler $member): int => $member->employments()->update(['started_at' => now()->subDays(10)]));
    $event = Event::factory()->create(['date' => now()->addDays($eventDaysAhead)]);

    // Act
    $validator = Validator::make(['person' => $person->id], ['person' => [new IsBookable($event)]]);

    // Assert
    expect($validator->passes())->toBe($passes);
})->with([
    'a tag team on a back-filled event' => [-5, true],
    'a tag team on an event before the start' => [-20, false],
]);
