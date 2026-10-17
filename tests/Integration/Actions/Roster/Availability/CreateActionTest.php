<?php

declare(strict_types=1);

use App\Actions\Roster\Availability\CreateAction;
use App\Exceptions\Roster\Availability\UnavailabilityException;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Availability\Unavailability;
use App\Models\Roster\Wrestlers\Wrestler;
use Spatie\Activitylog\Models\Activity;

test('creating unavailability stores inclusive calendar dates and its actor without changing the roster', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
    $person = Wrestler::factory()->bookable()->for($promotion)->create();
    $actor = administrator();

    // Act
    $entry = resolve(CreateAction::class)->handle($person, '2026-10-17', '2026-10-19', ' Outside commitment ', $actor);

    // Assert
    expect($entry->starts_on)->toBe('2026-10-17')
        ->and($entry->ends_on)->toBe('2026-10-19')
        ->and($entry->promotion_id)->toBe($promotion->id)
        ->and($entry->person_type)->toBe('wrestler')
        ->and($entry->person_id)->toBe($person->id)
        ->and($entry->created_by)->toBe($actor->id)
        ->and($entry->reason)->toBe('Outside commitment')
        ->and($entry->cancelled_at)->toBeNull()
        ->and($person->refresh()->currentEmployment()->exists())->toBeTrue()
        ->and(Activity::query()->where('log_name', 'availability')->where('subject_id', $person->id)->latest('id')->firstOrFail()->description)->toBe('create');
});

test('an exact repeated unavailable submission is rejected but overlapping ranges are retained', function (): void {
    // Arrange
    $person = Wrestler::factory()->for(Promotion::factory())->create();
    $actor = administrator();
    $action = resolve(CreateAction::class);
    $action->handle($person, '2026-10-17', '2026-10-19', null, $actor);

    // Act
    $duplicate = fn () => $action->handle($person, '2026-10-17', '2026-10-19', ' ', $actor);

    // Assert
    expect($duplicate)->toThrow(UnavailabilityException::class);
    $action->handle($person, '2026-10-18', '2026-10-20', null, $actor);
    expect(Unavailability::query()->count())->toBe(2);
});

test('an unowned record cannot receive a calendar restriction', function (): void {
    // Arrange
    $person = Wrestler::factory()->create(['promotion_id' => null]);
    $actor = administrator();

    // Act
    $save = fn () => resolve(CreateAction::class)->handle($person, '2026-10-17', '2026-10-17', null, $actor);

    // Assert
    expect($save)->toThrow(UnavailabilityException::class)->and(Unavailability::query()->count())->toBe(0);
});
