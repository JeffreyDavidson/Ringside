<?php

declare(strict_types=1);

use App\Actions\Roster\Availability\CancelAction;
use App\Actions\Roster\Availability\CreateAction;
use App\Data\Roster\UnavailabilityData;
use App\Lifecycle\Roster\RosterDateAvailability;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Availability\Unavailability;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\Activitylog\Models\Activity;

test('cancelling retains history and repeated cancellation cannot remove an overlapping entry', function (): void {
    // Arrange
    $person = Wrestler::factory()->for(Promotion::factory())->create();
    $actor = administrator();
    $first = resolve(CreateAction::class)->handle($person, new UnavailabilityData('2026-10-17', '2026-10-19', null), $actor);
    resolve(CreateAction::class)->handle($person, new UnavailabilityData('2026-10-18', '2026-10-20', null), $actor);
    $action = resolve(CancelAction::class);

    // Act
    $action->handle($person, $first->id, $actor);
    $action->handle($person, $first->id, $actor);

    // Assert
    expect($first->refresh()->cancelled_at)->not->toBeNull()
        ->and($first->cancelled_by)->toBe($actor->id)
        ->and(Unavailability::query()->count())->toBe(2)
        ->and(resolve(RosterDateAvailability::class)->allowsOn($person, '2026-10-17'))->toBeTrue()
        ->and(resolve(RosterDateAvailability::class)->allowsOn($person, '2026-10-18'))->toBeFalse()
        ->and(Activity::query()->where('log_name', 'availability')->where('description', 'cancel')->count())->toBe(1);
});

test('a foreign entry cannot be cancelled through another person even for a global administrator', function (): void {
    // Arrange
    $person = Wrestler::factory()->for(Promotion::factory())->create();
    $other = Wrestler::factory()->for(Promotion::factory())->create();
    $actor = administrator();
    $entry = resolve(CreateAction::class)->handle($other, new UnavailabilityData('2026-10-17', '2026-10-17', null), $actor);

    // Act
    $cancel = fn () => resolve(CancelAction::class)->handle($person, $entry->id, $actor);

    // Assert
    expect($cancel)->toThrow(ModelNotFoundException::class)->and($entry->refresh()->cancelled_at)->toBeNull();
});
