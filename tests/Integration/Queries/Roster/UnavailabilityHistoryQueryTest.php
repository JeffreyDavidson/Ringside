<?php

declare(strict_types=1);

use App\Actions\Roster\Availability\CancelAction;
use App\Actions\Roster\Availability\CreateAction;
use App\Data\Roster\UnavailabilityData;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Roster\UnavailabilityHistoryQuery;

test('lists cancelled and ended entries, latest first, for the person only', function (): void {
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
    $old = $create->handle($person, new UnavailabilityData('2026-09-01', '2026-09-02', null), $actor);
    $ended = $create->handle($person, new UnavailabilityData('2026-10-10', '2026-10-19', null), $actor);
    $cancelled = $create->handle($person, new UnavailabilityData('2026-12-01', '2026-12-02', null), $actor);
    resolve(CancelAction::class)->handle($person, $cancelled->id, $actor);
    $create->handle($person, new UnavailabilityData('2026-10-20', '2026-10-20', null), $actor);
    $otherEnded = $create->handle($other, new UnavailabilityData('2026-09-01', '2026-09-02', null), $actor);
    $query = resolve(UnavailabilityHistoryQuery::class);

    // Act
    $page = $query->get($person, '2026-10-20')->paginate(10, pageName: 'availabilityHistoryPage');

    // Assert
    expect($page->pluck('id')->all())->toBe([$cancelled->id, $ended->id, $old->id])
        ->and($page->pluck('id'))->not->toContain($otherEnded->id);
});
