<?php

declare(strict_types=1);

use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Events\EventTalentPeopleQuery;

test('reads the asked wrestlers of the promotion keyed by id, deleted ones included, in one statement', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $kept = Wrestler::factory()
        ->for($promotion)
        ->create();
    $deleted = Wrestler::factory()
        ->for($promotion)
        ->create();
    $deleted->delete();
    $notAsked = Wrestler::factory()
        ->for($promotion)
        ->create();
    $foreign = Wrestler::factory()
        ->create();
    $query = resolve(EventTalentPeopleQuery::class);
    $ids = [$kept->id, $deleted->id, $foreign->id];

    // Act
    $people = $query->get(Wrestler::class, $promotion->id, $ids);
    $statements = recordStatements(fn () => $query->get(Wrestler::class, $promotion->id, $ids));

    // Assert
    expect($people->keys()->all())->toEqualCanonicalizing([$kept->id, $deleted->id])
        ->and($people->has($notAsked->id))->toBeFalse()
        ->and($statements)->toHaveCount(1);
});

test('reads referees of the promotion', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $referee = Referee::factory()
        ->for($promotion)
        ->create();

    // Act
    $people = resolve(EventTalentPeopleQuery::class)->get(Referee::class, $promotion->id, [$referee->id]);

    // Assert
    expect($people->get($referee->id)?->is($referee))->toBeTrue();
});
