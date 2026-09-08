<?php

declare(strict_types=1);

use App\Enums\Titles\TitleStatus;
use App\Models\Titles\Title;

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
    $this->expectsDatabaseQueryCount(1);

    // Act
    $query = Title::query();
    $query->withActivityStatusState();
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
