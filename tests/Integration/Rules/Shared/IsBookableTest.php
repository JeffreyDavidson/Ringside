<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Rules\Shared\IsBookable;
use Illuminate\Support\Facades\Validator;

test('booking validation reads employment on the event date', function (string $model, int $eventDaysAhead, bool $passes): void {
    // Arrange
    $person = $model::factory()->bookable()->create();
    $person->employments()->update(['started_at' => now()->subDays(10)]);
    $event = Event::factory()->create(['date' => now()->addDays($eventDaysAhead)]);

    // Act
    $validator = Validator::make(['person' => $person->id], ['person' => [resolve(IsBookable::class, ['model' => $model, 'event' => $event])]]);

    // Assert
    expect($validator->passes())->toBe($passes);
})->with([
    'a wrestler on a back-filled event' => [Wrestler::class, -5, true],
    'a wrestler on an event before the start' => [Wrestler::class, -20, false],
    'a referee on a back-filled event' => [Referee::class, -5, true],
    'a referee on an event before the start' => [Referee::class, -20, false],
]);

test('booking validation reads a tag team employment on the event date', function (int $eventDaysAhead, bool $passes): void {
    // Arrange
    $tagTeam = TagTeam::factory()->bookable()->create();
    $tagTeam->employments()->update(['started_at' => now()->subDays(10)]);
    $tagTeam->currentWrestlers->each(fn (Wrestler $member): int => $member->employments()->update(['started_at' => now()->subDays(10)]));
    $event = Event::factory()->create(['date' => now()->addDays($eventDaysAhead)]);

    // Act
    $validator = Validator::make(['person' => $tagTeam->id], ['person' => [new IsBookable(TagTeam::class, $event)]]);

    // Assert
    expect($validator->passes())->toBe($passes);
})->with([
    'a tag team on a back-filled event' => [-5, true],
    'a tag team on an event before the start' => [-20, false],
]);

it('fails with the not bookable message for a missing record or a value that is not an id', function (string $model, string $message, mixed $value): void {
    // Arrange
    $validator = Validator::make(['person' => $value], ['person' => [resolve(IsBookable::class, ['model' => $model])]]);

    // Act
    $error = $validator->errors()->first('person');

    // Assert
    expect($error)->toBe(__($message));
})->with([
    'a wrestler that does not exist' => [Wrestler::class, 'wrestlers.validation.not_bookable', PHP_INT_MAX],
    'a referee that does not exist' => [Referee::class, 'referees.validation.not_bookable', PHP_INT_MAX],
    'a tag team that does not exist' => [TagTeam::class, 'tag-teams.validation.not_bookable', PHP_INT_MAX],
    'a wrestler id sent as an array' => [Wrestler::class, 'wrestlers.validation.not_bookable', [1]],
    'a referee id sent as a boolean' => [Referee::class, 'referees.validation.not_bookable', true],
    'a tag team id sent as a float' => [TagTeam::class, 'tag-teams.validation.not_bookable', 1.5],
]);
