<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;

test('it creates a member invitation for a new promotion by default', function () {
    // Act
    $invitation = PromotionInvitation::factory()->create();

    // Assert
    expect($invitation->role)->toBe(MembershipRole::Member)
        ->and($invitation->email)->toContain('@')
        ->and($invitation->promotion)->toBeInstanceOf(Promotion::class);
});

test('it supports an explicit email and role', function () {
    // Arrange
    $promotion = Promotion::factory()->create();

    // Act
    $invitation = PromotionInvitation::factory()
        ->for($promotion)
        ->forEmail('Chosen@Example.test')
        ->withRole(MembershipRole::Owner)
        ->create();

    // Assert
    expect($invitation->promotion_id)->toBe($promotion->id)
        ->and($invitation->email)->toBe('chosen@example.test')
        ->and($invitation->role)->toBe(MembershipRole::Owner);
});

test('it expires thirty days from now by default', function () {
    // Act
    $invitation = PromotionInvitation::factory()->create();

    // Assert
    expect($invitation->expires_at->toDateTimeString())->toBe(now()->addDays(30)->toDateTimeString());
});

test('its expired state is already past its expiry', function () {
    // Act
    $invitation = PromotionInvitation::factory()->expired()->create();

    // Assert
    expect($invitation->expires_at->isPast())->toBeTrue();
});
