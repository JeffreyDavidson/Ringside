<?php

declare(strict_types=1);

require_once __DIR__.'/../../../Fixtures/live-show.php';

use App\Actions\Events\MarkLiveAttendanceAction;
use App\Enums\Events\LiveAttendanceMark;
use App\Exceptions\Events\LiveShowException;
use Spatie\Activitylog\Models\Activity;

test('briefing requires arrival and foreign person keys never change attendance', function (string $key, LiveAttendanceMark $mark): void {
    // Arrange
    $fixture = liveShowFixture();
    $key = $key === 'owned' ? $fixture['person'] : $key;
    $before = $fixture['run']->content;

    // Act / Assert
    expect(fn () => resolve(MarkLiveAttendanceAction::class)->handle($fixture['event'], 1, $key, $mark, true, signedInUser()))->toThrow(LiveShowException::class);
    expect($fixture['run']->refresh()->content)->toBe($before)->and($fixture['run']->version)->toBe(1);
})->with([
    'briefed before arrived' => ['owned', LiveAttendanceMark::Briefed],
    'foreign key' => ['wrestler:999999', LiveAttendanceMark::Arrived],
]);

test('clearing an arrival clears briefing and retains the actor and before-after audit', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $action = resolve(MarkLiveAttendanceAction::class);
    $action->handle($fixture['event'], 1, $fixture['person'], LiveAttendanceMark::Arrived, true, signedInUser());
    $action->handle($fixture['event'], 2, $fixture['person'], LiveAttendanceMark::Briefed, true, signedInUser());

    // Act
    $action->handle($fixture['event'], 3, $fixture['person'], LiveAttendanceMark::Arrived, false, signedInUser());

    // Assert
    $person = $fixture['run']->refresh()->content['roster'][0];
    expect($person['arrived'])->toBeNull()->and($person['briefed'])->toBeNull()->and($person['updated_by'])->toBe(auth()->id());
    $audit = Activity::query()->where('log_name', 'live-show')->orderByDesc('id')->firstOrFail();
    expect($audit->causer_id)->toBe(auth()->id())
        ->and($audit->subject_id)->toBe($fixture['event']->id)
        ->and(data_get($audit->properties, 'before.briefed'))->not->toBeNull()
        ->and(data_get($audit->properties, 'after.briefed'))->toBeNull();
});

test('a future prepared show cannot mark attendance early', function (): void {
    // Arrange
    $fixture = liveShowFixture(date: now()->addDay()->toIso8601String());

    // Act / Assert
    expect(fn () => resolve(MarkLiveAttendanceAction::class)->handle($fixture['event'], 1, $fixture['person'], LiveAttendanceMark::Arrived, true, signedInUser()))->toThrow(LiveShowException::class);
    expect($fixture['run']->refresh()->version)->toBe(1);
});

test('rescheduling a prepared event moves its attendance day guard', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $fixture['event']->update(['date' => now()->addDay()]);

    // Act / Assert
    expect(fn () => resolve(MarkLiveAttendanceAction::class)->handle($fixture['event'], 1, $fixture['person'], LiveAttendanceMark::Arrived, true, signedInUser()))->toThrow(LiveShowException::class);
    expect($fixture['run']->refresh()->version)->toBe(1);
});

test('an attendance mark records the actor passed in, not the signed-in user', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $actor = administrator();

    // Act
    resolve(MarkLiveAttendanceAction::class)->handle($fixture['event'], 1, $fixture['person'], LiveAttendanceMark::Arrived, true, $actor);

    // Assert
    expect($fixture['run']->refresh()->content['roster'][0]['updated_by'])->toBe($actor->id);
});

test('an attendance mark audit names the actor passed in as its causer', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $actor = administrator();

    // Act
    resolve(MarkLiveAttendanceAction::class)->handle($fixture['event'], 1, $fixture['person'], LiveAttendanceMark::Arrived, true, $actor);

    // Assert
    $audit = Activity::query()->where('log_name', 'live-show')->latest('id')->firstOrFail();
    expect($audit->causer_id)->toBe($actor->id)
        ->and($audit->causer_type)->toBe($actor->getMorphClass());
});
