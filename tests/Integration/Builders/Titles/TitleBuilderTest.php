<?php

declare(strict_types=1);

use App\Builders\Titles\TitleBuilder;
use App\Enums\Titles\TitleStatus;
use App\Models\Promotions\Promotion;
use App\Models\Titles\Title;

use function Pest\Laravel\expectsDatabaseQueryCount;

test('active titles can be retrieved', function () {
    // Arrange
    $activeTitle = Title::factory()->active()->create();
    Title::factory()->active()->trashed()->create();
    Title::factory()->withFutureActivation()->create();
    Title::factory()->inactive()->create();
    Title::factory()->retired()->create();
    Title::factory()->undebuted()->create();

    // Act
    $query = Title::query();
    $query->active();
    $activeTitles = $query->get();

    // Assert
    expect($activeTitles->modelKeys())->toBe([$activeTitle->id]);
});

test('future activated titles can be retrieved', function () {
    // Arrange
    Title::factory()->active()->create();
    $futureActivatedTitle = Title::factory()->withFutureActivation()->create();
    Title::factory()->withFutureActivation()->trashed()->create();
    Title::factory()->inactive()->create();
    Title::factory()->retired()->create();
    Title::factory()->undebuted()->create();

    // Act
    $query = Title::query();
    $query->withPendingDebut();
    $futureActivatedTitles = $query->get();

    // Assert
    expect($futureActivatedTitles->modelKeys())->toBe([$futureActivatedTitle->id]);
});

test('inactive titles can be retrieved', function () {
    // Arrange
    Title::factory()->active()->create();
    Title::factory()->withFutureActivation()->create();
    $inactiveTitle = Title::factory()->inactive()->create();
    Title::factory()->inactive()->trashed()->create();
    Title::factory()->retired()->create();
    Title::factory()->undebuted()->create();

    // Act
    $query = Title::query();
    $query->inactive();
    $inactiveTitles = $query->get();

    // Assert
    expect($inactiveTitles->modelKeys())->toBe([$inactiveTitle->id]);
});

test('retired titles can be retrieved separately', function () {
    // Arrange
    Title::factory()->active()->create();
    $retiredTitle = Title::factory()->retired()->create();
    Title::factory()->retired()->trashed()->create();
    Title::factory()->withFutureActivation()->create();
    Title::factory()->inactive()->create();
    Title::factory()->undebuted()->create();

    // Act
    $query = Title::query();
    $query->retired();
    $retiredTitles = $query->get();

    // Assert
    expect($retiredTitles->modelKeys())->toBe([$retiredTitle->id]);
});

test('projected activity status does not query per title', function () {
    // Arrange
    $active = Title::factory()->active()->create();
    $retired = Title::factory()->retired()->create();
    $pending = Title::factory()->withFutureActivation()->create();
    $inactive = Title::factory()->inactive()->create();
    $initial = Title::factory()->unactivated()->create();
    expectsDatabaseQueryCount(1);

    // Act
    $query = Title::query();
    $query->withExists(TitleBuilder::ACTIVITY_STATUS_STATE);
    $query->orderBy('id');
    $titles = $query->get();
    $statuses = $titles->mapWithKeys(fn (Title $title): array => [$title->id => $title->status]);

    // Assert
    expect($statuses->all())->toBe([
        $active->id => TitleStatus::Active,
        $retired->id => TitleStatus::Retired,
        $pending->id => TitleStatus::PendingDebut,
        $inactive->id => TitleStatus::Inactive,
        $initial->id => TitleStatus::Undebuted,
    ]);
});

test('titles offered for a promotion include its titles and the ids already selected', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $own = Title::factory()->for($promotion)->create();
    $selectedElsewhere = Title::factory()->create();
    Title::factory()->create();

    // Act
    $titles = Title::query()
        ->offeredForPromotion($promotion->id, [$selectedElsewhere->id])
        ->orderBy('id')
        ->get();

    // Assert
    expect($titles->modelKeys())->toBe([$own->id, $selectedElsewhere->id]);
});

test('titles offered without a promotion are the unowned ones', function () {
    // Arrange
    $unowned = Title::factory()->create(['promotion_id' => null]);
    Title::factory()->for(Promotion::factory())->create();

    // Act
    $titles = Title::query()
        ->offeredForPromotion(null)
        ->get();

    // Assert
    expect($titles->modelKeys())->toBe([$unowned->id]);
});
