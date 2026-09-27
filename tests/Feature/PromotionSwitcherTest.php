<?php

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;

function attachActivePromotionMembership(User $user, Promotion $promotion): void
{
    $promotion->users()->attach($user, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);
}

test('a user can switch to another active promotion', function () {
    $user = User::factory()->basicUser()->create(['status' => UserStatus::Active]);
    $firstPromotion = Promotion::factory()->create();
    $secondPromotion = Promotion::factory()->create();
    attachActivePromotionMembership($user, $firstPromotion);
    attachActivePromotionMembership($user, $secondPromotion);

    $response = $this
        ->from(route('dashboard'))
        ->actingAs($user)
        ->post(route('promotions.switch'), ['promotion_id' => $secondPromotion->id]);

    $response
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', __('promotions.switched'));

    expect(session('active_promotion_id'))->toBe($secondPromotion->id);
});

test('a user cannot switch to a promotion without an active membership', function () {
    $user = User::factory()->basicUser()->create(['status' => UserStatus::Active]);
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    attachActivePromotionMembership($user, $promotion);

    $this
        ->actingAs($user)
        ->post(route('promotions.switch'), ['promotion_id' => $otherPromotion->id])
        ->assertForbidden();

    expect(session('active_promotion_id'))->toBeNull();
});

test('the authenticated layout displays active promotions', function () {
    $user = User::factory()->basicUser()->create(['status' => UserStatus::Active]);
    $promotion = Promotion::factory()->create(['name' => 'Ringside Wrestling']);
    attachActivePromotionMembership($user, $promotion);

    $this
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Ringside Wrestling')
        ->assertSee(__('promotions.switch'));
});
