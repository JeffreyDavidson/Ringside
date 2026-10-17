<?php

declare(strict_types=1);

use App\Actions\Roster\Availability\CreateAction;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Roster\UnavailabilityOnDateQuery;

test('reads the entries that cover the day for the given people of one promotion in one statement', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $otherPromotion = Promotion::factory()
        ->create();
    $wrestler = Wrestler::factory()
        ->bookable()
        ->for($promotion)
        ->create();
    $referee = Referee::factory()
        ->bookable()
        ->for($promotion)
        ->create();
    $notAsked = Wrestler::factory()
        ->bookable()
        ->for($promotion)
        ->create();
    $elsewhere = Wrestler::factory()
        ->bookable()
        ->for($otherPromotion)
        ->create();
    $actor = administrator();
    $handle = resolve(CreateAction::class);
    $wrestlerEntry = $handle->handle($wrestler, '2026-10-20', '2026-10-22', null, $actor);
    $refereeEntry = $handle->handle($referee, '2026-10-21', '2026-10-21', null, $actor);
    $handle->handle($wrestler, '2026-11-01', '2026-11-02', null, $actor);
    $handle->handle($notAsked, '2026-10-21', '2026-10-21', null, $actor);
    $handle->handle($elsewhere, '2026-10-21', '2026-10-21', null, $actor);
    $query = resolve(UnavailabilityOnDateQuery::class);

    // Act
    $entries = $query->get($promotion->id, '2026-10-21', collect([$wrestler->id, $referee->id]));
    $statements = recordStatements(fn () => $query->get($promotion->id, '2026-10-21', collect([$wrestler->id, $referee->id])));

    // Assert
    expect($entries->modelKeys())->toEqualCanonicalizing([$wrestlerEntry->id, $refereeEntry->id])
        ->and($statements)->toHaveCount(1);
});
