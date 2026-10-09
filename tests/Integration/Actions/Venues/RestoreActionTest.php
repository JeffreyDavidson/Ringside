<?php

declare(strict_types=1);

use App\Actions\Venues\DeleteAction;
use App\Actions\Venues\RestoreAction;
use App\Enums\BusinessRuleReason;
use App\Exceptions\Events\CannotBeRestoredException;
use App\Models\Events\Event;
use App\Models\Events\Venue;

test('it restores a soft-deleted venue', function (): void {
    $venue = Venue::factory()->create();
    resolve(DeleteAction::class)->handle($venue);

    $deletedVenue = Venue::withTrashed()->findOrFail($venue->id);
    resolve(RestoreAction::class)->handle($deletedVenue);

    expect(Venue::query()->find($venue->id))->not->toBeNull()
        ->and(Venue::withTrashed()->findOrFail($venue->id)->deleted_at)->toBeNull();
});

test('it rejects restoring a venue that is not deleted', function (): void {
    $venue = Venue::factory()->create();
    $exception = null;

    try {
        resolve(RestoreAction::class)->handle($venue);
    } catch (CannotBeRestoredException $caught) {
        $exception = $caught;
    }

    expect($exception?->reason())->toBe(BusinessRuleReason::NotDeleted)
        ->and(Venue::query()->find($venue->id))->not->toBeNull();
});

test('it rejects restoring a venue whose live events share a date, naming the date', function (): void {
    $date = now()->addWeek()->setTime(18, 0);
    $venue = Venue::factory()->create();
    Event::factory()->for($venue)->create(['date' => $date]);
    Event::factory()->for($venue)->create(['date' => $date->copy()->setTime(20, 0)]);
    resolve(DeleteAction::class)->handle($venue);

    $act = fn () => resolve(RestoreAction::class)->handle(Venue::withTrashed()->findOrFail($venue->id));

    expect($act)->toThrow(CannotBeRestoredException::class, "Venue '{$venue->name}' cannot be restored because it hosts more than one event on {$date->format('M j, Y')}. Move or delete the extra events first.")
        ->and(Venue::onlyTrashed()->whereKey($venue->id)->exists())->toBeTrue();
});

test('it restores a venue whose live events are on different days and ignores deleted events', function (): void {
    $date = now()->addWeek();
    $venue = Venue::factory()->create();
    Event::factory()->for($venue)->create(['date' => $date]);
    Event::factory()->for($venue)->create(['date' => $date->copy()->addDay()]);
    Event::factory()->for($venue)->create(['date' => $date])->delete();
    resolve(DeleteAction::class)->handle($venue);

    resolve(RestoreAction::class)->handle(Venue::withTrashed()->findOrFail($venue->id));

    expect(Venue::query()->find($venue->id))->not->toBeNull();
});
