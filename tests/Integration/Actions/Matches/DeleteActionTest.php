<?php

declare(strict_types=1);

use App\Actions\Matches\DeleteAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Matches\CannotBeDeletedException;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Titles\TitleChampionship;

use function Pest\Laravel\assertNotSoftDeleted;
use function Pest\Laravel\assertSoftDeleted;

test('it soft deletes a match while preserving its historical records', function () {
    $eventMatch = EventMatch::factory()->complete()->withReferees()->create();
    $competitorIds = $eventMatch->competitors()->pluck('id');
    $sideIds = $eventMatch->sides()->pluck('id');
    $refereeIds = $eventMatch->referees()->pluck('referees.id');
    $matchFinish = $eventMatch->match_finish;
    $winningSideId = $eventMatch->winning_side_id;

    resolve(DeleteAction::class)->handle($eventMatch);

    assertSoftDeleted($eventMatch);

    expect(EventMatch::query()->find($eventMatch->id))->toBeNull()
        ->and(EventMatch::withTrashed()->find($eventMatch->id))->not->toBeNull()
        ->and($eventMatch->competitors()->pluck('id'))->toEqual($competitorIds)
        ->and($eventMatch->sides()->pluck('id'))->toEqual($sideIds)
        ->and($eventMatch->referees()->pluck('referees.id'))->toEqual($refereeIds)
        ->and($eventMatch->refresh()->match_finish)->toBe($matchFinish)
        ->and($eventMatch->winning_side_id)->toBe($winningSideId)
        ->and($eventMatch->lifecycleTransitions()->sole()->transition)->toBe(LifecycleTransitionType::Deleted)
        ->and(Referee::query()->whereKey($refereeIds)->count())->toBe($refereeIds->count());
});

test('it refuses to delete a match that won or lost a title and writes nothing', function (bool $closesReign): void {
    // Arrange
    $eventMatch = EventMatch::factory()->complete()->create();
    $reign = TitleChampionship::factory();
    $reign = $closesReign ? $reign->lostAtEventMatch($eventMatch) : $reign->wonAtEventMatch($eventMatch);
    $reign = $reign->create();

    // Act
    $delete = fn () => resolve(DeleteAction::class)->handle($eventMatch);

    // Assert
    expect($delete)->toThrow(CannotBeDeletedException::class, __('matches.errors.deleted.has_title_change'))
        ->and($eventMatch->lifecycleTransitions()->count())->toBe(0)
        ->and(TitleChampionship::query()->whereKey($reign->id)->where('won_match_id', $reign->won_match_id)->where('lost_match_id', $reign->lost_match_id)->exists())->toBeTrue();

    assertNotSoftDeleted($eventMatch);
})->with([
    'a match that won the title' => [false],
    'a match that lost the title' => [true],
]);

test('it deletes a match whose result changed no title even when another match of the event did', function (): void {
    // Arrange
    $eventMatch = EventMatch::factory()->complete()->create();
    $otherMatch = EventMatch::factory()->forEvent($eventMatch->event)->complete()->create();
    TitleChampionship::factory()->wonAtEventMatch($otherMatch)->create();

    // Act
    resolve(DeleteAction::class)->handle($eventMatch);

    // Assert
    assertSoftDeleted($eventMatch);
    assertNotSoftDeleted($otherMatch);
});

test('it deletes a match whose only reign was already deleted', function (): void {
    // Arrange
    $eventMatch = EventMatch::factory()->complete()->create();
    TitleChampionship::factory()->wonAtEventMatch($eventMatch)->create()->delete();

    // Act
    resolve(DeleteAction::class)->handle($eventMatch);

    // Assert
    assertSoftDeleted($eventMatch);
});
