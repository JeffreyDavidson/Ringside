<?php

declare(strict_types=1);

use App\Actions\Promotions\AcceptPromotionInvitationAction;
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

test('creates an active membership with the role the owner chose and deletes the invitation', function (MembershipRole $role) {
    // Arrange
    $promotion = Promotion::factory()
        ->create(['name' => 'Invitation Championship Wrestling']);

    $invitee = basicUser();
    inviteTo($promotion, $invitee, $role);

    actingAs($invitee);

    // Act
    $response = post(route('promotions.invitation.accept', $promotion));

    // Assert
    $response
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', __('promotions.invitation_accepted', [
            'promotion' => 'Invitation Championship Wrestling',
            'role' => $role->label(),
        ]));

    $membership = $promotion->memberships()
        ->where('user_id', $invitee->id)
        ->firstOrFail();

    expect($membership->status)->toBe(MembershipStatus::Active)
        ->and($membership->role)
        ->toBe($role)
        ->and(promotionHasMemberWithRole($promotion, $invitee, $role))
        ->toBeTrue()
        ->and($promotion->invitations()
            ->count())
        ->toBe(0);
})->with([
    'owner' => MembershipRole::Owner,
    'manager' => MembershipRole::Manager,
    'member' => MembershipRole::Member,
]);

test('gives the new member access to the promotion records', function () {
    // Arrange
    $promotion = Promotion::factory()
        ->create();

    $invitee = basicUser();
    inviteTo($promotion, $invitee, MembershipRole::Member);
    $wrestler = Wrestler::factory()
        ->for($promotion, 'promotion')
        ->create();

    actingAs($invitee);
    resolve(AcceptPromotionInvitationAction::class)->handle($promotion, $invitee);

    // Act
    $response = get(route('wrestlers.show', $wrestler));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('wrestlers.show')
        ->assertViewHas('wrestler', $wrestler);

    expect(promotionHasActiveMember($promotion, $invitee))->toBeTrue();
});

test('cannot be done for another email by forging the promotion of someone elses invitation', function () {
    // Arrange
    $promotion = Promotion::factory()
        ->create();

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
        ->and($promotion->memberships()
            ->count())
        ->toBe(0);
});

test('is not possible for a suspended member, keeps the suspension and deletes the invitation', function () {
    // Arrange
    [$promotion, $other] = Promotion::factory()
        ->count(2)
        ->create()
        ->all();
    $user = basicUser();
    joinAs($promotion, $user, MembershipRole::Member, MembershipStatus::Suspended);
    joinAs($other, $user);
    $invitation = inviteTo($promotion, $user, MembershipRole::Owner);

    actingAs($user);

    // Act
    $response = post(route('promotions.invitation.accept', $promotion));

    // Assert
    $response
        ->assertSessionHas('error', __('promotions.invitation_unavailable'));

    $membership = $promotion->memberships()
        ->sole();

    expect($membership->status)->toBe(MembershipStatus::Suspended)
        ->and($membership->role)
        ->toBe(MembershipRole::Member)
        ->and($invitation->fresh())
        ->toBeNull()
        ->and(promotionHasActiveMember($promotion, $user))
        ->toBeFalse();
});

test('is not possible once the invitation has expired', function () {
    // Arrange
    $promotion = Promotion::factory()
        ->create();

    $user = basicUser();
    PromotionInvitation::factory()
        ->for($promotion)
        ->forEmail($user->email)
        ->expired()
        ->create();

    actingAs($user);

    // Act
    $response = post(route('promotions.invitation.accept', $promotion));

    // Assert
    $response
        ->assertSessionHas('error', __('promotions.invitation_unavailable'));

    expect($promotion->memberships()
        ->count())->toBe(0);
});

test('is not possible after the invitation is gone', function () {
    // Arrange
    $promotion = Promotion::factory()
        ->create();

    $user = basicUser();

    actingAs($user);

    // Act
    $response = post(route('promotions.invitation.accept', $promotion));

    // Assert
    $response
        ->assertSessionHas('error', __('promotions.invitation_unavailable'));

    expect($promotion->memberships()
        ->count())->toBe(0);
});

test('requires a signed-in user', function () {
    // Arrange
    $promotion = Promotion::factory()
        ->create();

    // Act
    $response = post(route('promotions.invitation.accept', $promotion));

    // Assert
    $response
        ->assertRedirect(route('login'));
});

test('is refused for an inactive account', function () {
    // Arrange
    $promotion = Promotion::factory()
        ->create();

    $user = User::factory()
        ->basicUser()
        ->create(['status' => UserStatus::Inactive]);
    $invitation = inviteTo($promotion, $user);

    actingAs($user);

    // Act
    $response = post(route('promotions.invitation.accept', $promotion));

    // Assert
    $response
        ->assertRedirect(route('login'));

    expect(promotionHasActiveMember($promotion, $user))->toBeFalse()
        ->and($invitation->fresh())->not->toBeNull();
});

test('a pending invitation alone cannot grant access to promotion records', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $invitee = basicUser();
    inviteTo($promotion, $invitee, MembershipRole::Member);
    $wrestler = Wrestler::factory()
        ->for($promotion)
        ->create();

    actingAs($invitee);

    // Act
    $response = get(route('wrestlers.show', $wrestler));

    // Assert
    $response
        ->assertForbidden();

    expect(promotionHasActiveMember($promotion, $invitee))->toBeFalse();
});

test('an administrator can accept an invitation through the HTTP endpoint', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create(['name' => 'Accepted Wrestling']);
    $admin = administrator();
    $invitation = inviteTo($promotion, $admin, MembershipRole::Manager);

    actingAs($admin);

    // Act
    $response = post(route('promotions.invitation.accept', $promotion));

    // Assert
    $response
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', __('promotions.invitation_accepted', [
            'promotion' => 'Accepted Wrestling',
            'role' => MembershipRole::Manager->label(),
        ]));

    expect(promotionHasMemberWithRole($promotion, $admin, MembershipRole::Manager))->toBeTrue()
        ->and($invitation->fresh())
        ->toBeNull();
});
