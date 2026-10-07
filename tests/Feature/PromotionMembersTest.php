<?php

declare(strict_types=1);

use App\Actions\Promotions\AcceptPromotionInvitationAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Livewire\Promotions\Members\Manage;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;
use App\Models\Users\User;
use Dom\HTMLDocument;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\travel;

it('shows a promotion detail page and its member manager to platform administrators', function () {
    $promotion = Promotion::factory()->create(['name' => 'Ringside Wrestling']);

    actingAs(administrator())
        ->get(route('promotions.show', $promotion))
        ->assertOk()
        ->assertViewIs('promotions.show')
        ->assertSee('Ringside Wrestling')
        ->assertSeeHtml('data-test="promotion-members-loading-placeholder"')
        ->assertSeeLivewire(Manage::class);
});

it('keeps promotion membership management unavailable to regular users', function () {
    $promotion = Promotion::factory()->create();

    actingAs(basicUser())
        ->get(route('promotions.show', $promotion))
        ->assertForbidden();
});

it('redirects guests to login from the promotion member page', function () {
    $promotion = Promotion::factory()->create();

    get(route('promotions.show', $promotion))
        ->assertRedirect(route('login'));
});

it('saves an invitation for the typed email to only the selected promotion with its selected role', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => UserStatus::Active]);

    // Act
    $component = Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => $user->email, 'newMemberRole' => MembershipRole::Manager->value])
        ->call('addMember');

    // Assert
    $invitation = $promotion->invitations()->sole();

    $component->assertHasNoErrors()->assertSet('email', '');
    expect($invitation->email)->toBe($user->email)
        ->and($invitation->role)->toBe(MembershipRole::Manager)
        ->and($promotion->memberships()->count())->toBe(0)
        ->and(promotionHasActiveMember($promotion, $user))->toBeFalse()
        ->and($otherPromotion->invitations()->count())->toBe(0)
        ->and($user->promotions()->count())->toBe(0);
});

it('stores the email trimmed and lowercase and treats other spellings of it as the same invitation', function () {
    // Arrange
    $promotion = Promotion::factory()->create();

    // Act
    $component = Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => '  GLOBAL.Member@Example.TEST '])
        ->call('addMember')
        ->assertHasNoErrors()
        ->set('email', 'global.member@example.test')
        ->call('addMember');

    // Assert
    $component->assertHasErrors(['email'])
        ->assertSee(__('promotions.invitation_already_pending'));
    expect($promotion->invitations()->sole()->email)->toBe('global.member@example.test');
});

it('answers every email with the same message and a pending row, whether or not an account exists', function (string $case) {
    // Arrange
    $promotion = Promotion::factory()->create();
    $active = User::factory()->create([
        'first_name' => 'Secretive',
        'last_name' => 'Person',
        'email' => 'secretive.person@example.test',
        'status' => UserStatus::Active,
    ]);
    $inactive = User::factory()->create(['first_name' => 'Dormant', 'last_name' => 'Person', 'email' => 'inactive.person@example.test', 'status' => UserStatus::Inactive]);
    $unverified = User::factory()->create(['first_name' => 'Unverified', 'last_name' => 'Person', 'email' => 'unverified.person@example.test', 'status' => UserStatus::Unverified]);

    $email = match ($case) {
        'active account' => $active->email,
        'inactive account' => $inactive->email,
        'unverified account' => $unverified->email,
        default => 'nobody@example.test',
    };

    // Act
    $component = Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => $email])
        ->call('addMember');

    // Assert
    $component->assertHasNoErrors()
        ->assertDispatched('flash-message', type: 'status', message: __('promotions.invitation_sent', ['role' => 'Member']))
        ->assertSee($email)
        ->assertDontSee('Secretive')
        ->assertDontSee('Dormant')
        ->assertDontSee('Unverified Person');
    expect($promotion->invitations()->sole()->email)->toBe($email)
        ->and($promotion->memberships()->count())->toBe(0);
})->with([
    'active account',
    'inactive account',
    'unverified account',
    'unknown email',
]);

it('rejects text that is not an email address and stores nothing', function (string $value) {
    // Arrange
    $promotion = Promotion::factory()->create();

    // Act
    $component = Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => $value])
        ->call('addMember');

    // Assert
    $component->assertHasErrors('email')->assertNotDispatched('flash-message');
    expect($promotion->invitations()->count())->toBe(0);
})->with([
    'partial email' => ['secretive'],
    'name search' => ['Secretive Person'],
]);

it('never puts the invited accounts full name in the message the owner sees', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['first_name' => 'Ann', 'last_name' => "D'Arcy", 'status' => UserStatus::Active]);

    // Act
    $component = Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => $user->email])
        ->call('addMember');

    // Assert
    $component->assertDispatched('flash-message', fn (string $event, array $params): bool => ! str_contains($params['message'], 'Ann')
        && ! str_contains($params['message'], "D'Arcy"))
        ->assertDontSee("D'Arcy");
});

it('tells the owner when the email already belongs to a member of the promotion', function (MembershipStatus $status) {
    // Arrange
    $promotion = Promotion::factory()->create();
    $existing = User::factory()->create(['email' => 'existing.member@example.test', 'status' => UserStatus::Active]);
    $promotion->users()->attach($existing, [
        'role' => MembershipRole::Member,
        'status' => $status,
    ]);

    // Act
    $component = Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => 'Existing.Member@example.test'])
        ->call('addMember');

    // Assert
    $component->assertHasErrors(['email'])
        ->assertSee(__('promotions.member_already_added'))
        ->assertNotDispatched('flash-message');
    expect($promotion->invitations()->count())->toBe(0)
        ->and($promotion->memberships()->sole()->status)->toBe($status);
})->with([
    'active member' => [MembershipStatus::Active],
    'suspended member' => [MembershipStatus::Suspended],
]);

it('tells the owner when the email already has a pending invitation and keeps the stored one', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    PromotionInvitation::factory()->for($promotion)->forEmail('pending@example.test')->withRole(MembershipRole::Member)->create();

    // Act
    $component = Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => 'Pending@example.test', 'newMemberRole' => MembershipRole::Owner->value])
        ->call('addMember');

    // Assert
    $component->assertHasErrors(['email'])
        ->assertSee(__('promotions.invitation_already_pending'))
        ->assertNotDispatched('flash-message');
    expect($promotion->invitations()->sole()->role)->toBe(MembershipRole::Member);
});

it('clears an earlier email error once an invitation is sent', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $existing = User::factory()->create(['status' => UserStatus::Active]);
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $promotion->users()->attach($existing, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);

    // Act
    $component = Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => $existing->email])
        ->call('addMember')
        ->assertHasErrors(['email'])
        ->set('email', $user->email)
        ->set('newMemberRole', MembershipRole::Manager->value)
        ->call('addMember');

    // Assert
    $component->assertHasNoErrors()
        ->assertDispatched('flash-message', type: 'status', message: __('promotions.invitation_sent', ['role' => 'Manager']));
});

it('links the email error to the email input', function () {
    $promotion = Promotion::factory()->create();
    $existing = User::factory()->create(['status' => UserStatus::Active]);
    $promotion->users()->attach($existing, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);

    $html = Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => $existing->email])
        ->call('addMember')
        ->html();

    $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
    $input = $document->getElementById('promotion-member-email');
    $error = $document->getElementById('promotion-member-email-error');

    expect($input?->getAttribute('aria-describedby'))->toBe('promotion-member-email-help promotion-member-email-error')
        ->and($input?->getAttribute('aria-invalid'))->toBe('true')
        ->and($error?->textContent)->toContain(__('promotions.member_already_added'));
});

it('labels roles and membership statuses with their enum labels', function () {
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $promotion->users()->attach($user, [
        'role' => MembershipRole::Manager,
        'status' => MembershipStatus::Suspended,
    ]);

    Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id])
        ->assertSeeHtmlInOrder([
            '<option value="owner">',
            MembershipRole::Owner->label(),
            '<option value="manager">',
            MembershipRole::Manager->label(),
            '<option value="member"',
            MembershipRole::Member->label(),
        ])
        ->assertSee(MembershipStatus::Suspended->label());
});

it('requires an email and a valid role to add a member', function (string $email, string $role, string $field) {
    $promotion = Promotion::factory()->create();

    Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => $email, 'newMemberRole' => $role])
        ->call('addMember')
        ->assertHasErrors($field);
})->with([
    'missing email' => ['', 'member', 'email'],
    'invalid role' => ['someone@example.test', 'superuser', 'role'],
    'invalid email' => ['someone', 'member', 'email'],
]);

it('does not let a non-owner add members by email', function () {
    $promotion = Promotion::factory()->create();
    $manager = basicUser();
    $target = User::factory()->create(['status' => UserStatus::Active]);
    $promotion->users()->attach($manager, [
        'role' => MembershipRole::Manager,
        'status' => MembershipStatus::Active,
    ]);

    Livewire::actingAs($manager)
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => $target->email])
        ->call('addMember')
        ->assertForbidden();

    expect(promotionHasActiveMember($promotion, $target))->toBeFalse()
        ->and($promotion->invitations()->count())->toBe(0);
});

it('never lists other users in the member manager', function () {
    $promotion = Promotion::factory()->create();
    User::factory()->create(['first_name' => 'Hidden', 'last_name' => 'Account', 'email' => 'hidden.account@example.test', 'status' => UserStatus::Active]);

    Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => 'hidden'])
        ->assertDontSee('Hidden Account')
        ->assertDontSee('hidden.account@example.test');
});

it('updates a member role without changing membership in another promotion', function () {
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => UserStatus::Active]);

    $promotion->users()->attach($user, [
        'role' => MembershipRole::Member,
        'status' => MembershipStatus::Active,
    ]);
    $otherPromotion->users()->attach($user, [
        'role' => MembershipRole::Manager,
        'status' => MembershipStatus::Active,
    ]);

    Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id])
        ->set("memberRoles.{$user->id}", MembershipRole::Owner->value)
        ->call('updateMemberRole', $user->id)
        ->assertHasNoErrors()
        ->assertDispatched('flash-message', type: 'status', message: "{$user->refresh()->full_name}’s promotion role is now Owner.");

    expect($promotion->memberships()->where('user_id', $user->id)->firstOrFail()->role)
        ->toBe(MembershipRole::Owner)
        ->and($otherPromotion->memberships()->where('user_id', $user->id)->firstOrFail()->role)
        ->toBe(MembershipRole::Manager);
});

it('suspends and reactivates a member without deleting their promotion relationship', function () {
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => UserStatus::Active]);

    $promotion->users()->attach($user, [
        'role' => MembershipRole::Manager,
        'status' => MembershipStatus::Active,
    ]);

    $component = Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id])
        ->call('updateMemberStatus', $user->id, MembershipStatus::Suspended->value)
        ->assertHasNoErrors();

    expect(promotionHasActiveMember($promotion, $user))->toBeFalse();

    $component->call('updateMemberStatus', $user->id, MembershipStatus::Active->value)
        ->assertHasNoErrors();

    expect($promotion->memberships()->count())->toBe(1)
        ->and($promotion->memberships()->where('user_id', $user->id)->firstOrFail()->role)
        ->toBe(MembershipRole::Manager)
        ->and(promotionHasActiveMember($promotion, $user))->toBeTrue();
});

it('does not allow membership status changes outside active and suspended states', function () {
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => UserStatus::Active]);

    $promotion->users()->attach($user, [
        'role' => MembershipRole::Member,
        'status' => MembershipStatus::Active,
    ]);

    Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id])
        ->call('updateMemberStatus', $user->id, 'invited')
        ->assertHasErrors('status');

    expect(promotionHasActiveMember($promotion, $user))->toBeTrue();
});

it('keeps the last active owner when changing roles or status', function (string $change) {
    $promotion = Promotion::factory()->create();
    $owner = User::factory()->create(['status' => UserStatus::Active]);
    $promotion->users()->attach($owner, [
        'role' => MembershipRole::Owner,
        'status' => MembershipStatus::Active,
    ]);

    $component = Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id])
        ->set("memberRoles.{$owner->id}", MembershipRole::Manager->value);

    match ($change) {
        'demote' => $component->call('updateMemberRole', $owner->id),
        default => $component->call('updateMemberStatus', $owner->id, MembershipStatus::Suspended->value),
    };

    $component->assertHasErrors('member')
        ->assertSee('at least one active owner');

    $membership = $promotion->memberships()->where('user_id', $owner->id)->firstOrFail();

    expect($membership->role)->toBe(MembershipRole::Owner)
        ->and($membership->status)->toBe(MembershipStatus::Active);
})->with(['demote', 'suspend']);

it('restores the role selector after a rejected demotion', function () {
    $promotion = Promotion::factory()->create();
    $owner = User::factory()->create(['status' => UserStatus::Active]);
    $promotion->users()->attach($owner, [
        'role' => MembershipRole::Owner,
        'status' => MembershipStatus::Active,
    ]);

    Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id])
        ->set("memberRoles.{$owner->id}", MembershipRole::Member->value)
        ->call('updateMemberRole', $owner->id)
        ->assertSet("memberRoles.{$owner->id}", MembershipRole::Owner->value);
});

it('allows demoting an owner when another active owner remains', function () {
    $promotion = Promotion::factory()->create();
    $owner = User::factory()->create(['status' => UserStatus::Active]);
    $otherOwner = User::factory()->create(['status' => UserStatus::Active]);
    $promotion->users()->attach([$owner->id, $otherOwner->id], [
        'role' => MembershipRole::Owner,
        'status' => MembershipStatus::Active,
    ]);

    Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id])
        ->set("memberRoles.{$owner->id}", MembershipRole::Manager->value)
        ->call('updateMemberRole', $owner->id)
        ->assertHasNoErrors();

    expect($promotion->memberships()->where('user_id', $owner->id)->firstOrFail()->role)
        ->toBe(MembershipRole::Manager);
});

it('clears an earlier email error once a role is saved', function () {
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $promotion->users()->attach($user, [
        'role' => MembershipRole::Member,
        'status' => MembershipStatus::Active,
    ]);

    Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => $user->email])
        ->call('addMember')
        ->assertHasErrors(['email'])
        ->set("memberRoles.{$user->id}", MembershipRole::Manager->value)
        ->call('updateMemberRole', $user->id)
        ->assertHasNoErrors()
        ->assertDontSee(__('promotions.member_already_added'));
});

it('confirms suspending and reactivating a member', function () {
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['first_name' => 'Ann', 'last_name' => "D'Arcy", 'status' => UserStatus::Active]);
    $promotion->users()->attach($user, [
        'role' => MembershipRole::Member,
        'status' => MembershipStatus::Active,
    ]);

    $component = Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id]);

    $component->call('updateMemberStatus', $user->id, MembershipStatus::Suspended->value)
        ->assertDispatched('flash-message', type: 'status', message: "Ann D'Arcy can no longer access this promotion.");

    $component->call('updateMemberStatus', $user->id, MembershipStatus::Active->value)
        ->assertDispatched('flash-message', type: 'status', message: "Ann D'Arcy can access this promotion again.");
});

describe('pending invitations', function () {
    it('lists a pending invitation by the email only, and keeps it out of the member list', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        User::factory()->create(['first_name' => 'Hidden', 'last_name' => 'Invitee', 'email' => 'hidden.invitee@example.test', 'status' => UserStatus::Active]);
        PromotionInvitation::factory()->for($promotion)->forEmail('hidden.invitee@example.test')->withRole(MembershipRole::Manager)->create();

        // Act
        $component = Livewire::actingAs(administrator())
            ->test(Manage::class, ['promotionId' => $promotion->id]);

        // Assert
        $component->assertSee(__('promotions.invitations_title'))
            ->assertSee('hidden.invitee@example.test')
            ->assertSee(__('promotions.invitation_pending'))
            ->assertSee(MembershipRole::Manager->label())
            ->assertDontSee('Hidden Invitee')
            ->assertDontSee('Hidden')
            ->assertSee(trans_choice('promotions.member_count', 0, ['count' => 0]))
            ->assertSee(__('promotions.no_members'));
    });

    it('lists invitations oldest first', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        PromotionInvitation::factory()->for($promotion)->forEmail('m.second@example.test')->create();
        travel(-1)->day();
        PromotionInvitation::factory()->for($promotion)->forEmail('z.first@example.test')->create();

        // Act
        $component = Livewire::actingAs(administrator())
            ->test(Manage::class, ['promotionId' => $promotion->id]);

        // Assert
        $component->assertSeeInOrder(['z.first@example.test', 'm.second@example.test']);
    });

    it('shows pending invitations only to people who can manage members', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        PromotionInvitation::factory()->for($promotion)->forEmail('hidden.invitee@example.test')->create();
        $member = basicUser();
        $promotion->users()->attach($member, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);

        // Act
        $component = Livewire::actingAs($member)
            ->test(Manage::class, ['promotionId' => $promotion->id]);

        // Assert
        $component->assertDontSee('hidden.invitee@example.test')
            ->assertDontSee(__('promotions.invitations_title'));
    });

    it('lets an owner cancel a pending invitation', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $otherPromotion = Promotion::factory()->create();
        $invitation = PromotionInvitation::factory()->for($promotion)->forEmail('cancel.me@example.test')->create();
        $kept = PromotionInvitation::factory()->for($promotion)->forEmail('keep.me@example.test')->create();
        $otherPromotionInvitation = PromotionInvitation::factory()->for($otherPromotion)->forEmail('cancel.me@example.test')->create();

        // Act
        $component = Livewire::actingAs(administrator())
            ->test(Manage::class, ['promotionId' => $promotion->id])
            ->call('cancelInvitation', $invitation->id);

        // Assert
        $component->assertHasNoErrors()
            ->assertDispatched('flash-message', type: 'status', message: __('promotions.invitation_cancelled'))
            ->assertDontSee('cancel.me@example.test')
            ->assertSee('keep.me@example.test');
        expect($promotion->invitations()->pluck('id')->all())->toBe([$kept->id])
            ->and($otherPromotionInvitation->fresh())->not->toBeNull();
    });

    it('does not let a non-owner cancel an invitation', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $invitation = PromotionInvitation::factory()->for($promotion)->create();
        $manager = basicUser();
        $promotion->users()->attach($manager, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);

        // Act
        $component = Livewire::actingAs($manager)
            ->test(Manage::class, ['promotionId' => $promotion->id])
            ->call('cancelInvitation', $invitation->id);

        // Assert
        $component->assertForbidden();
        expect($invitation->fresh())->not->toBeNull();
    });

    it('cannot cancel the invitation of another promotion by forging its id', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $otherPromotion = Promotion::factory()->create();
        $foreign = PromotionInvitation::factory()->for($otherPromotion)->create();

        // Act
        $component = Livewire::actingAs(administrator())
            ->test(Manage::class, ['promotionId' => $promotion->id])
            ->call('cancelInvitation', $foreign->id);

        // Assert
        $component->assertNotFound();
        expect($foreign->fresh())->not->toBeNull();
    });

    it('cannot activate or re-role a pending invitation as if it were a member', function (string $action) {
        // Arrange
        $promotion = Promotion::factory()->create();
        $invitee = User::factory()->create(['email' => 'invitee@example.test', 'status' => UserStatus::Active]);
        $invitation = PromotionInvitation::factory()->for($promotion)->forEmail('invitee@example.test')->withRole(MembershipRole::Member)->create();

        // Act
        $component = Livewire::actingAs(administrator())
            ->test(Manage::class, ['promotionId' => $promotion->id])
            ->set("memberRoles.{$invitee->id}", MembershipRole::Owner->value);

        match ($action) {
            'activate' => $component->call('updateMemberStatus', $invitee->id, MembershipStatus::Active->value),
            default => $component->call('updateMemberRole', $invitee->id),
        };

        // Assert
        $component->assertNotFound();
        expect($invitation->fresh()?->role)->toBe(MembershipRole::Member)
            ->and($promotion->memberships()->count())->toBe(0);
    })->with(['activate', 'change role']);

    it('counts only members on the promotion page, not invitations', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        PromotionInvitation::factory()->for($promotion)->create();
        $member = User::factory()->create(['status' => UserStatus::Active]);
        $promotion->users()->attach($member, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);

        // Act
        $response = actingAs(administrator())->get(route('promotions.show', $promotion));

        // Assert
        $response->assertOk()->assertViewHas('promotion', fn (Promotion $shown): bool => $shown->memberships_count === 1);
    });
});

it('lists only pending invitations and shows when each expires in the promotion time zone', function () {
    // Arrange
    $promotion = Promotion::factory()->create(['timezone' => 'Pacific/Auckland']);
    PromotionInvitation::factory()->for($promotion)->forEmail('pending@example.test')->create(['expires_at' => '2026-11-05 20:00:00']);
    PromotionInvitation::factory()->for($promotion)->forEmail('gone@example.test')->expired()->create();

    // Act
    $component = Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id]);

    // Assert
    $component->assertSee('pending@example.test')
        ->assertDontSee('gone@example.test')
        ->assertSee(__('promotions.invitation_expires', ['date' => 'Nov 6, 2026']))
        ->assertDontSee('Nov 5, 2026');
});

it('lets the owner invite again once the earlier invitation has expired', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    PromotionInvitation::factory()->for($promotion)->forEmail('late@example.test')->withRole(MembershipRole::Member)->expired()->create();

    // Act
    $component = Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => 'late@example.test', 'newMemberRole' => MembershipRole::Manager->value])
        ->call('addMember');

    // Assert
    $component->assertHasNoErrors();
    expect($promotion->invitations()->sole()->role)->toBe(MembershipRole::Manager);
});

it('rejects an email longer than 255 characters and stores nothing', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $email = str_repeat('a', 250).'@example.test';

    // Act
    $component = Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => $email])
        ->call('addMember');

    // Assert
    $component->assertHasErrors(['email' => 'max']);
    expect($promotion->invitations()->count())->toBe(0);
});

it('lists invitations created at the same moment in id order', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $createdAt = now()->startOfSecond();
    PromotionInvitation::factory()->for($promotion)->forEmail('z.lower.id@example.test')->create(['id' => 10, 'created_at' => $createdAt]);
    PromotionInvitation::factory()->for($promotion)->forEmail('a.higher.id@example.test')->create(['id' => 20, 'created_at' => $createdAt]);

    // Act
    $component = Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id]);

    // Assert
    $component->assertSeeInOrder(['z.lower.id@example.test', 'a.higher.id@example.test']);
});

it('clears earlier email and member errors when an invitation is cancelled', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $invitation = PromotionInvitation::factory()->for($promotion)->forEmail('pending@example.test')->create();
    $component = Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => 'pending@example.test'])
        ->call('addMember')
        ->assertHasErrors(['email']);
    $component->instance()->addError('member', 'A member error.');

    // Act
    $component->call('cancelInvitation', $invitation->id);

    // Assert
    $component->assertHasNoErrors(['email', 'member']);
});

it('adds a role entry for a member who joined after the page was opened', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $owner = User::factory()->create(['status' => UserStatus::Active]);
    $promotion->users()->attach($owner, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
    $joiner = User::factory()->create(['email' => 'joiner@example.test', 'status' => UserStatus::Active]);
    PromotionInvitation::factory()->for($promotion)->forEmail($joiner->email)->withRole(MembershipRole::Manager)->create();
    $component = Livewire::actingAs($owner)
        ->test(Manage::class, ['promotionId' => $promotion->id])
        ->set("memberRoles.{$owner->id}", MembershipRole::Manager->value);

    // Act
    app(AcceptPromotionInvitationAction::class)->handle($promotion, $joiner);
    $component->call('$refresh')
        ->call('updateMemberRole', $joiner->id);

    // Assert
    $component->assertHasNoErrors()
        ->assertSet("memberRoles.{$joiner->id}", MembershipRole::Manager->value)
        ->assertSet("memberRoles.{$owner->id}", MembershipRole::Manager->value);
});

describe('invitation rate limit', function () {
    it('allows 30 invitations an hour per promotion and refuses the 31st', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        RateLimiter::clear("promotion-invitations:{$promotion->id}");
        $component = Livewire::actingAs(administrator())
            ->test(Manage::class, ['promotionId' => $promotion->id]);

        // Act
        foreach (range(1, 30) as $number) {
            $component->set('email', "invitee{$number}@example.test")
                ->call('addMember')
                ->assertHasNoErrors();
        }

        $component->set('email', 'one.too.many@example.test')
            ->call('addMember');

        // Assert
        $component->assertHasErrors(['email'])
            ->assertSee(trans_choice('promotions.invitation_rate_limited', 60, ['minutes' => 60]));
        expect($promotion->invitations()->count())->toBe(30);
    });

    it('does not count attempts that fail validation', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        RateLimiter::clear("promotion-invitations:{$promotion->id}");
        $component = Livewire::actingAs(administrator())
            ->test(Manage::class, ['promotionId' => $promotion->id]);

        // Act
        foreach (range(1, 31) as $ignored) {
            $component->set('email', 'not-an-email')->call('addMember');
        }

        $component->set('email', 'valid@example.test')->call('addMember');

        // Assert
        $component->assertHasNoErrors();
        expect($promotion->invitations()->count())->toBe(1);
    });

    it('limits each promotion separately', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $other = Promotion::factory()->create();
        RateLimiter::clear("promotion-invitations:{$promotion->id}");
        RateLimiter::clear("promotion-invitations:{$other->id}");

        foreach (range(1, 30) as $ignored) {
            RateLimiter::hit("promotion-invitations:{$promotion->id}", 3600);
        }

        // Act
        $blocked = Livewire::actingAs(administrator())
            ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => 'blocked@example.test'])
            ->call('addMember');
        $allowed = Livewire::actingAs(administrator())
            ->test(Manage::class, ['promotionId' => $other->id, 'email' => 'allowed@example.test'])
            ->call('addMember');

        // Assert
        $blocked->assertHasErrors(['email']);
        $allowed->assertHasNoErrors();
        expect($promotion->invitations()->count())->toBe(0)
            ->and($other->invitations()->count())->toBe(1);
    });
});

/**
 * @return array{Promotion, Collection<int, User>}
 */
function promotionWithMembers(int $count): array
{
    $promotion = Promotion::factory()->create();
    $users = User::factory()->count($count)->create(['status' => UserStatus::Active]);

    foreach ($users as $index => $user) {
        $promotion->users()->attach($user, [
            'role' => MembershipRole::Member,
            'status' => MembershipStatus::Active,
            'created_at' => now()->addMinutes($index),
        ]);
    }

    return [$promotion, $users];
}

describe('member pagination', function () {
    it('shows 25 members on the first page and the rest on the second', function () {
        // Arrange
        [$promotion, $users] = promotionWithMembers(30);
        $component = Livewire::actingAs(administrator())
            ->test(Manage::class, ['promotionId' => $promotion->id]);

        // Act
        $first = $component->viewData('members')->pluck('user_id')->all();
        $component->call('nextPage');
        $second = $component->viewData('members')->pluck('user_id')->all();

        // Assert
        expect($first)->toHaveCount(25)
            ->and($second)->toHaveCount(5)
            ->and([...$first, ...$second])->toBe($users->pluck('id')->all());
    });

    it('changes the role of a member on the second page', function () {
        // Arrange
        [$promotion, $users] = promotionWithMembers(30);
        $member = $users->sortByDesc('id')->firstOrFail();
        $component = Livewire::actingAs(administrator())
            ->test(Manage::class, ['promotionId' => $promotion->id])
            ->call('nextPage');

        // Act
        $component->set("memberRoles.{$member->id}", MembershipRole::Manager->value)
            ->call('updateMemberRole', $member->id);

        // Assert
        $component->assertHasNoErrors();
        expect($promotion->memberships()->where('user_id', $member->id)->firstOrFail()->role)->toBe(MembershipRole::Manager);
    });

    it('builds the role options once and reuses them for every select', function () {
        // Arrange
        [$promotion] = promotionWithMembers(3);
        $component = Livewire::actingAs(administrator())
            ->test(Manage::class, ['promotionId' => $promotion->id]);

        // Act
        $options = $component->viewData('roleOptions');

        // Assert
        expect($options)->toBe(collect(MembershipRole::cases())->mapWithKeys(fn (MembershipRole $role): array => [$role->value => $role->label()])->all());
    });
});
