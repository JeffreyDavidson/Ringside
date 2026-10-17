<?php

declare(strict_types=1);

use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Events\BookableIdsQuery;

test('lists only the bookable wrestlers among the given ids in one statement', function (): void {
    // Arrange
    $bookable = Wrestler::factory()
        ->bookable()
        ->create();
    $unemployed = Wrestler::factory()
        ->unemployed()
        ->create();
    $notAsked = Wrestler::factory()
        ->bookable()
        ->create();
    $query = resolve(BookableIdsQuery::class);

    // Act
    $ids = $query->get(Wrestler::class, [$unemployed->id, $bookable->id]);
    $statements = recordStatements(fn () => $query->get(Wrestler::class, [$unemployed->id, $bookable->id]));

    // Assert
    expect($ids)->toBe([$bookable->id])
        ->and($ids)->not->toContain($notAsked->id)
        ->and($statements)->toHaveCount(1);
});

test('lists only the bookable referees among the given ids', function (): void {
    // Arrange
    $bookable = Referee::factory()
        ->bookable()
        ->create();
    $unemployed = Referee::factory()
        ->unemployed()
        ->create();

    // Act
    $ids = resolve(BookableIdsQuery::class)->get(Referee::class, [$unemployed->id, $bookable->id]);

    // Assert
    expect($ids)->toBe([$bookable->id]);
});

test('lists only the bookable tag teams among the given ids', function (): void {
    // Arrange
    $bookable = TagTeam::factory()
        ->bookable()
        ->create();
    $unbookable = TagTeam::factory()
        ->unbookable()
        ->create();

    // Act
    $ids = resolve(BookableIdsQuery::class)->get(TagTeam::class, [$unbookable->id, $bookable->id]);

    // Assert
    expect($ids)->toBe([$bookable->id]);
});

test('lists nothing for no ids', function (): void {
    // Arrange
    Wrestler::factory()
        ->bookable()
        ->create();

    // Act
    $ids = resolve(BookableIdsQuery::class)->get(Wrestler::class, []);

    // Assert
    expect($ids)->toBeEmpty();
});
