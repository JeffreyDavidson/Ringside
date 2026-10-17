<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Models\Promotions\Promotion;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Events\PublicEventPeopleQuery;

test('reads the asked wrestlers of the promotion in one statement', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $asked = Wrestler::factory()
        ->for($promotion)
        ->create();
    $notAsked = Wrestler::factory()
        ->for($promotion)
        ->create();
    $foreign = Wrestler::factory()
        ->create();
    $query = resolve(PublicEventPeopleQuery::class);
    $ids = [$asked->id, $foreign->id];

    // Act
    $people = $query->get(Wrestler::class, $promotion->id, $ids);
    $statements = recordStatements(fn () => $query->get(Wrestler::class, $promotion->id, $ids));

    // Assert
    expect($people->modelKeys())->toBe([$asked->id])
        ->and($people->modelKeys())->not->toContain($notAsked->id)
        ->and($statements)->toHaveCount(1);
});

test('reads the asked tag teams of the promotion', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $team = TagTeam::factory()
        ->for($promotion)
        ->create();

    // Act
    $people = resolve(PublicEventPeopleQuery::class)->get(TagTeam::class, $promotion->id, [$team->id]);

    // Assert
    expect($people->modelKeys())->toBe([$team->id]);
});

test('reads them for a signed-in visitor from another promotion', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $wrestler = Wrestler::factory()
        ->for($promotion)
        ->create();
    actingAsPromotionMember(Promotion::factory()->create(), MembershipRole::Owner);

    // Act
    $people = resolve(PublicEventPeopleQuery::class)->get(Wrestler::class, $promotion->id, [$wrestler->id]);

    // Assert
    expect(Wrestler::query()->whereKey($wrestler->id)->exists())->toBeFalse()
        ->and($people->modelKeys())->toBe([$wrestler->id]);
});
