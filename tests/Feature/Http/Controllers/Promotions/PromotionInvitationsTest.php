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
use function Pest\Laravel\followingRedirects;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\travel;
use function Pest\Laravel\withSession;

function inviteTo(Promotion $promotion, User $user, MembershipRole $role = MembershipRole::Manager): PromotionInvitation
{
    return PromotionInvitation::factory()->for($promotion)->forEmail($user->email)->withRole($role)->create();
}

function joinAs(Promotion $promotion, User $user, MembershipRole $role = MembershipRole::Member, MembershipStatus $status = MembershipStatus::Active): void
{
    $promotion->users()->attach($user, ['role' => $role, 'status' => $status]);
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
    test('the no-membership page leaves out an expired invitation', function () {
        // Arrange
        $promotion = Promotion::factory()->create(['name' => 'Expired Invitation Wrestling']);
        $invitee = basicUser();
        PromotionInvitation::factory()->for($promotion)->forEmail($invitee->email)->expired()->create();
        actingAs($invitee);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response->assertForbidden()
            ->assertDontSee('Expired Invitation Wrestling')
            ->assertDontSeeHtml(route('promotions.invitation.accept', $promotion));
    });

    test('the no-membership page lists the invitations addressed to the users email with accept and decline', function () {
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

    test('an invitation saved before the account existed is shown once the account with that email is active', function () {
        // Arrange
        $promotion = Promotion::factory()->create(['name' => 'Invitation Championship Wrestling']);
        PromotionInvitation::factory()->for($promotion)->forEmail('Future.Wrestler@Example.test')->withRole(MembershipRole::Owner)->create();
        $registered = User::factory()->basicUser()->create([
            'email' => 'future.wrestler@example.test',
            'status' => UserStatus::Unverified,
        ]);
        $registered->update(['status' => UserStatus::Active]);
        actingAs($registered);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response->assertForbidden()
            ->assertSee('Invitation Championship Wrestling')
            ->assertSee(__('promotions.invitation_role', ['role' => 'Owner']))
            ->assertSeeHtml(route('promotions.invitation.accept', $promotion));
    });

    test('an invitation is shown whatever the case and whitespace it was typed in', function (string $typed) {
        // Arrange
        $promotion = Promotion::factory()->create(['name' => 'Invitation Championship Wrestling']);
        $invitee = User::factory()->basicUser()->create(['email' => 'typed@example.test', 'status' => UserStatus::Active]);
        PromotionInvitation::factory()->for($promotion)->forEmail($typed)->create();
        actingAs($invitee);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response->assertSee('Invitation Championship Wrestling');
    })->with([
        'upper case' => ['TYPED@EXAMPLE.TEST'],
        'whitespace' => ['  typed@example.test  '],
    ]);

    test('invitations are listed oldest first', function () {
        // Arrange
        $invitee = basicUser();
        $newer = Promotion::factory()->create(['name' => 'Newer Invitation Wrestling']);
        $older = Promotion::factory()->create(['name' => 'Older Invitation Wrestling']);
        inviteTo($older, $invitee);
        travel(1)->day();
        inviteTo($newer, $invitee);
        actingAs($invitee);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response->assertSeeInOrder(['Older Invitation Wrestling', 'Newer Invitation Wrestling']);
    });
});

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

        $response->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', __('promotions.invitation_accepted', ['promotion' => 'Invitation Championship Wrestling', 'role' => $role->label()]));
        expect($membership->status)->toBe(MembershipStatus::Active)
            ->and($membership->role)->toBe($role)
            ->and($promotion->hasMemberWithRole($invitee, $role))->toBeTrue()
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
        $response->assertRedirect(route('dashboard'))
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
            ->and($promotion->hasActiveMember($user))->toBeFalse();
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
        expect($promotion->hasActiveMember($user))->toBeFalse()
            ->and($invitation->fresh())->not->toBeNull();
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
        $otherInvitation = inviteTo($otherPromotion, $invitee);
        $bystanderInvitation = inviteTo($promotion, $bystander);
        actingAs($invitee);

        // Act
        $response = post(route('promotions.invitation.decline', $promotion));

        // Assert
        $response->assertRedirect(route('dashboard'))
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
        expect($promotion->hasMemberWithRole($member, MembershipRole::Owner))->toBeTrue();
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

describe('feedback for a user without a membership', function () {
    test('shows the decline message on the no-membership page', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $invitee = basicUser();
        inviteTo($promotion, $invitee);
        actingAs($invitee);

        // Act
        $response = followingRedirects()->post(route('promotions.invitation.decline', $promotion));

        // Assert
        $response->assertForbidden()
            ->assertSeeHtml('role="status"')
            ->assertSeeText(__('promotions.invitation_declined'));
    });

    test('shows the unavailable message on the no-membership page when accepting fails', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        actingAs(basicUser());

        // Act
        $response = followingRedirects()->post(route('promotions.invitation.accept', $promotion));

        // Assert
        $response->assertForbidden()
            ->assertSeeHtml('role="alert"')
            ->assertSeeText(__('promotions.invitation_unavailable'));
    });
});

describe('promotion existence is not revealed', function () {
    test('a missing promotion answers an invitation action like an existing one without an invitation', function (string $routeName, bool $promotionExists) {
        // Arrange
        $user = basicUser();
        $promotionId = $promotionExists ? Promotion::factory()->create()->id : 999999;
        actingAs($user);

        // Act
        $response = post(route($routeName, ['promotion' => $promotionId]));

        // Assert
        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', __('promotions.invitation_unavailable'));
    })->with([
        'accept, missing' => ['promotions.invitation.accept', false],
        'accept, existing' => ['promotions.invitation.accept', true],
        'decline, missing' => ['promotions.invitation.decline', false],
        'decline, existing' => ['promotions.invitation.decline', true],
    ]);

    test('switching to a missing promotion answers like switching to one the user is not a member of', function (bool $promotionExists) {
        // Arrange
        $user = basicUser();
        $promotionId = $promotionExists ? Promotion::factory()->create()->id : 999999;
        actingAs($user);

        // Act
        $response = post(route('promotions.switch'), ['promotion_id' => $promotionId]);

        // Assert
        $response->assertForbidden();
    })->with([
        'missing' => [false],
        'existing, not a member' => [true],
    ]);
});
