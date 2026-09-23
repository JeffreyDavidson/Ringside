<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Livewire\Promotions\Members\Manage;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;
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
        ->assertSee('No users are assigned to this promotion.')
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

it('adds an existing global account to only the selected promotion with its selected role', function () {
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => UserStatus::Active]);

    Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id])
        ->set('search', $user->email)
        ->set('newMemberRole', MembershipRole::Manager->value)
        ->call('addMember', $user->id)
        ->assertHasNoErrors();

    $membership = $promotion->memberships()->where('user_id', $user->id)->firstOrFail();

    expect($membership->role)->toBe(MembershipRole::Manager)
        ->and($membership->status)->toBe(MembershipStatus::Active)
        ->and($otherPromotion->hasActiveMember($user))->toBeFalse()
        ->and($user->promotions()->count())->toBe(1);
});

it('searches for available accounts by email without case sensitivity', function () {
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create([
        'email' => 'global.member@example.test',
        'status' => UserStatus::Active,
    ]);

    Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id])
        ->set('search', mb_strtoupper($user->email))
        ->assertSee($user->email);
});

it('does not allow an inactive account to be added as a promotion member', function () {
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => UserStatus::Inactive]);

    Livewire::actingAs(administrator())
        ->test(Manage::class, ['promotionId' => $promotion->id])
        ->set('newMemberRole', MembershipRole::Member->value)
        ->call('addMember', $user->id)
        ->assertHasErrors('userId');

    expect($promotion->memberships()->exists())->toBeFalse();
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
        ->assertHasNoErrors();

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
