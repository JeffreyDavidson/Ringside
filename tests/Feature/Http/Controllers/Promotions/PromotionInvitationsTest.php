<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withSession;

function inviteTo(Promotion $promotion, User $user, MembershipRole $role = MembershipRole::Manager): void
{
    $promotion->users()->attach($user, ['role' => $role, 'status' => MembershipStatus::Invited]);
}

function joinAs(Promotion $promotion, User $user, MembershipRole $role = MembershipRole::Member): void
{
    $promotion->users()->attach($user, ['role' => $role, 'status' => MembershipStatus::Active]);
}

describe('an invitation grants nothing until it is accepted', function () {
    test('an invited user with no other membership gets the no-membership page and no access to the inviting promotion', function (string $routeName) {
        // Arrange
        $promotion = Promotion::factory()->create();
        $invitee = basicUser();
        inviteTo($promotion, $invitee, MembershipRole::Owner);
        $wrestler = Wrestler::factory()->for($promotion, 'promotion')->create();
        actingAs($invitee);

        // Act
        $response = get(match ($routeName) {
            'dashboard' => route('dashboard'),
            'wrestlers.show' => route('wrestlers.show', $wrestler),
            default => route('promotions.show', $promotion),
        });

        // Assert
        $response->assertForbidden();
    })->with(['dashboard', 'wrestlers.show', 'promotions.show']);

    test('an invited user keeps their own promotion and cannot reach the inviting promotions records', function () {
        // Arrange
        $own = Promotion::factory()->create();
        $inviting = Promotion::factory()->create();
        $invitee = basicUser();
        joinAs($own, $invitee);
        inviteTo($inviting, $invitee, MembershipRole::Owner);
        $foreignWrestler = Wrestler::factory()->for($inviting, 'promotion')->create();
        withSession(['active_promotion_id' => $inviting->id]);
        actingAs($invitee);

        // Act
        $dashboard = get(route('dashboard'));
        $foreignRecord = get(route('wrestlers.show', $foreignWrestler));
        $foreignPage = get(route('promotions.show', $inviting));

        // Assert
        $dashboard->assertSuccessful()->assertSessionHas('active_promotion_id', $own->id);
        $foreignRecord->assertNotFound();
        $foreignPage->assertForbidden();
        expect($inviting->hasActiveMember($invitee))->toBeFalse();
    });

    test('an invited user cannot switch to the inviting promotion', function () {
        // Arrange
        $own = Promotion::factory()->create();
        $inviting = Promotion::factory()->create();
        $invitee = basicUser();
        joinAs($own, $invitee);
        inviteTo($inviting, $invitee);
        actingAs($invitee);

        // Act
        $response = post(route('promotions.switch'), ['promotion_id' => $inviting->id]);

        // Assert
        $response->assertForbidden();
        expect(session('active_promotion_id'))->toBeNull();
    });
});

describe('seeing invitations', function () {
    test('the no-membership page lists the users own pending invitations with accept and decline', function () {
        // Arrange
        $promotion = Promotion::factory()->create(['name' => 'Invitation Championship Wrestling']);
        $otherPromotion = Promotion::factory()->create(['name' => 'Somebody Elses Wrestling']);
        $invitee = basicUser();
        inviteTo($promotion, $invitee, MembershipRole::Manager);
        inviteTo($otherPromotion, basicUser());
        actingAs($invitee);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response->assertForbidden()
            ->assertViewIs('promotions.no-membership')
            ->assertSee('Invitation Championship Wrestling')
            ->assertSee(__('promotions.invitation_role', ['role' => 'Manager']))
            ->assertSeeHtml(route('promotions.invitation.accept', $promotion))
            ->assertSeeHtml(route('promotions.invitation.decline', $promotion))
            ->assertDontSee('Somebody Elses Wrestling')
            ->assertDontSeeHtml(route('promotions.invitation.accept', $otherPromotion));
    });

    test('a user who already has a promotion sees the invitation in the promotion switcher', function () {
        // Arrange
        $own = Promotion::factory()->create();
        $inviting = Promotion::factory()->create(['name' => 'Invitation Championship Wrestling']);
        $invitee = basicUser();
        joinAs($own, $invitee);
        inviteTo($inviting, $invitee);
        actingAs($invitee);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response->assertSuccessful()
            ->assertSee('Invitation Championship Wrestling')
            ->assertSeeHtml('data-test="invitation-indicator"')
            ->assertSeeHtml(route('promotions.invitation.accept', $inviting));
    });

    test('a user without invitations sees none', function () {
        // Arrange
        $own = Promotion::factory()->create();
        $invitee = basicUser();
        joinAs($own, $invitee);
        actingAs($invitee);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response->assertSuccessful()
            ->assertDontSeeHtml('data-test="invitation-indicator"')
            ->assertDontSeeHtml('data-test="pending-invitations"');
    });
});

describe('accepting an invitation', function () {
    test('turns the membership active with the role the owner chose', function (MembershipRole $role) {
        // Arrange
        $promotion = Promotion::factory()->create(['name' => 'Invitation Championship Wrestling']);
        $invitee = basicUser();
        inviteTo($promotion, $invitee, $role);
        actingAs($invitee);

        // Act
        $response = post(route('promotions.invitation.accept', $promotion));

        // Assert
        $membership = $promotion->memberships()->where('user_id', $invitee->id)->firstOrFail();

        $response->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', __('promotions.invitation_accepted', ['promotion' => 'Invitation Championship Wrestling', 'role' => $role->label()]));
        expect($membership->status)->toBe(MembershipStatus::Active)
            ->and($membership->role)->toBe($role)
            ->and($promotion->hasMemberWithRole($invitee, $role))->toBeTrue();
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

    test('cannot be done for someone else by forging the promotion of another users invitation', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $invitee = basicUser();
        $attacker = basicUser();
        inviteTo($promotion, $invitee, MembershipRole::Owner);
        actingAs($attacker);

        // Act
        $response = post(route('promotions.invitation.accept', $promotion));

        // Assert
        $response->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', __('promotions.invitation_unavailable'));
        expect($promotion->memberships()->where('user_id', $invitee->id)->firstOrFail()->status)->toBe(MembershipStatus::Invited)
            ->and($promotion->memberships()->where('user_id', $attacker->id)->exists())->toBeFalse();
    });

    test('is not possible for a suspended member or after the invitation is gone', function (?MembershipStatus $status) {
        // Arrange
        $promotion = Promotion::factory()->create();
        $user = basicUser();

        if ($status instanceof MembershipStatus) {
            $promotion->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => $status]);
        }

        actingAs($user);

        // Act
        $response = post(route('promotions.invitation.accept', $promotion));

        // Assert
        $response->assertSessionHas('error', __('promotions.invitation_unavailable'));
        expect($promotion->memberships()->where('user_id', $user->id)->first()?->status)->toBe($status);
    })->with([
        'cancelled invitation' => [null],
        'suspended member' => [MembershipStatus::Suspended],
    ]);

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
        inviteTo($promotion, $user);
        actingAs($user);

        // Act
        $response = post(route('promotions.invitation.accept', $promotion));

        // Assert
        $response->assertRedirect(route('login'));
        expect($promotion->hasActiveMember($user))->toBeFalse();
    });
});

describe('declining an invitation', function () {
    test('removes the invitation and nothing else', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $otherPromotion = Promotion::factory()->create();
        $invitee = basicUser();
        $bystander = basicUser();
        inviteTo($promotion, $invitee);
        inviteTo($otherPromotion, $invitee);
        inviteTo($promotion, $bystander);
        actingAs($invitee);

        // Act
        $response = post(route('promotions.invitation.decline', $promotion));

        // Assert
        $response->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', __('promotions.invitation_declined'));
        expect($promotion->memberships()->where('user_id', $invitee->id)->exists())->toBeFalse()
            ->and($otherPromotion->memberships()->where('user_id', $invitee->id)->exists())->toBeTrue()
            ->and($promotion->memberships()->where('user_id', $bystander->id)->exists())->toBeTrue();
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
        expect($promotion->hasMemberWithRole($member, MembershipRole::Owner))->toBeTrue();
    });

    test('cannot be done for someone else by forging the promotion of another users invitation', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $invitee = basicUser();
        $attacker = basicUser();
        inviteTo($promotion, $invitee);
        actingAs($attacker);

        // Act
        $response = post(route('promotions.invitation.decline', $promotion));

        // Assert
        $response->assertSessionHas('error', __('promotions.invitation_unavailable'));
        expect($promotion->memberships()->where('user_id', $invitee->id)->exists())->toBeTrue();
    });
});
