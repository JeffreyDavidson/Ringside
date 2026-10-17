<?php

declare(strict_types=1);

use App\Models\Promotions\Promotion;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Matches\BookingHistorySubjectsQuery;

test('reads the requested wrestlers of the promotion, deleted ones included, with the promotion loaded', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    [$kept, $deleted, $unrequested] = Wrestler::factory()
        ->count(3)
        ->for($promotion, 'promotion')
        ->create()
        ->all();
    $foreign = Wrestler::factory()
        ->for(Promotion::factory(), 'promotion')
        ->create();
    $deleted->delete();

    // Act
    $subjects = resolve(BookingHistorySubjectsQuery::class)->get(
        new Wrestler, [$kept->id, $deleted->id, $foreign->id], $promotion->id,
    );

    // Assert
    expect($subjects->modelKeys())->toEqualCanonicalizing([$kept->id, $deleted->id])
        ->and($subjects->modelKeys())->not->toContain($unrequested->id, $foreign->id)
        ->and($subjects->every(fn (Wrestler|TagTeam $subject): bool => $subject->relationLoaded('promotion')))->toBeTrue();
});

test('reads tag teams when asked for them and nothing for an empty selection', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $team = TagTeam::factory()
        ->for($promotion, 'promotion')
        ->create();

    // Act
    $teams = resolve(BookingHistorySubjectsQuery::class)->get(new TagTeam, [$team->id], $promotion->id);
    $none = resolve(BookingHistorySubjectsQuery::class)->get(new TagTeam, [], $promotion->id);

    // Assert
    expect($teams->modelKeys())->toBe([$team->id])
        ->and($none)->toBeEmpty();
});

test('runs one query for the subjects and one eager load for their promotion', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $wrestlers = Wrestler::factory()
        ->count(3)
        ->for($promotion, 'promotion')
        ->create();

    // Act
    $statements = recordStatements(fn (): mixed => resolve(BookingHistorySubjectsQuery::class)->get(
        new Wrestler, $wrestlers->modelKeys(), $promotion->id,
    ));

    // Assert
    expect($statements)->toHaveCount(2);
});
