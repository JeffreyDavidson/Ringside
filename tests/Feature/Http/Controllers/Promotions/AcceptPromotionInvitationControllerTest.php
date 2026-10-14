<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

require_once __DIR__.'/../../../../Fixtures/promotion-invitations.php';

describe('accepting an invitation', function () {
    test('creates an active membership with the role the owner chose and deletes the invitation', function (MembershipRole $role) {
        // Arrange
        $promotion = Promotion::factory()->create(['name' => 'Invitation Championship Wrestling']);
        $invitee = basicUser();
        inviteTo($promotion, $invitee, $role);
        actingAs($invitee);

        // Act
        $response = post(route('promotions.invitation.accept', $promotion));

        // Assert
        $membership = $promotion->memberships()->where('user_id', $invitee->id)->firstOrFail();

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', __('promotions.invitation_accepted', ['promotion' => 'Invitation Championship Wrestling', 'role' => $role->label()]));

        expect($membership->status)->toBe(MembershipStatus::Active)
            ->and($membership->role)->toBe($role)
            ->and(promotionHasMemberWithRole($promotion, $invitee, $role))->toBeTrue()
            ->and($promotion->invitations()->count())->toBe(0);
    })->with([
        'owner' => MembershipRole::Owner,
        'manager' => MembershipRole::Manager,
        'member' => MembershipRole::Member,
    ]);

    test('gives the new member access to the promotion records', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $invitee = basicUser();
        inviteTo($promotion, $invitee, MembershipRole::Member);
        $wrestler = Wrestler::factory()->for($promotion, 'promotion')->create();
        actingAs($invitee);
        $beforeAccepting = get(route('wrestlers.show', $wrestler));
        post(route('promotions.invitation.accept', $promotion));

        // Act
        $afterAccepting = get(route('wrestlers.show', $wrestler));

        // Assert
        $beforeAccepting->assertForbidden();
        $afterAccepting->assertOk();
    });

    test('cannot be done for another email by forging the promotion of someone elses invitation', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $invitee = basicUser();
        $attacker = basicUser();
        $invitation = inviteTo($promotion, $invitee, MembershipRole::Owner);
        actingAs($attacker);

        // Act
        $response = post(route('promotions.invitation.accept', $promotion));

        // Assert
        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', __('promotions.invitation_unavailable'));

        expect($invitation->fresh())->not->toBeNull()
            ->and($promotion->memberships()->count())->toBe(0);
    });

    test('is not possible for a suspended member, keeps the suspension and deletes the invitation', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $other = Promotion::factory()->create();
        $user = basicUser();
        joinAs($promotion, $user, MembershipRole::Member, MembershipStatus::Suspended);
        joinAs($other, $user);
        $invitation = inviteTo($promotion, $user, MembershipRole::Owner);
        actingAs($user);

        // Act
        $response = post(route('promotions.invitation.accept', $promotion));

        // Assert
        $membership = $promotion->memberships()->sole();

        $response->assertSessionHas('error', __('promotions.invitation_unavailable'));

        expect($membership->status)->toBe(MembershipStatus::Suspended)
            ->and($membership->role)->toBe(MembershipRole::Member)
            ->and($invitation->fresh())->toBeNull()
            ->and(promotionHasActiveMember($promotion, $user))->toBeFalse();
    });

    test('is not possible once the invitation has expired', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $user = basicUser();
        PromotionInvitation::factory()->for($promotion)->forEmail($user->email)->expired()->create();
        actingAs($user);

        // Act
        $response = post(route('promotions.invitation.accept', $promotion));

        // Assert
        $response->assertSessionHas('error', __('promotions.invitation_unavailable'));

        expect($promotion->memberships()->count())->toBe(0);
    });

    test('is not possible after the invitation is gone', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $user = basicUser();
        actingAs($user);

        // Act
        $response = post(route('promotions.invitation.accept', $promotion));

        // Assert
        $response->assertSessionHas('error', __('promotions.invitation_unavailable'));

        expect($promotion->memberships()->count())->toBe(0);
    });

    test('requires a signed-in user', function () {
        // Arrange
        $promotion = Promotion::factory()->create();

        // Act
        $response = post(route('promotions.invitation.accept', $promotion));

        // Assert
        $response->assertRedirect(route('login'));
    });

    test('is refused for an inactive account', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $user = User::factory()->basicUser()->create(['status' => UserStatus::Inactive]);
        $invitation = inviteTo($promotion, $user);
        actingAs($user);

        // Act
        $response = post(route('promotions.invitation.accept', $promotion));

        // Assert
        $response->assertRedirect(route('login'));

        expect(promotionHasActiveMember($promotion, $user))->toBeFalse()
            ->and($invitation->fresh())->not->toBeNull();
    });
});
