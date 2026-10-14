<?php

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;

function attachActivePromotionMembership(User $user, Promotion $promotion): void
{
    $promotion->users()->attach($user, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);
}

test('the authenticated layout displays active promotions', function () {
    $user = User::factory()->basicUser()->create(['status' => UserStatus::Active]);
    $promotion = Promotion::factory()->create(['name' => 'Ringside Wrestling']);
    attachActivePromotionMembership($user, $promotion);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Ringside Wrestling')
        ->assertSee(__('promotions.switch'));
});
