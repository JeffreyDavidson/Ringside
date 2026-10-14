<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
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

require_once __DIR__.'/../../../../Fixtures/promotion-invitations.php';

describe('an invitation grants nothing until it is accepted', function () {
    test('a pending invitation grants no access to the dashboard', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $invitee = basicUser();
        inviteTo($promotion, $invitee, MembershipRole::Owner);

        actingAs($invitee);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response
            ->assertForbidden()
            ->assertViewIs('promotions.no-membership');
    });

    test('a pending invitation grants no access to the wrestler profile', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $invitee = basicUser();
        inviteTo($promotion, $invitee, MembershipRole::Owner);
        $wrestler = Wrestler::factory()->for($promotion)->create();

        actingAs($invitee);

        // Act
        $response = get(route('wrestlers.show', $wrestler));

        // Assert
        $response
            ->assertForbidden()
            ->assertViewIs('promotions.no-membership');
    });

    test('a pending invitation grants no access to the promotion profile', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $invitee = basicUser();
        inviteTo($promotion, $invitee, MembershipRole::Owner);

        actingAs($invitee);

        // Act
        $response = get(route('promotions.show', $promotion));

        // Assert
        $response
            ->assertForbidden()
            ->assertViewIs('promotions.no-membership');
    });

    test('a pending invitation cannot replace the active promotion', function (): void {
        // Arrange
        [$own, $inviting] = Promotion::factory()->count(2)->create()->all();
        $invitee = basicUser();
        joinAs($own, $invitee);
        inviteTo($inviting, $invitee, MembershipRole::Owner);
        $foreignWrestler = Wrestler::factory()->for($inviting, 'promotion')->create();
        withSession(['active_promotion_id' => $inviting->id]);

        actingAs($invitee);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response
            ->assertOk()
            ->assertSessionHas('active_promotion_id', $own->id);

        expect(promotionHasActiveMember($inviting, $invitee))->toBeFalse();
    });

    test('a pending invitation cannot expose a foreign wrestler', function (): void {
        // Arrange
        [$own, $inviting] = Promotion::factory()->count(2)->create()->all();
        $invitee = basicUser();
        joinAs($own, $invitee);
        inviteTo($inviting, $invitee, MembershipRole::Owner);
        $foreignWrestler = Wrestler::factory()->for($inviting, 'promotion')->create();
        withSession(['active_promotion_id' => $inviting->id]);

        actingAs($invitee);

        // Act
        $response = get(route('wrestlers.show', $foreignWrestler));

        // Assert
        $response
            ->assertNotFound();

        expect(promotionHasActiveMember($inviting, $invitee))->toBeFalse();
    });

    test('a pending invitation cannot authorize a foreign promotion page', function (): void {
        // Arrange
        [$own, $inviting] = Promotion::factory()->count(2)->create()->all();
        $invitee = basicUser();
        joinAs($own, $invitee);
        inviteTo($inviting, $invitee, MembershipRole::Owner);
        $foreignWrestler = Wrestler::factory()->for($inviting, 'promotion')->create();
        withSession(['active_promotion_id' => $inviting->id]);

        actingAs($invitee);

        // Act
        $response = get(route('promotions.show', $inviting));

        // Assert
        $response
            ->assertForbidden();

        expect(promotionHasActiveMember($inviting, $invitee))->toBeFalse();
    });

    test('an invited user cannot switch to the inviting promotion', function () {
        // Arrange
        [$own, $inviting] = Promotion::factory()->count(2)->create()->all();
        $invitee = basicUser();
        joinAs($own, $invitee);
        inviteTo($inviting, $invitee);

        actingAs($invitee);

        $requestData = ['promotion_id' => $inviting->id];

        // Act
        $response = post(route('promotions.switch'), $requestData);

        // Assert
        $response
            ->assertForbidden();

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
        $response
            ->assertForbidden()
            ->assertDontSee('Expired Invitation Wrestling')
            ->assertDontSeeHtml(route('promotions.invitation.accept', $promotion));
    });

    test('the no-membership page lists the invitations addressed to the users email with accept and decline', function () {
        // Arrange
        [$promotion, $otherPromotion] = Promotion::factory()
            ->count(2)
            ->sequence(['name' => 'Invitation Championship Wrestling'], ['name' => 'Somebody Elses Wrestling'])
            ->create()
            ->all();
        $invitee = basicUser();
        inviteTo($promotion, $invitee, MembershipRole::Manager);
        inviteTo($otherPromotion, basicUser());

        actingAs($invitee);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response
            ->assertForbidden()
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
        $response
            ->assertSuccessful()
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
        $response
            ->assertSuccessful()
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
        $response
            ->assertForbidden()
            ->assertSee('Invitation Championship Wrestling')
            ->assertSee(__('promotions.invitation_role', ['role' => 'Owner']))
            ->assertSeeHtml(route('promotions.invitation.accept', $promotion));
    });

    test('an invitation is shown whatever the case and whitespace it was typed in', function (string $typed) {
        // Arrange
        $promotion = Promotion::factory()->create(['name' => 'Invitation Championship Wrestling']);

        $invitee = User::factory()->basicUser()->create([
            'email' => 'typed@example.test',
            'status' => UserStatus::Active,
        ]);
        PromotionInvitation::factory()->for($promotion)->forEmail($typed)->create();

        actingAs($invitee);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response
            ->assertSee('Invitation Championship Wrestling');
    })->with([
        'upper case' => ['TYPED@EXAMPLE.TEST'],
        'whitespace' => ['  typed@example.test  '],
    ]);

    test('invitations are listed oldest first', function () {
        // Arrange
        $invitee = basicUser();
        [$newer, $older] = Promotion::factory()
            ->count(2)
            ->sequence(['name' => 'Newer Invitation Wrestling'], ['name' => 'Older Invitation Wrestling'])
            ->create()
            ->all();
        inviteTo($older, $invitee);
        travel(1)->day();
        inviteTo($newer, $invitee);

        actingAs($invitee);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response
            ->assertSeeInOrder(['Older Invitation Wrestling', 'Newer Invitation Wrestling']);
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
        $response
            ->assertForbidden()
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
        $response
            ->assertForbidden()
            ->assertSeeHtml('role="alert"')
            ->assertSeeText(__('promotions.invitation_unavailable'));
    });
});

describe('promotion existence is not revealed', function () {
    test('accept returns unavailable for a missing promotion without an invitation', function (): void {
        // Arrange
        $user = basicUser();
        $promotionId = 999999;

        actingAs($user);

        // Act
        $response = post(route('promotions.invitation.accept', ['promotion' => $promotionId]));

        // Assert
        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', __('promotions.invitation_unavailable'));
    });

    test('accept returns unavailable for a existing promotion without an invitation', function (): void {
        // Arrange
        $user = basicUser();
        $promotionId = Promotion::factory()->create()->id;

        actingAs($user);

        // Act
        $response = post(route('promotions.invitation.accept', ['promotion' => $promotionId]));

        // Assert
        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', __('promotions.invitation_unavailable'));
    });

    test('decline returns unavailable for a missing promotion without an invitation', function (): void {
        // Arrange
        $user = basicUser();
        $promotionId = 999999;

        actingAs($user);

        // Act
        $response = post(route('promotions.invitation.decline', ['promotion' => $promotionId]));

        // Assert
        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', __('promotions.invitation_unavailable'));
    });

    test('decline returns unavailable for a existing promotion without an invitation', function (): void {
        // Arrange
        $user = basicUser();
        $promotionId = Promotion::factory()->create()->id;

        actingAs($user);

        // Act
        $response = post(route('promotions.invitation.decline', ['promotion' => $promotionId]));

        // Assert
        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', __('promotions.invitation_unavailable'));
    });

});
