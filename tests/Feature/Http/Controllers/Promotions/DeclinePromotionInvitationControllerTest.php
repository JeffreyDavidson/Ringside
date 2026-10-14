<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Models\Promotions\Promotion;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

require_once __DIR__.'/../../../../Fixtures/promotion-invitations.php';

describe('declining an invitation', function () {
    test('removes the invitation and nothing else', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $otherPromotion = Promotion::factory()->create();
        $invitee = basicUser();
        $bystander = basicUser();
        inviteTo($promotion, $invitee);
        $otherInvitation = inviteTo($otherPromotion, $invitee);
        $bystanderInvitation = inviteTo($promotion, $bystander);
        actingAs($invitee);

        // Act
        $response = post(route('promotions.invitation.decline', $promotion));

        // Assert
        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', __('promotions.invitation_declined'));

        expect($promotion->invitations()->pluck('id')->all())->toBe([$bystanderInvitation->id])
            ->and($otherInvitation->fresh())->not->toBeNull()
            ->and($promotion->memberships()->count())->toBe(0);
    });

    test('cannot remove an active membership', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $member = basicUser();
        joinAs($promotion, $member, MembershipRole::Owner);
        actingAs($member);

        // Act
        $response = post(route('promotions.invitation.decline', $promotion));

        // Assert
        $response->assertSessionHas('error', __('promotions.invitation_unavailable'));

        expect(promotionHasMemberWithRole($promotion, $member, MembershipRole::Owner))->toBeTrue();
    });

    test('cannot be done for another email by forging the promotion of someone elses invitation', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $invitee = basicUser();
        $attacker = basicUser();
        $invitation = inviteTo($promotion, $invitee);
        actingAs($attacker);

        // Act
        $response = post(route('promotions.invitation.decline', $promotion));

        // Assert
        $response->assertSessionHas('error', __('promotions.invitation_unavailable'));

        expect($invitation->fresh())->not->toBeNull();
    });
});
