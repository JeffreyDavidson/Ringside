<?php

declare(strict_types=1);

use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;

test('it filters invitations by promotion', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $invitation = PromotionInvitation::factory()->for($promotion)->create();
    PromotionInvitation::factory()->create();

    // Act
    $invitations = PromotionInvitation::query()
        ->forPromotion($promotion)
        ->get();

    // Assert
    expect($invitations->modelKeys())->toBe([$invitation->id]);
});

test('it filters invitations by email ignoring case and surrounding whitespace', function (string $search) {
    // Arrange
    $invitation = PromotionInvitation::factory()->forEmail('Match.Me@example.test')->create();
    PromotionInvitation::factory()->forEmail('match.me.too@example.test')->create();

    // Act
    $invitations = PromotionInvitation::query()
        ->forEmail($search)
        ->get();

    // Assert
    expect($invitations->modelKeys())->toBe([$invitation->id]);
})->with([
    'exact' => ['match.me@example.test'],
    'upper case' => ['MATCH.ME@EXAMPLE.TEST'],
    'whitespace' => ['  match.me@example.test '],
]);

test('it never widens an email filter with wildcard characters', function (string $search) {
    // Arrange
    PromotionInvitation::factory()->forEmail('someone@example.test')->create();

    // Act
    $exists = PromotionInvitation::query()
        ->forEmail($search)
        ->exists();

    // Assert
    expect($exists)->toBeFalse();
})->with([
    'percent' => ['%@example.test'],
    'underscore' => ['someon_@example.test'],
]);

test('it separates pending invitations from expired ones, counting the expiry moment itself as expired', function () {
    // Arrange
    $pending = PromotionInvitation::factory()->create(['expires_at' => now()->addSecond()]);
    $atExpiry = PromotionInvitation::factory()->create(['expires_at' => now()]);
    $expired = PromotionInvitation::factory()->expired()->create();

    // Act
    $pendingIds = PromotionInvitation::query()->pending()->pluck('id')->all();
    $expiredIds = PromotionInvitation::query()->expired()->pluck('id')->all();

    // Assert
    expect($pendingIds)->toBe([$pending->id])
        ->and($expiredIds)->toEqualCanonicalizing([$atExpiry->id, $expired->id]);
});

test('it orders invitations by creation with the id breaking ties', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $later = PromotionInvitation::factory()->for($promotion)->create(['created_at' => '2026-02-01 00:00:00']);
    $tieFirst = PromotionInvitation::factory()->for($promotion)->create(['created_at' => '2026-01-01 00:00:00']);
    $tieSecond = PromotionInvitation::factory()->for($promotion)->create(['created_at' => '2026-01-01 00:00:00']);

    // Act
    $invitations = PromotionInvitation::query()
        ->oldestFirst()
        ->get();

    // Assert
    expect($invitations->modelKeys())->toBe([$tieFirst->id, $tieSecond->id, $later->id]);
});
