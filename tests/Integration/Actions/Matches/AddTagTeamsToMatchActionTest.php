<?php

declare(strict_types=1);

use App\Actions\Matches\AddTagTeamsToMatchAction;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\TagTeams\TagTeam;

test('it adds a tag team to a match as a polymorphic competitor', function () {
    $match = EventMatch::factory()->create();
    $tagTeam = TagTeam::factory()->bookable()->create();

    resolve(AddTagTeamsToMatchAction::class)->handle($match, collect([$tagTeam]), 1);

    $this->assertDatabaseHas('events_matches_competitors', [
        'match_id' => $match->id,
        'competitor_id' => $tagTeam->id,
        'competitor_type' => TagTeam::class,
        'side_number' => 1,
    ]);
});

test('it rejects match assignment when no tag team is available', function () {
    $match = EventMatch::factory()->create();
    $tagTeams = TagTeam::factory()->retired()->count(2)->create();

    expect(fn () => resolve(AddTagTeamsToMatchAction::class)->handle($match, $tagTeams, 1))
        ->toThrow(EntityNotAvailableException::class, 'No eligible tag teams were provided for match assignment.');
});

test('it rejects a tag team already booked on the event card', function () {
    $event = Event::factory()->scheduled()->create();
    $existingMatch = EventMatch::factory()->forEvent($event)->create();
    $targetMatch = EventMatch::factory()->forEvent($event)->create();
    $tagTeam = TagTeam::factory()->bookable()->create();

    $existingMatch->competitors()->create([
        'competitor_id' => $tagTeam->id,
        'competitor_type' => TagTeam::class,
        'side_number' => 1,
    ]);

    expect(fn () => resolve(AddTagTeamsToMatchAction::class)->handle($targetMatch, collect([$tagTeam]), 1))
        ->toThrow(SchedulingConflictException::class, "Tag team [{$tagTeam->name}] is already booked at this event time.");

    expect($targetMatch->competitors()->count())->toBe(0);
});

test('it assigns a repeated tag team only once', function () {
    $match = EventMatch::factory()->create();
    $tagTeam = TagTeam::factory()->bookable()->create();

    resolve(AddTagTeamsToMatchAction::class)->handle($match, collect([$tagTeam, $tagTeam]), 1);

    expect($match->competitors()->count())->toBe(1);
});
