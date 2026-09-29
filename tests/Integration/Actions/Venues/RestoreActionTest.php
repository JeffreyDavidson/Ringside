<?php

declare(strict_types=1);

use App\Actions\Venues\DeleteAction;
use App\Actions\Venues\RestoreAction;
use App\Enums\BusinessRuleReason;
use App\Exceptions\Events\CannotBeRestoredException;
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
