<?php

use App\Actions\Promotions\UpdatePromotionMemberRoleAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;

test('it updates the role of a user in the selected promotion', function () {
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create();
    $promotion->users()->attach($user, [
        'role' => MembershipRole::Member,
        'status' => MembershipStatus::Active,
    ]);

    app(UpdatePromotionMemberRoleAction::class)->handle($promotion, $user, MembershipRole::Owner);

    expect($promotion->memberships()->where('user_id', $user->id)->firstOrFail()->role)
        ->toBe(MembershipRole::Owner);
});
