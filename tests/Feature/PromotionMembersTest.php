<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Livewire\Promotions\Members\Manage;
use App\Models\Promotions\Promotion;
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

it('adds an existing active account by exact email to only the selected promotion with its selected role', function () {
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => UserStatus::Active]);

    Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => $user->email, 'newMemberRole' => MembershipRole::Manager->value])
        ->call('addMember')
        ->assertHasNoErrors()
        ->assertSet('email', '');

    $membership = $promotion->memberships()->where('user_id', $user->id)->firstOrFail();

    expect($membership->role)->toBe(MembershipRole::Manager)
        ->and($membership->status)->toBe(MembershipStatus::Active)
        ->and($otherPromotion->hasActiveMember($user))->toBeFalse()
        ->and($user->promotions()->count())->toBe(1);
});

it('matches the email without case sensitivity and ignores surrounding whitespace', function () {
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create([
        'email' => 'global.member@example.test',
        'status' => UserStatus::Active,
    ]);

    Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => '  GLOBAL.Member@Example.TEST '])
        ->call('addMember')
        ->assertHasNoErrors();

    expect($promotion->hasActiveMember($user))->toBeTrue();
});

it('gives the same generic outcome whenever no member is added', function (string $case) {
    $promotion = Promotion::factory()->create();
    $active = User::factory()->create([
        'first_name' => 'Secretive',
        'last_name' => 'Person',
        'email' => 'secretive.person@example.test',
        'status' => UserStatus::Active,
    ]);
    $inactive = User::factory()->create(['email' => 'inactive.person@example.test', 'status' => UserStatus::Inactive]);
    $existing = User::factory()->create(['email' => 'existing.member@example.test', 'status' => UserStatus::Active]);
    $promotion->users()->attach($existing, [
        'role' => MembershipRole::Member,
        'status' => MembershipStatus::Active,
    ]);

    $email = match ($case) {
        'partial email' => 'secretive',
        'percent wildcard' => '%@example.test',
        'underscore wildcard' => 'secretive_person@example.test',
        'name search' => 'Secretive Person',
        'unknown email' => 'nobody@example.test',
        'inactive user' => $inactive->email,
        default => $existing->email,
    };

    Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => $email])
        ->call('addMember')
        ->assertHasErrors(['email'])
        ->assertSee(__('promotions.member_not_added'))
        ->assertDontSee('Secretive Person')
        ->assertDontSee('secretive.person@example.test')
        ->assertDontSee('inactive.person@example.test');

    expect($promotion->memberships()->count())->toBe(1)
        ->and($promotion->hasActiveMember($active))->toBeFalse();
})->with(['partial email', 'percent wildcard', 'underscore wildcard', 'name search', 'unknown email', 'inactive user']);

it('tells the owner when the account is already a member of the promotion', function (MembershipStatus $status) {
    $promotion = Promotion::factory()->create();
    $existing = User::factory()->create(['email' => 'existing.member@example.test', 'status' => UserStatus::Active]);
    $promotion->users()->attach($existing, [
        'role' => MembershipRole::Member,
        'status' => $status,
    ]);

    Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => 'Existing.Member@example.test'])
        ->call('addMember')
        ->assertHasErrors(['email'])
        ->assertSee(__('promotions.member_already_added'))
        ->assertDontSee(__('promotions.member_not_added'))
        ->assertNotDispatched('flash-message');

    expect($promotion->memberships()->count())->toBe(1);
})->with([
    'active member' => [MembershipStatus::Active],
    'suspended member' => [MembershipStatus::Suspended],
]);

it('confirms an added member and clears an earlier email error', function () {
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['first_name' => 'Ann', 'last_name' => "D'Arcy", 'status' => UserStatus::Active]);

    Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => 'nobody@example.test'])
        ->call('addMember')
        ->assertHasErrors(['email'])
        ->set('email', $user->email)
        ->set('newMemberRole', MembershipRole::Manager->value)
        ->call('addMember')
        ->assertHasNoErrors()
        ->assertDispatched('flash-message', type: 'status', message: "Ann D'Arcy was added to this promotion as Manager.");
});

it('links the email error to the email input', function () {
    $promotion = Promotion::factory()->create();

    $html = Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => 'nobody@example.test'])
        ->call('addMember')
        ->html();

    $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
    $input = $document->getElementById('promotion-member-email');
    $error = $document->getElementById('promotion-member-email-error');

    expect($input?->getAttribute('aria-describedby'))->toBe('promotion-member-email-help promotion-member-email-error')
        ->and($input?->getAttribute('aria-invalid'))->toBe('true')
        ->and($error?->textContent)->toContain(__('promotions.member_not_added'));
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
        ->test(Manage::class, ['promotionId' => $promotion->id, 'email' => 'nobody@example.test'])
        ->call('addMember')
        ->assertHasErrors(['email'])
        ->set("memberRoles.{$user->id}", MembershipRole::Manager->value)
        ->call('updateMemberRole', $user->id)
        ->assertHasNoErrors()
        ->assertDontSee(__('promotions.member_not_added'));
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
