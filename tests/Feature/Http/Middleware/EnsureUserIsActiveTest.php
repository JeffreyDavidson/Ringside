<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withSession;

test('signed in users who are not active are logged out with a status message', function (UserStatus $status, string $messageKey): void {
    // Arrange
    $user = User::factory()
        ->create(['status' => UserStatus::Active]);

    actingAs($user);
    withSession(['private_session_marker' => 'account-only']);
    $user->forceFill(['status' => $status])->save();

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', __($messageKey))
        ->assertSessionMissing('private_session_marker');

    assertGuest();
})->with([
    'unverified' => [UserStatus::Unverified, 'auth-forms.account_pending'],
    'inactive' => [UserStatus::Inactive, 'auth-forms.account_inactive'],
]);

test('signed in users whose account no longer exists are logged out as inactive', function (): void {
    // Arrange
    $user = User::factory()
        ->create(['status' => UserStatus::Active]);

    actingAs($user);
    withSession(['private_session_marker' => 'account-only']);
    $user->forceDelete();

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', __('auth-forms.account_inactive'))
        ->assertSessionMissing('private_session_marker');

    assertGuest();
});

test('active users continue to the requested page', function (): void {
    // Arrange
    $user = administrator();
    actingAs($user);

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response
        ->assertOk();

    assertAuthenticatedAs($user);
});

test('guests continue to public pages without being logged out', function (): void {
    // Arrange
    withSession(['public_session_marker' => 'guest-preference']);

    // Act
    $response = get(route('login'));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('auth.login')
        ->assertSessionHas('public_session_marker', 'guest-preference');

    assertGuest();
});

describe('invitation acceptance writes', function (): void {
    test('active users can accept their valid invitation and create a membership', function (): void {
        // Arrange
        $user = basicUser();
        $promotion = Promotion::factory()
            ->create();
        $invitation = PromotionInvitation::factory()
            ->for($promotion)
            ->forEmail($user->email)
            ->withRole(MembershipRole::Member)
            ->create();

        actingAs($user);

        // Act
        $response = post(route('promotions.invitation.accept', $promotion));

        // Assert
        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', __('promotions.invitation_accepted', [
                'promotion' => $promotion->name,
                'role' => MembershipRole::Member->label(),
            ]));

        assertAuthenticatedAs($user);
        assertDatabaseHas('promotion_user', [
            'promotion_id' => $promotion->id,
            'user_id' => $user->id,
            'role' => MembershipRole::Member->value,
            'status' => MembershipStatus::Active->value,
        ]);
        assertModelMissing($invitation);
    });

    test('a user made inactive cannot accept a valid invitation or create a membership', function (): void {
        // Arrange
        $user = basicUser();
        $promotion = Promotion::factory()
            ->create();
        $invitation = PromotionInvitation::factory()
            ->for($promotion)
            ->forEmail($user->email)
            ->withRole(MembershipRole::Member)
            ->create();
        $invitation->refresh();
        $originalInvitation = $invitation->getRawOriginal();

        actingAs($user);
        withSession(['private_session_marker' => 'account-only']);
        User::query()
            ->whereKey($user->id)
            ->update(['status' => UserStatus::Inactive]);

        // Act
        $response = post(route('promotions.invitation.accept', $promotion));

        // Assert
        $invitation->refresh();

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', __('auth-forms.account_inactive'))
            ->assertSessionMissing('private_session_marker')
            ->assertSessionMissing('active_promotion_id');

        assertGuest();
        assertModelExists($invitation);
        assertDatabaseCount('promotion_user', 0);
        assertDatabaseHas('users', ['id' => $user->id, 'status' => UserStatus::Inactive->value]);
        expect($invitation->getRawOriginal())->toBe($originalInvitation);
    });
});
