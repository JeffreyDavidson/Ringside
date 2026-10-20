<?php

declare(strict_types=1);

use App\Actions\Roster\Availability\CancelAction;
use App\Actions\Roster\Availability\CreateAction;
use App\Data\Roster\UnavailabilityData;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Roster\CurrentUnavailabilitiesQuery;

test('lists the active entries that have not ended, earliest first, for the person only', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $person = Wrestler::factory()
        ->bookable()
        ->for($promotion)
        ->create();
    $other = Wrestler::factory()
        ->bookable()
        ->for($promotion)
        ->create();
    $actor = administrator();
    $create = resolve(CreateAction::class);
    $later = $create->handle($person, new UnavailabilityData('2026-11-01', '2026-11-02', null), $actor);
    $today = $create->handle($person, new UnavailabilityData('2026-10-20', '2026-10-20', null), $actor);
    $sameStart = $create->handle($person, new UnavailabilityData('2026-10-20', '2026-10-25', null), $actor);
    $create->handle($person, new UnavailabilityData('2026-10-01', '2026-10-19', null), $actor);
    $cancelled = $create->handle($person, new UnavailabilityData('2026-12-01', '2026-12-02', null), $actor);
    resolve(CancelAction::class)->handle($person, $cancelled->id, $actor);
    $create->handle($other, new UnavailabilityData('2026-10-21', '2026-10-21', null), $actor);
    $query = resolve(CurrentUnavailabilitiesQuery::class);

    // Act
    $page = $query->get($person, '2026-10-20')->paginate(10, pageName: 'availabilityPage');

    // Assert
    expect($page->pluck('id')->all())->toBe([$today->id, $sameStart->id, $later->id]);
});
