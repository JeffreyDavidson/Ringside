<?php

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Livewire\Promotions\Members\Manage;
use App\Models\Events\Venue;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

function attachPromotionMember(User $user, Promotion $promotion, MembershipRole $role, MembershipStatus $status = MembershipStatus::Active): void
{
    $promotion->users()->attach($user, [
        'role' => $role,
        'status' => $status,
    ]);
}

test('members can view their promotion data but cannot manage it', function () {
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    $user = basicUser();
    $wrestler = Wrestler::factory()->for($promotion, 'promotion')->create();
    $otherWrestler = Wrestler::factory()->for($otherPromotion, 'promotion')->create();
    attachPromotionMember($user, $promotion, MembershipRole::Member);

    $context = app(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();

    expect(Gate::forUser($user)->allows('view', $promotion))->toBeTrue()
        ->and(Gate::forUser($user)->allows('viewAny', Wrestler::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('view', $wrestler))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', Wrestler::class))->toBeFalse()
        ->and(Gate::forUser($user)->allows('update', $wrestler))->toBeFalse()
        ->and(Gate::forUser($user)->allows('view', $otherWrestler))->toBeFalse()
        ->and(Gate::forUser($user)->allows('viewAny', Venue::class))->toBeFalse();

    Livewire::actingAs($user)
        ->test(Manage::class, ['promotionId' => $promotion->id])
        ->assertDontSee('Add an existing user')
        ->assertDontSee('Save role')
        ->assertSee('Member')
        ->call('addMember', User::factory()->create(['status' => UserStatus::Active])->id)
        ->assertForbidden();
});

test('managers can manage promotion data but cannot change promotion settings or memberships', function () {
    $promotion = Promotion::factory()->create();
    $user = basicUser();
    $wrestler = Wrestler::factory()->for($promotion, 'promotion')->create();
    attachPromotionMember($user, $promotion, MembershipRole::Manager);

    $context = app(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();

    expect(Gate::forUser($user)->allows('create', Wrestler::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('update', $wrestler))->toBeTrue()
        ->and(Gate::forUser($user)->allows('retire', $wrestler))->toBeTrue()
        ->and(Gate::forUser($user)->allows('update', $promotion))->toBeFalse()
        ->and(Gate::forUser($user)->allows('manageMembers', $promotion))->toBeFalse();

    Livewire::actingAs($user)
        ->test(Manage::class, ['promotionId' => $promotion->id])
        ->assertDontSee('Add an existing user')
        ->assertDontSee('Save role')
        ->assertSee('Manager')
        ->call('updateMemberStatus', User::factory()->create()->id, MembershipStatus::Suspended->value)
        ->assertForbidden();
});

test('owners can update promotion settings and manage membership roles', function () {
    $promotion = Promotion::factory()->create();
    $user = basicUser();
    attachPromotionMember($user, $promotion, MembershipRole::Owner);

    expect(Gate::forUser($user)->allows('view', $promotion))->toBeTrue()
        ->and(Gate::forUser($user)->allows('update', $promotion))->toBeTrue()
        ->and(Gate::forUser($user)->allows('manageMembers', $promotion))->toBeTrue();

    $newMember = User::factory()->create(['status' => UserStatus::Active]);

    Livewire::actingAs($user)
        ->test(Manage::class, ['promotionId' => $promotion->id])
        ->assertSee('Add an existing user')
        ->assertSee('Save role')
        ->call('addMember', $newMember->id)
        ->assertHasNoErrors();

    expect($promotion->hasActiveMember($newMember))->toBeTrue();
});

test('suspended promotion members no longer have access', function () {
    $promotion = Promotion::factory()->create();
    $user = basicUser();
    attachPromotionMember($user, $promotion, MembershipRole::Owner, MembershipStatus::Suspended);

    $context = app(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();

    expect(Gate::forUser($user)->allows('view', $promotion))->toBeFalse()
        ->and(Gate::forUser($user)->allows('viewAny', Wrestler::class))->toBeFalse()
        ->and(Gate::forUser($user)->allows('manageMembers', $promotion))->toBeFalse();
});
