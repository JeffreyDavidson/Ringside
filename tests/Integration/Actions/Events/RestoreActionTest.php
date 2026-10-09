<?php

declare(strict_types=1);

use App\Actions\Events\DeleteAction;
use App\Actions\Events\RestoreAction;
use App\Actions\Venues\RestoreAction as RestoreVenueAction;
use App\Exceptions\Events\CannotBeRestoredException;
use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Lifecycle\Periods\DeletionStateManager;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Services\Promotions\PromotionContextService;

/**
 * Book a resource on a new match of the given event.
 */
function bookResourceOnEvent(Event $event, Wrestler|TagTeam|Referee|Title $resource): EventMatch
{
    $match = EventMatch::factory()->forEvent($event);

    return match (true) {
        $resource instanceof Wrestler, $resource instanceof TagTeam => $match->withCompetitors([$resource])->create(),
        $resource instanceof Referee => tap($match->create(), fn (EventMatch $created) => $created->referees()->attach($resource)),
        $resource instanceof Title => tap($match->create(), fn (EventMatch $created) => $created->titles()->attach($resource)),
    };
}

function deleteEvent(Event $event): Event
{
    resolve(DeleteAction::class)->handle($event);

    return Event::withTrashed()->findOrFail($event->id);
}

test('it restores a soft-deleted event', function (): void {
    $event = Event::factory()->scheduled()->withVenue()->create();
    resolve(DeleteAction::class)->handle($event);

    $deletedEvent = Event::withTrashed()->findOrFail($event->id);
    resolve(RestoreAction::class)->handle($deletedEvent);

    expect(Event::query()->find($event->id))->not->toBeNull()
        ->and(Event::withTrashed()->findOrFail($event->id)->deleted_at)->toBeNull();
});

test('it restores an event that has no venue', function (): void {
    $event = Event::factory()->scheduled()->create(['venue_id' => null]);
    $deletedEvent = deleteEvent($event);

    resolve(RestoreAction::class)->handle($deletedEvent);

    expect(Event::query()->find($event->id))->not->toBeNull();
});

test('it restores an event whose venue is live and free that day', function (): void {
    $venue = Venue::factory()->create();
    $event = Event::factory()->atVenue($venue)->scheduledOn(now()->addWeek()->toDateString())->create();
    $deletedEvent = deleteEvent($event);

    resolve(RestoreAction::class)->handle($deletedEvent);

    expect(Event::query()->find($event->id))->not->toBeNull();
});

test('it refuses to restore an event while its venue is deleted, so a restore cannot double-book the venue', function (): void {
    // Arrange: event B is deleted, event A takes its day at the venue, then the venue is deleted.
    $date = now()->addWeek();
    $venue = Venue::factory()->create(['name' => 'Madison Square Garden']);
    $eventB = Event::factory()->for($venue)->create(['date' => $date]);
    $deletedEventB = deleteEvent($eventB);
    $eventA = Event::factory()->for($venue)->create(['date' => $date]);
    resolve(DeletionStateManager::class)->delete($venue, now());

    // Act
    $act = fn () => resolve(RestoreAction::class)->handle($deletedEventB);

    // Assert
    expect($act)->toThrow(CannotBeRestoredException::class, "Event '{$eventB->name}' cannot be restored because its venue 'Madison Square Garden' is deleted. Restore the venue first.")
        ->and(Event::onlyTrashed()->whereKey($eventB->id)->exists())->toBeTrue();

    // The venue can still be restored, and it then holds only event A that day.
    resolve(RestoreVenueAction::class)->handle(Venue::withTrashed()->findOrFail($venue->id));

    expect($venue->events()->pluck('id')->all())->toBe([$eventA->id]);
});

describe('event restore scheduling conflicts', function (): void {
    test('it rejects restoring an event whose booked resource is booked in another event at the same time', function (Closure $createResource): void {
        // Arrange
        $resource = $createResource();
        $date = now()->addWeek();
        $event = Event::factory()->create(['date' => $date]);
        bookResourceOnEvent($event, $resource);
        $deletedEvent = deleteEvent($event);
        $otherEvent = Event::factory()->create(['date' => $date]);
        bookResourceOnEvent($otherEvent, $resource);

        // Act
        $act = fn () => resolve(RestoreAction::class)->handle($deletedEvent);

        // Assert
        expect($act)->toThrow(SchedulingConflictException::class)
            ->and(Event::onlyTrashed()->whereKey($event->id)->exists())->toBeTrue();
    })->with([
        'wrestler' => [fn () => Wrestler::factory()->bookable()->create()],
        'tag team' => [fn () => TagTeam::factory()->bookable()->create()],
        'referee' => [fn () => Referee::factory()->bookable()->create()],
        'title' => [fn () => Title::factory()->create()],
    ]);

    test('it rejects restoring an event under an enforced promotion context when a booked resource is booked in another event at the same time', function (Closure $createResource): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $resource = $createResource($promotion);
        $date = now()->addWeek();
        $event = Event::factory()->for($promotion, 'promotion')->create(['date' => $date]);
        bookResourceOnEvent($event, $resource);
        $deletedEvent = deleteEvent($event);
        $otherEvent = Event::factory()->for($promotion, 'promotion')->create(['date' => $date]);
        bookResourceOnEvent($otherEvent, $resource);
        resolve(PromotionContextService::class)->set($promotion);
        resolve(PromotionContextService::class)->enforce();

        // Act
        $act = fn () => resolve(RestoreAction::class)->handle($deletedEvent);

        // Assert
        expect($act)->toThrow(SchedulingConflictException::class)
            ->and(Event::onlyTrashed()->whereKey($event->id)->exists())->toBeTrue();
    })->with([
        'wrestler' => [fn (Promotion $promotion) => Wrestler::factory()->bookable()->for($promotion, 'promotion')->create()],
        'tag team' => [fn (Promotion $promotion) => TagTeam::factory()->bookable()->for($promotion, 'promotion')->create()],
        'referee' => [fn (Promotion $promotion) => Referee::factory()->bookable()->for($promotion, 'promotion')->create()],
        'title' => [fn (Promotion $promotion) => Title::factory()->for($promotion, 'promotion')->create()],
    ]);

    test('it restores an event whose resource is booked in another event at a different time', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->bookable()->create();
        $event = Event::factory()->create(['date' => now()->addWeek()]);
        bookResourceOnEvent($event, $wrestler);
        $deletedEvent = deleteEvent($event);
        bookResourceOnEvent(Event::factory()->create(['date' => now()->addWeeks(2)]), $wrestler);

        // Act
        resolve(RestoreAction::class)->handle($deletedEvent);

        // Assert
        expect(Event::query()->find($event->id))->not->toBeNull();
    });

    test('it restores an event when the other event at the same time shares no resources', function (): void {
        // Arrange
        $date = now()->addWeek();
        $event = Event::factory()->create(['date' => $date]);
        bookResourceOnEvent($event, Wrestler::factory()->bookable()->create());
        $deletedEvent = deleteEvent($event);
        bookResourceOnEvent(Event::factory()->create(['date' => $date]), Wrestler::factory()->bookable()->create());

        // Act
        resolve(RestoreAction::class)->handle($deletedEvent);

        // Assert
        expect(Event::query()->find($event->id))->not->toBeNull();
    });

    test('it restores an unscheduled event without checking other events', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->bookable()->create();
        $event = Event::factory()->unscheduled()->create();
        bookResourceOnEvent($event, $wrestler);
        $deletedEvent = deleteEvent($event);
        bookResourceOnEvent(Event::factory()->unscheduled()->create(), $wrestler);

        // Act
        resolve(RestoreAction::class)->handle($deletedEvent);

        // Assert
        expect(Event::query()->find($event->id))->not->toBeNull();
    });

    test('it restores an event without matches', function (): void {
        // Arrange
        $date = now()->addWeek();
        $deletedEvent = deleteEvent(Event::factory()->create(['date' => $date]));
        bookResourceOnEvent(Event::factory()->create(['date' => $date]), Wrestler::factory()->bookable()->create());

        // Act
        resolve(RestoreAction::class)->handle($deletedEvent);

        // Assert
        expect(Event::query()->find($deletedEvent->id))->not->toBeNull();
    });

    test('it does not re-check matches that were deleted with the event', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->bookable()->create();
        $date = now()->addWeek();
        $event = Event::factory()->create(['date' => $date]);
        $match = bookResourceOnEvent($event, $wrestler);
        $match->delete();
        $deletedEvent = deleteEvent($event);
        bookResourceOnEvent(Event::factory()->create(['date' => $date]), $wrestler);

        // Act
        resolve(RestoreAction::class)->handle($deletedEvent);

        // Assert
        expect(Event::query()->find($event->id))->not->toBeNull()
            ->and(EventMatch::onlyTrashed()->whereKey($match->id)->exists())->toBeTrue();
    });
});
