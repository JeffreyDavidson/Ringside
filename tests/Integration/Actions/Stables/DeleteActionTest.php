<?php

declare(strict_types=1);

use App\Actions\Stables\DeleteAction;
use App\Exceptions\Roster\Stables\CannotBeDeletedException;
use App\Models\Stables\Stable;

test('it deletes an inactive stable without current members', function () {
    $stable = Stable::factory()->inactive()->create();

    expect($stable->canBeDeleted())->toBeTrue();

    resolve(DeleteAction::class)->handle($stable);

    expect($stable->refresh()->trashed())->toBeTrue();
});

test('it rejects an active stable', function () {
    $stable = Stable::factory()->active()->create();

    expect($stable->canBeDeleted())->toBeFalse()
        ->and(fn () => resolve(DeleteAction::class)->handle($stable))
        ->toThrow(CannotBeDeletedException::class);
});

test('it rejects a stable with a future establishment scheduled', function () {
    $stable = Stable::factory()->withFutureActivation()->create();

    expect($stable->canBeDeleted())->toBeFalse()
        ->and(fn () => resolve(DeleteAction::class)->handle($stable))
        ->toThrow(CannotBeDeletedException::class, 'has a future establishment scheduled');
});

test('it rejects a stable with current members', function () {
    $stable = Stable::factory()->withEmployedDefaultMembers()->create();

    expect($stable->canBeDeleted())->toBeFalse()
        ->and(fn () => resolve(DeleteAction::class)->handle($stable))
        ->toThrow(CannotBeDeletedException::class);
});

test('it rejects a stable that is already deleted', function () {
    $stable = Stable::factory()->create();
    $stable->delete();

    expect($stable->canBeDeleted())->toBeFalse()
        ->and(fn () => resolve(DeleteAction::class)->handle($stable))
        ->toThrow(CannotBeDeletedException::class);
});
