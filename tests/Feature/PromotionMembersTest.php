<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Livewire\Promotions\Members\Manage;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionMembership;
use App\Models\Users\User;
use Dom\HTMLDocument;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

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

it('invites an existing active account by exact email to only the selected promotion with its selected role', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => UserStatus::Active]);

    // Act
    $component = Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => $user->email, 'newMemberRole' => MembershipRole::Manager->value])
        ->call('addMember');

    // Assert
    $membership = $promotion->memberships()->where('user_id', $user->id)->firstOrFail();

    $component->assertHasNoErrors()->assertSet('email', '');
    expect($membership->role)->toBe(MembershipRole::Manager)
        ->and($membership->status)->toBe(MembershipStatus::Invited)
        ->and($promotion->hasActiveMember($user))->toBeFalse()
        ->and($otherPromotion->memberships()->where('user_id', $user->id)->exists())->toBeFalse()
        ->and($user->promotions()->count())->toBe(1);
});

it('matches the email without case sensitivity and ignores surrounding whitespace', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create([
        'email' => 'global.member@example.test',
        'status' => UserStatus::Active,
    ]);

    // Act
    Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => '  GLOBAL.Member@Example.TEST '])
        ->call('addMember')
        ->assertHasNoErrors();

    // Assert
    expect($promotion->memberships()->where('user_id', $user->id)->firstOrFail()->status)->toBe(MembershipStatus::Invited);
});

it('answers every email with the same message that never names the account', function (string $case) {
    // Arrange
    $promotion = Promotion::factory()->create();
    $active = User::factory()->create([
        'first_name' => 'Secretive',
        'last_name' => 'Person',
        'email' => 'secretive.person@example.test',
        'status' => UserStatus::Active,
    ]);
    $inactive = User::factory()->create(['email' => 'inactive.person@example.test', 'status' => UserStatus::Inactive]);

    $email = match ($case) {
        'existing active account' => $active->email,
        'partial email' => 'secretive',
        'percent wildcard' => '%@example.test',
        'underscore wildcard' => 'secretive_person@example.test',
        'name search' => 'Secretive Person',
        'unknown email' => 'nobody@example.test',
        default => $inactive->email,
    };

    // Act
    $component = Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => $email])
        ->call('addMember');

    // Assert
    $component->assertHasNoErrors()
        ->assertDispatched('flash-message', type: 'status', message: __('promotions.invitation_sent', ['role' => 'Member']))
        ->assertDontSee('Secretive')
        ->assertDontSee('inactive.person@example.test');
    expect($promotion->memberships()->count())->toBe($case === 'existing active account' ? 1 : 0);
})->with([
    'existing active account',
    'partial email',
    'percent wildcard',
    'underscore wildcard',
    'name search',
    'unknown email',
    'inactive user',
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

it('tells the owner when the account already has a membership or a pending invitation', function (MembershipStatus $status) {
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
    expect($promotion->memberships()->count())->toBe(1)
        ->and($promotion->memberships()->firstOrFail()->status)->toBe($status);
})->with([
    'active member' => [MembershipStatus::Active],
    'suspended member' => [MembershipStatus::Suspended],
    'pending invitation (previously unreachable: members were added as active at once)' => [MembershipStatus::Invited],
]);

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
        ->assertDispatched('flash-message', type: 'status', message: 'If that email belongs to an active account, an invitation to join this promotion as Manager was sent. Nobody joins until they accept.');
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

    expect($promotion->hasActiveMember($target))->toBeFalse();
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

    expect($promotion->hasActiveMember($user))->toBeFalse();

    $component->call('updateMemberStatus', $user->id, MembershipStatus::Active->value)
        ->assertHasNoErrors();

    expect($promotion->memberships()->count())->toBe(1)
        ->and($promotion->memberships()->where('user_id', $user->id)->firstOrFail()->role)
        ->toBe(MembershipRole::Manager)
        ->and($promotion->hasActiveMember($user))->toBeTrue();
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
        ->call('updateMemberStatus', $user->id, MembershipStatus::Invited->value)
        ->assertHasErrors('status');

    expect($promotion->hasActiveMember($user))->toBeTrue();
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
    /** @param array<string, string> $attributes */
    function invitePending(Promotion $promotion, array $attributes = [], MembershipRole $role = MembershipRole::Manager): User
    {
        $user = User::factory()->create(['status' => UserStatus::Active, ...$attributes]);
        $promotion->users()->attach($user, ['role' => $role, 'status' => MembershipStatus::Invited]);

        return $user;
    }

    it('lists a pending invitation by the email only, never by the name, and keeps it out of the member list', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        invitePending($promotion, ['first_name' => 'Hidden', 'last_name' => 'Invitee', 'email' => 'hidden.invitee@example.test']);

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

    it('shows pending invitations only to people who can manage members', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        invitePending($promotion, ['email' => 'hidden.invitee@example.test']);
        $member = basicUser();
        $promotion->users()->attach($member, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);

        // Act
        $component = Livewire::actingAs($member)
            ->test(Manage::class, ['promotionId' => $promotion->id]);

        // Assert
        $component->assertDontSee('hidden.invitee@example.test')
            ->assertDontSee(__('promotions.invitations_title'));
    });

    it('lets an owner cancel a pending invitation without naming the account', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $invitee = invitePending($promotion, ['first_name' => 'Hidden', 'last_name' => 'Invitee']);
        $otherPromotion = Promotion::factory()->create();
        $otherPromotion->users()->attach($invitee, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Invited]);

        // Act
        $component = Livewire::actingAs(administrator())
            ->test(Manage::class, ['promotionId' => $promotion->id])
            ->call('cancelInvitation', $invitee->id);

        // Assert
        $component->assertHasNoErrors()
            ->assertDispatched('flash-message', type: 'status', message: __('promotions.invitation_cancelled'))
            ->assertDontSee(__('promotions.invitations_title'));
        expect($promotion->memberships()->count())->toBe(0)
            ->and($otherPromotion->memberships()->where('user_id', $invitee->id)->exists())->toBeTrue();
    });

    it('does not let a non-owner cancel an invitation', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $invitee = invitePending($promotion);
        $manager = basicUser();
        $promotion->users()->attach($manager, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);

        // Act
        $component = Livewire::actingAs($manager)
            ->test(Manage::class, ['promotionId' => $promotion->id])
            ->call('cancelInvitation', $invitee->id);

        // Assert
        $component->assertForbidden();
        expect($promotion->memberships()->where('user_id', $invitee->id)->exists())->toBeTrue();
    });

    it('cannot cancel an active member or an invitation of another promotion by forging an id', function (string $case) {
        // Arrange
        $promotion = Promotion::factory()->create();
        $otherPromotion = Promotion::factory()->create();
        $target = User::factory()->create(['status' => UserStatus::Active]);

        if ($case === 'active member') {
            $promotion->users()->attach($target, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
        } else {
            $otherPromotion->users()->attach($target, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Invited]);
        }

        // Act
        $component = Livewire::actingAs(administrator())
            ->test(Manage::class, ['promotionId' => $promotion->id])
            ->call('cancelInvitation', $target->id);

        // Assert
        $component->assertNotFound();
        expect(PromotionMembership::query()->where('user_id', $target->id)->count())->toBe(1);
    })->with(['active member', 'invitation of another promotion']);

    it('never lets an owner activate or re-role a pending invitation behind the invited accounts back', function (string $action) {
        // Arrange
        $promotion = Promotion::factory()->create();
        $invitee = invitePending($promotion, [], MembershipRole::Member);

        // Act
        $component = Livewire::actingAs(administrator())
            ->test(Manage::class, ['promotionId' => $promotion->id])
            ->set("memberRoles.{$invitee->id}", MembershipRole::Owner->value);

        match ($action) {
            'activate' => $component->call('updateMemberStatus', $invitee->id, MembershipStatus::Active->value),
            default => $component->call('updateMemberRole', $invitee->id),
        };

        // Assert
        $membership = $promotion->memberships()->where('user_id', $invitee->id)->firstOrFail();

        $component->assertNotFound();
        expect($membership->status)->toBe(MembershipStatus::Invited)
            ->and($membership->role)->toBe(MembershipRole::Member)
            ->and($promotion->hasActiveMember($invitee))->toBeFalse();
    })->with(['activate', 'change role']);

    it('counts only joined members on the promotion page', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        invitePending($promotion);
        $member = User::factory()->create(['status' => UserStatus::Active]);
        $promotion->users()->attach($member, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);

        // Act
        $response = actingAs(administrator())->get(route('promotions.show', $promotion));

        // Assert
        $response->assertOk()->assertViewHas('promotion', fn (Promotion $shown): bool => $shown->memberships_count === 1);
    });
});
